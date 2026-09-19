<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Anti-bypass filter: detects contact details (phone numbers, emails, links, social handles, "call me" style
 * phrases) so sellers cannot use a listing to take deals off the platform.
 * Tuned to ignore years, prices, storage sizes and model numbers ("PS4 Pro 1TB", "2019", "$25").
 */
final class ContactFilter
{
    private const TLDS = 'com|net|org|info|biz|co|io|me|lb|shop|store|online|site|app|link|ly|gl|gg|tv|xyz|ai|edu|gov|cc|ws|fm';

    /** Latin words/phrases (matched as whole words). */
    private const WORDS = [
        'whats\s*app', 'watsapp', 'whatsap', 'wtsp', 'wtsap', 'wtsapp', 'wa\.me',
        'instagram', 'insta', 'telegram', 'tele\s+gram', 'viber', 'snapchat', 'facebook', 'messenger', 'tiktok',
        'call\s+me', 'text\s+me', 'sms\s+me', 'dm\s+me', 'pm\s+me', 'message\s+me', 'msg\s+me', 'contact\s+me', 'reach\s+me',
        'ring\s+me', 'phone\s+me', 'hit\s+me\s+up', 'inbox\s+me',
        'my\s+(?:number|phone|mobile|cell|whatsapp|insta|ig|email|mail)',
        'phone\s+number', 'mobile\s+number', 'contact\s+(?:number|info|details)',
        'gmail', 'hotmail', 'yahoo', 'outlook', 'icloud',
        'dot\s+(?:com|net|org|lb)', 'at\s+gmail',
    ];

    /** Arabic words (no word-boundary logic needed). */
    private const WORDS_AR = ['واتساب', 'واتس', 'وتساب', 'اتصل', 'تواصل', 'رقمي', 'رقم هاتف', 'انستا', 'إنستا', 'انستغرام', 'تلغرام', 'تيليغرام', 'ايميل', 'إيميل'];

    public static function containsContact(string $text): bool
    {
        return self::reason($text) !== null;
    }

    /** Which rule matched: 'email', 'handle', 'url', 'phone', 'word', or null when the text is clean. */
    public static function reason(string $text): ?string
    {
        $t = self::normalise($text);
        if ($t === '') {
            return null;
        }

        // emails
        if (preg_match('/[a-z0-9._%+\-]+\s?@\s?[a-z0-9\-]+(?:\.[a-z0-9\-]+)+/iu', $t)) {
            return 'email';
        }
        // @handles ("IG @mystore"): an @ directly followed by 3+ handle characters, not glued to a word before it
        if (preg_match('/(?<![\w@])@[a-z0-9_.]{3,}/iu', $t)) {
            return 'handle';
        }
        // URLs / domains
        if (preg_match('#(?:https?://|www\.)#i', $t)
            || preg_match('/(?<![\w.])[a-z0-9][a-z0-9\-]*(?:\.[a-z0-9\-]+)*\.(?:' . self::TLDS . ')(?![\w\-])(?:\/\S*)?/iu', $t)) {
            return 'url';
        }
        // "call me", "whatsapp", "instagram"...
        if (preg_match('/(?<![a-z])(?:' . implode('|', self::WORDS) . ')(?![a-z])/iu', $t)
            || preg_match('/(?:' . implode('|', array_map(static fn (string $w): string => preg_quote($w, '/'), self::WORDS_AR)) . ')/u', $t)) {
            return 'word';
        }
        if (self::hasPhone($t)) {
            return 'phone';
        }
        return null;
    }

    /** Human message for the seller. */
    public static function message(string $field): string
    {
        return $field . ' looks like it contains contact details (a phone number, email, link, social handle or "call me" style wording). '
            . 'CyberGaming is the only contact with buyers, so please remove it and describe just the item.';
    }

    /** Lower-case, unify digits/dots/at-signs and drop invisible characters used to dodge filters. */
    private static function normalise(string $text): string
    {
        $text = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
        // Arabic-Indic and full-width digits -> ASCII
        $text = strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            '＠' => '@', '．' => '.', '。' => '.', '＋' => '+',
        ]);
        // "name [at] mail [dot] com" style obfuscation
        $text = preg_replace('/\s*[\[\(\{]\s*at\s*[\]\)\}]\s*/i', '@', $text) ?? $text;
        $text = preg_replace('/\s*[\[\(\{]\s*dot\s*[\]\)\}]\s*/i', '.', $text) ?? $text;
        return trim($text);
    }

    /**
     * Phone numbers: a run of digits (optionally separated by single spaces, dashes, dots or brackets) with 7+ digits.
     * Runs made only of years ("2018 2019", "2018-2019") and dates ("12-05-2020") are not phone numbers.
     */
    private static function hasPhone(string $t): bool
    {
        if (!preg_match_all('/\+?\(?\d(?:[\s\-.()]{0,2}\d)*/u', $t, $m)) {
            return false;
        }
        foreach ($m[0] as $run) {
            $digits = preg_replace('/\D+/', '', $run) ?? '';
            if (strlen($digits) < 7) {
                continue;
            }
            $groups = preg_split('/\D+/', trim($run, "+ ()-.\t"), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (self::allYears($groups) || self::looksLikeDate($groups)) {
                continue;
            }
            return true;
        }
        return false;
    }

    /** @param string[] $groups */
    private static function allYears(array $groups): bool
    {
        if (count($groups) < 2) {
            return false;
        }
        foreach ($groups as $g) {
            if (!preg_match('/^(?:19[7-9]\d|20\d{2}|21\d{2})$/', $g)) {
                return false;
            }
        }
        return true;
    }

    /** @param string[] $groups */
    private static function looksLikeDate(array $groups): bool
    {
        return count($groups) === 3 && strlen($groups[0]) <= 2 && strlen($groups[1]) <= 2 && in_array(strlen($groups[2]), [2, 4], true);
    }
}
