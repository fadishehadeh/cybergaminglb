<?php
declare(strict_types=1);

namespace App\Modules\Seller;

/** Product image upload: jpg/png/webp, max 3 MB, verified by content (getimagesize + finfo), random file name. */
final class ImageUpload
{
    public const MAX_BYTES = 3 * 1024 * 1024;
    private const MAX_SIDE = 8000;

    /**
     * Validates the upload. Returns ['tmp' => ..., 'ext' => ...] for a good file, null for "no file" or an error
     * (errors are appended to $errors).
     *
     * @return array{tmp:string, ext:string}|null
     */
    public static function check(?array $file, array &$errors): ?array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'The image is too large (max 3 MB).'
                : 'The image upload failed. Please try again.';
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid upload.';
            return null;
        }
        if ($file['size'] > self::MAX_BYTES) {
            $errors[] = 'The image is too large (max 3 MB).';
            return null;
        }
        $info = @getimagesize($file['tmp_name']);
        $map  = [IMAGETYPE_JPEG => ['image/jpeg', 'jpg'], IMAGETYPE_PNG => ['image/png', 'png'], IMAGETYPE_WEBP => ['image/webp', 'webp']];
        if ($info === false || !isset($map[$info[2]])) {
            $errors[] = 'The image must be a JPG, PNG or WebP file.';
            return null;
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_SIDE || $info[1] > self::MAX_SIDE) {
            $errors[] = 'The image dimensions are not supported (max ' . self::MAX_SIDE . ' px per side).';
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($file['tmp_name']) !== $map[$info[2]][0]) {
            $errors[] = 'The image file type does not match its contents.';
            return null;
        }
        return ['tmp' => $file['tmp_name'], 'ext' => $map[$info[2]][1]];
    }

    /** Moves the file to public/uploads/products/ under a random name. Returns the path relative to public/uploads, or null. */
    public static function store(array $upload): ?string
    {
        $dir = PUBLIC_PATH . '/uploads/products';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $name = bin2hex(random_bytes(10)) . '.' . $upload['ext'];
        if (!@move_uploaded_file($upload['tmp'], $dir . '/' . $name)) {
            return null;
        }
        return 'products/' . $name;
    }
}
