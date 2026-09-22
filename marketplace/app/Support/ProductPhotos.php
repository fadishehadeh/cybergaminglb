<?php
declare(strict_types=1);

namespace App\Support;

/**
 * The three photos every USED listing needs: the disc, the box from outside, the box from inside (+ up to 3 extras).
 * Every upload is re-encoded to a resized JPEG, which also strips EXIF/GPS data (phone photos leak the seller's location).
 *
 * Form fields: photo_disc, photo_box_outside, photo_box_inside (single files) and photo_extra[] (optional, max 3).
 * Typical use:   $r = ProductPhotos::stage($_FILES, ProductPhotos::existingKinds($productId), $mustHaveThree);
 *                if ($r['errors']) { ...show them... } else { ProductPhotos::attach($productId, $r['staged']); }
 */
final class ProductPhotos
{
    /** Games: the disc, the box from outside, the box from inside. */
    public const LABELS = ['disc' => 'Disc', 'box_outside' => 'Box, outside', 'box_inside' => 'Box, inside'];
    /** Hardware: the unit from the front, the back with its ports, and everything in the box. "powered_on" is optional. */
    public const LABELS_HARDWARE = ['unit_front' => 'Unit, front', 'unit_back' => 'Unit, back and ports', 'box_accessories' => 'Box and accessories'];
    public const OPTIONAL_HARDWARE = ['powered_on' => 'Powered on'];
    private const ORDER = ['disc', 'box_outside', 'box_inside', 'unit_front', 'unit_back', 'box_accessories', 'powered_on', 'extra'];

    /** Required photo kinds and labels for a listing type: 'game' (default), 'hardware'. Digital items need none. */
    public static function labels(string $type = 'game'): array
    {
        return match ($type) {
            'hardware' => self::LABELS_HARDWARE,
            'digital'  => [],
            default    => self::LABELS,
        };
    }

    /** Listing type of a category: game | hardware | digital (categories.kind). */
    public static function typeForCategory(int $categoryId): string
    {
        $kind = db()->fetchValue('SELECT kind FROM categories WHERE id = ?', [$categoryId]);
        return in_array($kind, ['game', 'hardware', 'digital'], true) ? (string) $kind : 'game';
    }
    private const MAX_BYTES = 10485760;
    private const MAX_EXTRA = 3;
    private const MAX_SIDE = 1600;

    /** @return string[] kinds already stored for the product */
    public static function existingKinds(int $productId): array
    {
        return array_column(db()->fetchAll('SELECT DISTINCT kind FROM product_images WHERE product_id = ?', [$productId]), 'kind');
    }

    /**
     * Validate and process uploads. Nothing is attached to a product yet; on any error every processed file is removed.
     * @param bool $required demand the three kinds (unless already stored)
     * @return array{errors: string[], staged: array<int, array{kind: string, path: string}>}
     */
    public static function stage(array $files, array $existingKinds = [], bool $required = true, string $type = 'game'): array
    {
        $errors = [];
        $staged = [];

        $labels = self::labels($type);
        $optional = $type === 'hardware' ? self::OPTIONAL_HARDWARE : [];
        foreach ($optional as $kind => $label) {
            $f = $files['photo_' . $kind] ?? null;
            if (is_array($f) && (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$path, $err] = self::processOne($f);
                if ($err !== null) {
                    $errors[] = "{$label}: {$err}";
                } elseif ($path !== null) {
                    $staged[] = ['kind' => $kind, 'path' => $path];
                }
            }
        }
        foreach ($labels as $kind => $label) {
            $f = $files['photo_' . $kind] ?? null;
            $has = is_array($f) && (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if (!$has) {
                if ($required && !in_array($kind, $existingKinds, true)) {
                    $errors[] = "Please add a photo of the {$label}.";
                }
                continue;
            }
            [$path, $err] = self::processOne($f);
            if ($err !== null) {
                $errors[] = "{$label}: {$err}";
            } elseif ($path !== null) {
                $staged[] = ['kind' => $kind, 'path' => $path];
            }
        }

        foreach (self::normaliseMulti($files['photo_extra'] ?? null) as $i => $f) {
            if ($i >= self::MAX_EXTRA) {
                break;
            }
            if ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            [$path, $err] = self::processOne($f);
            if ($err !== null) {
                $errors[] = 'Extra photo ' . ($i + 1) . ": {$err}";
            } elseif ($path !== null) {
                $staged[] = ['kind' => 'extra', 'path' => $path];
            }
        }

        if ($errors) {
            self::discard($staged);
            return ['errors' => $errors, 'staged' => []];
        }
        return ['errors' => [], 'staged' => $staged];
    }

    /**
     * Loose photos not tied to a product (e.g. photos sent with a sell request). Field is a multi-file input (name="photos[]").
     * @return array{errors: string[], staged: array<int, array{kind: string, path: string}>}
     */
    public static function stageLoose(?array $multi, int $max = 6): array
    {
        $errors = [];
        $staged = [];
        foreach (self::normaliseMulti($multi) as $i => $f) {
            if ($i >= $max) {
                break;
            }
            if ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            [$path, $err] = self::processOne($f);
            if ($err !== null) {
                $errors[] = 'Photo ' . ($i + 1) . ": {$err}";
            } elseif ($path !== null) {
                $staged[] = ['kind' => 'extra', 'path' => $path];
            }
        }
        if ($errors) {
            self::discard($staged);
            return ['errors' => $errors, 'staged' => []];
        }
        return ['errors' => [], 'staged' => $staged];
    }

    /** Attach staged photos: a new disc/box photo replaces the old one of the same kind. Returns paths saved. */
    public static function attach(int $productId, array $staged): array
    {
        $saved = [];
        foreach ($staged as $s) {
            if ($s['kind'] !== 'extra') {
                foreach (db()->fetchAll('SELECT id, path FROM product_images WHERE product_id = ? AND kind = ?', [$productId, $s['kind']]) as $old) {
                    self::unlink($old['path']);
                    db()->execute('DELETE FROM product_images WHERE id = ?', [$old['id']]);
                }
            }
            $order = $s['kind'] === 'extra' ? 10 : (int) array_search($s['kind'], self::ORDER, true);
            db()->execute('INSERT INTO product_images (product_id, path, kind, sort_order) VALUES (?, ?, ?, ?)', [$productId, $s['path'], $s['kind'], $order]);
            $saved[] = $s['path'];
        }
        return $saved;
    }

    /** Photos of one product in display order (disc, box outside, box inside, extras). */
    public static function forProduct(int $productId): array
    {
        return db()->fetchAll(
            "SELECT id, path, kind FROM product_images WHERE product_id = ? ORDER BY FIELD(kind,'disc','box_outside','box_inside','unit_front','unit_back','box_accessories','powered_on','extra'), sort_order, id",
            [$productId]
        );
    }

    /** @return string[] required kinds this product does not have yet */
    public static function missingKinds(int $productId, string $type = 'game'): array
    {
        return array_values(array_diff(array_keys(self::labels($type)), self::existingKinds($productId)));
    }

    public static function deleteForProduct(int $productId): void
    {
        foreach (db()->fetchAll('SELECT path FROM product_images WHERE product_id = ?', [$productId]) as $r) {
            self::unlink($r['path']);
        }
        db()->execute('DELETE FROM product_images WHERE product_id = ?', [$productId]);
    }

    /** Discard files staged but not attached (e.g. when later validation fails). */
    public static function discard(array $staged): void
    {
        foreach ($staged as $s) {
            self::unlink($s['path']);
        }
    }

    private static function unlink(string $relative): void
    {
        $file = PUBLIC_PATH . '/uploads/' . ltrim($relative, '/');
        if (str_starts_with($relative, 'products/') && is_file($file)) {
            @unlink($file);
            if (class_exists(\App\Support\ImageVariants::class)) {
                \App\Support\ImageVariants::delete($file); // its WebP siblings
            }
        }
    }

    private static function normaliseMulti(?array $f): array
    {
        if (!$f || !isset($f['name']) || !is_array($f['name'])) {
            return [];
        }
        $out = [];
        foreach (array_keys($f['name']) as $i) {
            $out[] = [
                'name' => $f['name'][$i], 'type' => $f['type'][$i] ?? '', 'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE, 'size' => $f['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /** @return array{0: ?string, 1: ?string} [relative path, error message] */
    private static function processOne(array $f): array
    {
        $code = (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
            return [null, 'the photo is too large (max 10 MB).'];
        }
        if ($code !== UPLOAD_ERR_OK) {
            return [null, 'the upload failed, please try again.'];
        }
        if (($f['size'] ?? 0) > self::MAX_BYTES) {
            return [null, 'the photo is too large (max 10 MB).'];
        }
        $tmp = (string) $f['tmp_name'];
        if (!is_file($tmp) || (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp))) {
            return [null, 'invalid upload.'];
        }
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [null, 'please upload a JPG, PNG or WebP photo.'];
        }
        if ($info[0] < 400 || $info[1] < 400) {
            return [null, 'the photo is too small (at least 400 px). Move closer and retake it.'];
        }
        if (!function_exists('imagecreatetruecolor')) {
            return [null, 'photo processing is not available on this server.'];
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
            default        => @imagecreatefromwebp($tmp),
        };
        if (!$img) {
            return [null, 'that image could not be read.'];
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $rot = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($rot !== 0 && ($rotated = imagerotate($img, $rot, 0))) {
                $img = $rotated;
            }
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > self::MAX_SIDE) {
            $nw = $w >= $h ? self::MAX_SIDE : (int) round($w * self::MAX_SIDE / $h);
            $nh = $w >= $h ? (int) round($h * self::MAX_SIDE / $w) : self::MAX_SIDE;
            // imagescale() is unreliable across GD builds (fails on some servers regardless of the mode argument);
            // imagecopyresampled has no such quirk.
            $resized = imagecreatetruecolor($nw, $nh);
            if ($resized && imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h)) {
                $img = $resized;
            }
        }
        $dir = PUBLIC_PATH . '/uploads/products';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return [null, 'could not save the photo.'];
        }
        $name = bin2hex(random_bytes(12)) . '.jpg';
        if (!imagejpeg($img, $dir . '/' . $name, 82)) {
            return [null, 'could not save the photo.'];
        }
        // WebP siblings (-w300, -w600, full) for the storefront's <picture>; failure is harmless (the JPEG is served)
        if (class_exists(\App\Support\ImageVariants::class)) {
            \App\Support\ImageVariants::generate($dir . '/' . $name);
        }
        return ['products/' . $name, null];
    }
}
