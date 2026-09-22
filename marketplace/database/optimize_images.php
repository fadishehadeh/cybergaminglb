<?php
declare(strict_types=1);

/**
 * Generates WebP variants (-w300, -w600, full size) for every JPEG/PNG under uploads/covers and uploads/products.
 * Idempotent: files whose variants are already up to date are skipped. SVGs are ignored.
 *
 *   php database/optimize_images.php                       (web root = ../public)
 *   php database/optimize_images.php --public=/home/site/public_html
 *   php database/optimize_images.php --force               (regenerate everything)
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

require dirname(__DIR__) . '/app/Support/ImageVariants.php';

use App\Support\ImageVariants;

$public = dirname(__DIR__) . '/public';
$force = false;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--public=')) {
        $public = rtrim(substr($arg, 9), '/\\');
    } elseif ($arg === '--force') {
        $force = true;
    } else {
        fwrite(STDERR, "Unknown option: $arg\nUsage: php database/optimize_images.php [--public=/path/to/web/root] [--force]\n");
        exit(1);
    }
}
if (!is_dir($public . '/uploads')) {
    fwrite(STDERR, "No uploads folder under $public\n");
    exit(1);
}
if (!ImageVariants::supported()) {
    fwrite(STDERR, "PHP GD with WebP support is not available: nothing to do.\n");
    exit(1);
}

$done = $skipped = $failed = $svg = 0;
$origBytes = $webpBytes = 0;   // bytes of originals processed vs. the full-size WebP served instead
$w300 = 0;
foreach (['covers', 'products'] as $dir) {
    $path = $public . '/uploads/' . $dir;
    if (!is_dir($path)) {
        continue;
    }
    foreach (new DirectoryIterator($path) as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $file = $f->getPathname();
        $ext = strtolower($f->getExtension());
        if ($ext === 'svg') {
            $svg++;
            continue;
        }
        if (!ImageVariants::isSource($file)) {
            continue; // includes the .webp files themselves
        }
        if (!$force && ImageVariants::upToDate($file)) {
            $skipped++;
            continue;
        }
        if ($force) {
            ImageVariants::delete($file);
        }
        $made = ImageVariants::generate($file);
        if (!$made) {
            $failed++;
            fwrite(STDERR, "  failed: $dir/{$f->getFilename()}\n");
            continue;
        }
        $done++;
        $variants = ImageVariants::existing($file);
        $origBytes += (int) filesize($file);
        // what a phone/desktop card downloads instead of the original: the smallest variant, else the full one, else the original
        $served = $variants[300] ?? $variants[600] ?? $variants[0] ?? null;
        $webpBytes += $served ? (int) filesize($served) : (int) filesize($file);
        $w300 += isset($variants[300]) ? 1 : 0;
    }
}

$saved = $origBytes - $webpBytes;
printf("Optimised: %d   already up to date: %d   failed: %d   SVG skipped: %d\n", $done, $skipped, $failed, $svg);
if ($done > 0) {
    printf("Originals processed: %s   card-size WebP (smallest variant): %s   saved per card view: %s (%.0f%%)\n",
        fmt($origBytes), fmt($webpBytes), fmt($saved), $origBytes > 0 ? $saved * 100 / $origBytes : 0);
}
exit($failed > 0 ? 2 : 0);

function fmt(int $bytes): string
{
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 2) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
}
