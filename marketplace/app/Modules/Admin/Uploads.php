<?php
declare(strict_types=1);

namespace App\Modules\Admin;

/** One image upload validated the same way everywhere in the admin (JPG / PNG / WebP, real content, size limit). */
final class Uploads
{
    /**
     * @param string[] $errors appended to
     * @return array{tmp:string, ext:string, width:int, height:int}|null null = no file sent, or invalid (see $errors)
     */
    public static function check(?array $file, array &$errors, int $maxBytes = 3145728, string $noun = 'The image'): ?array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $mb = rtrim(rtrim(number_format($maxBytes / 1048576, 1, '.', ''), '0'), '.');
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? $noun . ' is too large (max ' . $mb . ' MB).'
                : $noun . ' upload failed. Please try again.';
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid upload.';
            return null;
        }
        if ($file['size'] > $maxBytes) {
            $errors[] = $noun . ' is too large (max ' . $mb . ' MB).';
            return null;
        }
        $info = @getimagesize($file['tmp_name']);
        $map  = [IMAGETYPE_JPEG => ['image/jpeg', 'jpg'], IMAGETYPE_PNG => ['image/png', 'png'], IMAGETYPE_WEBP => ['image/webp', 'webp']];
        if ($info === false || !isset($map[$info[2]])) {
            $errors[] = $noun . ' must be a JPG, PNG or WebP file.';
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($file['tmp_name']) !== $map[$info[2]][0]) {
            $errors[] = $noun . ' file type does not match its contents.';
            return null;
        }
        return ['tmp' => $file['tmp_name'], 'ext' => $map[$info[2]][1], 'width' => (int) $info[0], 'height' => (int) $info[1]];
    }

    /** Moves a checked upload into public/uploads/{subdir}/ under a random name. Returns the path relative to uploads/, or null. */
    public static function store(array $upload, string $subdir): ?string
    {
        $dir = PUBLIC_PATH . '/uploads/' . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
        $name = bin2hex(random_bytes(10)) . '.' . $upload['ext'];
        if (!@move_uploaded_file($upload['tmp'], $dir . '/' . $name)) {
            return null;
        }
        return $subdir . '/' . $name;
    }
}
