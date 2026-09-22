<?php
declare(strict_types=1);

namespace App\Support;

/**
 * WebP siblings for JPEG/PNG uploads, next to the original:
 *   cover.jpg  ->  cover-w300.webp, cover-w600.webp, cover.webp (full size)
 * Nothing here ever throws: without GD/WebP support every method just returns without doing anything, and the
 * storefront falls back to the original file (see Ui::picture()).
 */
final class ImageVariants
{
    /** Width => WebP quality. The full-size variant uses 0 as its width key. */
    public const WIDTHS = [300 => 78, 600 => 80];
    public const FULL_QUALITY = 80;

    public static function supported(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatetruecolor')
            && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng');
    }

    /** Absolute path of a variant. $width 0 = full size. */
    public static function variantPath(string $file, int $width = 0): string
    {
        $base = preg_replace('/\.(jpe?g|png)$/i', '', $file) ?? $file;
        return $base . ($width > 0 ? '-w' . $width : '') . '.webp';
    }

    /** True for files we can make variants of (by extension). */
    public static function isSource(string $file): bool
    {
        return (bool) preg_match('/\.(jpe?g|png)$/i', $file);
    }

    /** True when the variants that apply to this file exist and are at least as new as the source. */
    public static function upToDate(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }
        $mtime = (int) @filemtime($file);
        $info = @getimagesize($file);
        $srcW = $info ? (int) $info[0] : 0;
        foreach (self::WIDTHS as $width => $q) {
            if ($srcW > 0 && $srcW <= $width) {
                continue; // never generated for a source this small
            }
            $v = self::variantPath($file, $width);
            if (!is_file($v) || (int) @filemtime($v) < $mtime) {
                return false;
            }
        }
        $full = self::variantPath($file, 0);
        return is_file($full) && (int) @filemtime($full) >= $mtime;
    }

    /**
     * Generate the WebP variants for one JPEG/PNG. Idempotent: variants that are already newer than the source are kept.
     * A variant is never wider than the source (small originals only get the sizes they can honestly fill), and a
     * full-size WebP that would not be smaller than the original is dropped (the original is served instead).
     * @return string[] absolute paths of the variants that exist afterwards
     */
    public static function generate(string $file): array
    {
        try {
            return self::doGenerate($file);
        } catch (\Throwable) {
            return [];
        }
    }

    /** Remove the variants of an original (used when a file is deleted). */
    public static function delete(string $file): void
    {
        foreach ([0, ...array_keys(self::WIDTHS)] as $w) {
            $v = self::variantPath($file, $w);
            if (is_file($v)) {
                @unlink($v);
            }
        }
    }

    /** Variants that exist, as [width => absolute path]; width 0 = full. */
    public static function existing(string $file): array
    {
        $out = [];
        foreach ([300, 600, 0] as $w) {
            $v = self::variantPath($file, $w);
            if (is_file($v) && filesize($v) > 0) {
                $out[$w] = $v;
            }
        }
        return $out;
    }

    private static function doGenerate(string $file): array
    {
        if (!self::supported() || !self::isSource($file) || !is_file($file)) {
            return [];
        }
        if (self::upToDate($file)) {
            return array_values(self::existing($file));
        }
        $info = @getimagesize($file);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || $info[0] < 1 || $info[1] < 1) {
            return [];
        }
        // decoding a huge image can exhaust memory: skip anything above ~40 megapixels
        if ($info[0] * $info[1] > 40_000_000) {
            return [];
        }
        $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
        if (!$src) {
            return [];
        }
        if ($info[2] === IMAGETYPE_PNG) {
            imagepalettetotruecolor($src);
            imagealphablending($src, false);
            imagesavealpha($src, true);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $out = [];

        foreach (self::WIDTHS as $width => $quality) {
            $target = self::variantPath($file, $width);
            if ($w <= $width) {
                // never upscale: the source is not wider than this size, the full-size WebP covers it
                if (is_file($target)) {
                    @unlink($target);
                }
                continue;
            }
            $scaled = self::resample($src, $width, (int) max(1, round($h * $width / $w)));
            if ($scaled && self::write($scaled, $target, $quality)) {
                $out[] = $target;
            }
            if ($scaled) {
                imagedestroy($scaled);
            }
        }

        $full = self::variantPath($file, 0);
        if (self::write($src, $full, self::FULL_QUALITY)) {
            // a WebP that is not smaller than the original is pointless: drop it and let the original be served
            if (filesize($full) >= filesize($file)) {
                @unlink($full);
            } else {
                $out[] = $full;
            }
        }
        imagedestroy($src);
        return $out;
    }

    /**
     * imagescale() with an explicit interpolation mode is unreliable across GD builds — on some servers it returns
     * false for any mode argument, even the documented default. imagecopyresampled has no such quirk.
     */
    private static function resample(\GdImage $src, int $w, int $h): ?\GdImage
    {
        $dst = imagecreatetruecolor($w, $h);
        if (!$dst) {
            return null;
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src))) {
            imagedestroy($dst);
            return null;
        }
        return $dst;
    }

    private static function write(\GdImage $img, string $target, int $quality): bool
    {
        $tmp = $target . '.tmp' . getmypid();
        if (!@imagewebp($img, $tmp, $quality)) {
            @unlink($tmp);
            return false;
        }
        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }
}
