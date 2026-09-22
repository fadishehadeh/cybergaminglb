<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Allow-list HTML cleaner for admin-written content (guides). Anything not explicitly allowed is removed:
 * scripts, styles, iframes, forms, event handlers, javascript: URLs, inline styles and unknown tags (their text is kept).
 * Run it when SAVING and again when RENDERING.
 */
final class SafeHtml
{
    private const TAGS = [
        'p' => [], 'br' => [], 'hr' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 'code' => [], 'pre' => [], 'blockquote' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'a' => ['href', 'title'], 'img' => ['src', 'alt', 'width', 'height'], 'small' => [], 'sup' => [], 'sub' => [],
    ];
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'noscript', 'template', 'link', 'meta', 'base'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="sh-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $root = $doc->getElementById('sh-root');
        if (!$root) {
            return e($html);
        }
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function walk(\DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $c) {
            $children[] = $c;
        }
        foreach ($children as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child);
            if (!isset(self::TAGS[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, self::TAGS[$tag], true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && !self::safeUrl($attr->value, $name === 'src')) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a' && $child->hasAttribute('href')) {
                $href = $child->getAttribute('href');
                if (preg_match('#^https?://#i', $href) && !str_contains($href, (string) parse_url((string) config('app.url'), PHP_URL_HOST))) {
                    $child->setAttribute('rel', 'noopener nofollow');
                }
            }
            if ($tag === 'img') {
                if (!$child->hasAttribute('src')) {
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('loading', 'lazy');
            }
        }
    }

    private static function safeUrl(string $url, bool $image): bool
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x1F]/', $url)) {
            return false;
        }
        if (preg_match('#^(https?://|/|\#)#i', $url)) {
            return !preg_match('#^//#', $url);
        }
        return !$image && (bool) preg_match('#^(mailto:|tel:)#i', $url);
    }
}
