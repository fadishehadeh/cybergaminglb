<?php
declare(strict_types=1);

namespace App\Support;

/** Anonymous public handle for an account. Random, unique, permanent: users never pick or see anyone's real name. */
final class Alias
{
    private const ADJECTIVES = ['Swift', 'Silent', 'Brave', 'Clever', 'Lucky', 'Rapid', 'Bold', 'Calm', 'Fierce', 'Noble', 'Sneaky', 'Mighty', 'Cosmic', 'Neon', 'Pixel', 'Turbo', 'Shadow', 'Golden', 'Iron', 'Frozen', 'Blazing', 'Stealth', 'Royal', 'Wild', 'Sharp', 'Rogue', 'Mystic', 'Atomic', 'Crimson', 'Electric'];
    private const NOUNS = ['Falcon', 'Panther', 'Wolf', 'Tiger', 'Dragon', 'Phoenix', 'Raven', 'Cobra', 'Fox', 'Lynx', 'Eagle', 'Shark', 'Viper', 'Bear', 'Hawk', 'Otter', 'Rhino', 'Jaguar', 'Badger', 'Gecko', 'Mantis', 'Orca', 'Yeti', 'Golem', 'Knight', 'Ninja', 'Pilot', 'Ranger', 'Wizard', 'Titan'];

    public static function generate(): string
    {
        for ($i = 0; $i < 30; $i++) {
            $alias = self::ADJECTIVES[random_int(0, count(self::ADJECTIVES) - 1)]
                . self::NOUNS[random_int(0, count(self::NOUNS) - 1)]
                . random_int(10, 99);
            if (!db()->fetchValue('SELECT 1 FROM users WHERE alias = ?', [$alias])) {
                return $alias;
            }
        }
        return 'Player' . strtoupper(bin2hex(random_bytes(4)));
    }

    /** Returns the user's alias, creating it on first use. */
    public static function ensure(int $userId): string
    {
        $current = db()->fetchValue('SELECT alias FROM users WHERE id = ?', [$userId]);
        if (is_string($current) && $current !== '') {
            return $current;
        }
        $alias = self::generate();
        db()->execute('UPDATE users SET alias = ? WHERE id = ? AND (alias IS NULL OR alias = \'\')', [$alias, $userId]);
        return (string) db()->fetchValue('SELECT alias FROM users WHERE id = ?', [$userId]);
    }
}
