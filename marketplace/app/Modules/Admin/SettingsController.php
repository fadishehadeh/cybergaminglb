<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\Pricing;
use App\Support\Settings;

final class SettingsController extends AdminController
{
    /** settings key => [label, item_condition value used by Pricing::conditionFactor()] */
    public const FACTORS = [
        'buyback_factor_new'      => ['New', 'New'],
        'buyback_factor_like_new' => ['Like New', 'Like New'],
        'buyback_factor_good'     => ['Good', 'Good'],
        'buyback_factor_fair'     => ['Fair', 'Fair'],
    ];

    public function index(Request $request): void
    {
        $values = [];
        foreach (['commission_pct', 'member_commission_pct', 'delivery_fee', 'free_delivery_over', 'buyback_pct', 'tradein_pct', 'swap_fee', 'whatsapp_number', 'site_name', 'tagline', 'contact_email', 'instagram_url', 'hub_address', ...array_keys(self::FACTORS)] as $key) {
            $values[$key] = (string) (Settings::all()[$key] ?? '');
        }

        // Worked example from the SAVED settings (the page also updates it live while typing).
        $example = ['price' => 20.0, 'rows' => []];
        foreach (self::FACTORS as $key => [$label, $condition]) {
            $example['rows'][] = [
                'label'  => $label,
                'factor' => Pricing::conditionFactor($condition),
                'cash'   => Pricing::buybackOffer(20.0, $condition),
                'credit' => Pricing::tradeCredit(20.0, $condition),
                'key'    => $key,
            ];
        }
        $zones = db()->fetchAll('SELECT id, name, fee, sort_order, is_active FROM delivery_zones ORDER BY sort_order, name');
        $this->view('settings/index', ['values' => $values, 'example' => $example, 'zones' => $zones, 'digital' => Digital::counts()]);
    }

    public function update(Request $request): void
    {
        $errors = [];
        $in     = [];

        foreach (['commission_pct' => 'Commission', 'member_commission_pct' => 'Member commission', 'buyback_pct' => 'Buy-back', 'tradein_pct' => 'Trade-in credit'] as $key => $label) {
            $v = Forms::decimal($request->input($key));
            if ($v === null || $v > 100) {
                $errors[] = $label . ' % must be a number between 0 and 100.';
                $v = 0.0;
            }
            $in[$key] = $v;
        }

        foreach (self::FACTORS as $key => [$label]) {
            $v = Forms::decimal($request->input($key));
            if ($v === null || $v > 100) {
                $errors[] = 'Buy-back factor for "' . $label . '" must be a number between 0 and 100.';
                $v = 0.0;
            }
            $in[$key] = $v;
        }

        foreach (['delivery_fee' => 'Default delivery fee', 'free_delivery_over' => 'Free delivery threshold'] as $key => $label) {
            $v = Forms::decimal($request->input($key));
            if ($v === null || $v > 9999.99) {
                $errors[] = $label . ' must be an amount of 0 or more' . ($key === 'free_delivery_over' ? ' (0 turns free delivery off).' : '.');
                $v = 0.0;
            }
            $in[$key] = $v;
        }

        $fee = Forms::decimal($request->input('swap_fee'));
        if ($fee === null) {
            $errors[] = 'Swap fee must be an amount of 0 or more.';
            $fee = 0.0;
        }
        $in['swap_fee'] = $fee;

        $wa = preg_replace('/[\s+\-().]/', '', $this->str($request, 'whatsapp_number')) ?? '';
        if ($wa === '961') {
            // placeholder kept until the real number is entered (the header shows a warning)
        } elseif (!preg_match('/^\d{8,15}$/', $wa)) {
            $errors[] = 'WhatsApp number must be 8-15 digits including the country code, e.g. 96170123456.';
        }
        $in['whatsapp_number'] = $wa;

        $siteName = $this->str($request, 'site_name');
        if ($siteName === '' || mb_strlen($siteName) > 100) {
            $errors[] = 'Site name is required (max 100 characters).';
        }
        $in['site_name'] = $siteName;

        $tagline = $this->str($request, 'tagline');
        if (mb_strlen($tagline) > 200) {
            $errors[] = 'Tagline is too long (max 200 characters).';
        }
        $in['tagline'] = $tagline;

        $email = $this->str($request, 'contact_email');
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
            $errors[] = 'Contact email is not a valid email address.';
        }
        $in['contact_email'] = $email;

        $ig = $this->str($request, 'instagram_url');
        if ($ig !== '' && (!preg_match('#^https://[^\s]+$#i', $ig) || strlen($ig) > 255)) {
            $errors[] = 'Instagram URL must start with https://.';
        }
        $in['instagram_url'] = $ig;

        $hub = $this->str($request, 'hub_address');
        if (mb_strlen($hub) > 255) {
            $errors[] = 'Hub address is too long (max 255 characters).';
        }
        $in['hub_address'] = $hub;

        if ($errors) {
            $this->invalid('/admin/settings', $errors, $request);
        }

        $oldCommission = (float) setting('commission_pct', 15);
        $oldMember     = (float) setting('member_commission_pct', 10);
        foreach ($in as $key => $value) {
            Settings::set($key, is_float($value) ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0' : (string) $value);
        }

        $msg = 'Settings saved.';
        $what = [];
        if (abs($oldCommission - $in['commission_pct']) > 0.0001) {
            $what[] = 'Commission changed from ' . Forms::pct($oldCommission) . ' to ' . Forms::pct($in['commission_pct']);
        }
        if (abs($oldMember - $in['member_commission_pct']) > 0.0001) {
            $what[] = 'Member commission changed from ' . Forms::pct($oldMember) . ' to ' . Forms::pct($in['member_commission_pct']);
        }
        if ($what) {
            $changed = Pricing::recalculateAll();
            $msg .= ' ' . implode('; ', $what) . ': ' . $changed . ' product price' . ($changed === 1 ? ' was' : 's were') . ' recalculated.';
        }
        $this->ok($msg);
        $this->redirect('/admin/settings');
    }

    /** Master switch for digital goods (gift cards, Steam gifts). OFF = invisible everywhere on the storefront. */
    public function digital(Request $request): void
    {
        $to = $this->str($request, 'digital_enabled') === '1' ? '1' : '0';
        Settings::set('digital_enabled', $to);
        $counts = Digital::counts();
        $this->ok($to === '1'
            ? 'Digital goods are now ON: ' . $counts['live'] . ' active digital product' . ($counts['live'] === 1 ? ' is' : 's are') . ' visible on the website.'
            : 'Digital goods are now OFF: gift cards and Steam gifts are hidden everywhere on the website.');
        $this->redirect('/admin/settings#digital');
    }

    /** One click: add the starter gift-card catalogue as hidden drafts (idempotent). */
    public function starter(Request $request): void
    {
        $result = Digital::createStarter();
        if ($result === null) {
            $this->fail('The "Gift Cards" category does not exist. Add it under Categories & platforms first.');
        } elseif ($result['created'] === 0) {
            $this->ok('Nothing new to add: all ' . $result['skipped'] . ' starter items already exist.');
        } else {
            $this->ok($result['created'] . ' starter product' . ($result['created'] === 1 ? '' : 's') . ' added as hidden drafts'
                . ($result['skipped'] > 0 ? ' (' . $result['skipped'] . ' already existed)' : '')
                . '. Edit the prices, then use Show on each one to publish it.');
        }
        $this->redirect('/admin/settings#digital');
    }

    public function recalculate(Request $request): void
    {
        $changed = Pricing::recalculateAll();
        $this->ok('Prices recalculated from the current commission settings: ' . $changed . ' product' . ($changed === 1 ? '' : 's') . ' changed.');
        $this->redirect('/admin/settings');
    }
}
