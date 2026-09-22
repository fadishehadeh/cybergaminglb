<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/**
 * Sends machine-readable responses (sitemap, robots.txt, feeds, llms.txt) with public caching headers and answers
 * conditional requests: If-None-Match wins over If-Modified-Since, a match returns 304 with no body.
 */
final class Cacheable
{
    public const MAX_AGE = 3600;

    public static function send(string $body, string $contentType, int $lastModified): void
    {
        // These files are identical for every visitor: no session cookie and none of the "never cache" headers PHP adds for sessions.
        foreach (['Set-Cookie', 'Pragma', 'Expires', 'Cache-Control'] as $h) {
            header_remove($h);
        }
        $lastModified = $lastModified > 0 ? min($lastModified, time()) : time();
        $etag = '"' . md5($body) . '"';
        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=' . self::MAX_AGE);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: ' . $etag);
        header('Vary: Accept-Encoding');

        if (self::notModified($etag, $lastModified)) {
            http_response_code(304);
            exit;
        }
        http_response_code(200);
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    private static function notModified(string $etag, int $lastModified): bool
    {
        $inm = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($inm !== '') {
            if ($inm === '*') {
                return true;
            }
            foreach (explode(',', $inm) as $candidate) {
                // weak validators and the "-gzip" suffix some servers add are ignored for comparison
                $candidate = preg_replace('/^\s*W\//', '', trim($candidate)) ?? '';
                $candidate = str_replace('-gzip"', '"', $candidate);
                if ($candidate === $etag) {
                    return true;
                }
            }
            return false;
        }
        $ims = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        if ($ims !== '') {
            $t = strtotime($ims);
            return $t !== false && $t >= $lastModified;
        }
        return false;
    }

    /** Newest of several timestamps given as DB datetime strings, unix ints or null. */
    public static function newest(array $values): int
    {
        $max = 0;
        foreach ($values as $v) {
            if ($v === null || $v === '' || $v === false) {
                continue;
            }
            $t = is_int($v) ? $v : (int) strtotime((string) $v);
            $max = max($max, $t);
        }
        return $max;
    }
}
