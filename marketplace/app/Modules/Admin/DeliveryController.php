<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Core\Response;

/** Delivery zones editor (the list itself is shown on the Settings page). Zones are never deleted, only deactivated. */
final class DeliveryController extends AdminController
{
    private const BACK = '/admin/settings#zones';

    public function index(Request $request): void
    {
        $this->redirect(self::BACK);
    }

    public function store(Request $request): void
    {
        [$errors, $data] = $this->validate($request, null);
        if ($errors) {
            $this->back(self::BACK, "Zone not added:\n" . implode("\n", $errors), ['new_zone_name' => $data['name'], 'new_zone_fee' => $request->input('fee'), 'new_zone_sort' => $request->input('sort_order'), '_form' => '1']);
        }
        db()->execute(
            'INSERT INTO delivery_zones (name, fee, sort_order, is_active) VALUES (?, ?, ?, ?)',
            [$data['name'], $data['fee'], $data['sort_order'], $data['is_active']]
        );
        $this->ok('Zone "' . $data['name'] . '" added with a ' . money($data['fee']) . ' delivery fee.');
        $this->redirect(self::BACK);
    }

    public function update(Request $request, string $id): void
    {
        $zone = db()->fetch('SELECT * FROM delivery_zones WHERE id = ?', [$this->id($id)]);
        if (!$zone) {
            Response::abort(404);
        }
        [$errors, $data] = $this->validate($request, (int) $zone['id']);
        if ($errors) {
            $this->back(self::BACK, 'Zone "' . $zone['name'] . "\" not saved:\n" . implode("\n", $errors));
        }
        db()->execute(
            'UPDATE delivery_zones SET name = ?, fee = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$data['name'], $data['fee'], $data['sort_order'], $data['is_active'], $zone['id']]
        );
        $msg = 'Zone "' . $data['name'] . '" saved.';
        if (!$data['is_active'] && (int) $zone['is_active'] === 1) {
            $msg .= ' It is now hidden from customers; orders to it would use the default delivery fee.';
        }
        $this->ok($msg);
        $this->redirect(self::BACK);
    }

    /** @return array{0: string[], 1: array{name:string,fee:float,sort_order:int,is_active:int}} */
    private function validate(Request $request, ?int $ignoreId): array
    {
        $errors = [];
        $name = $this->str($request, 'name');
        if ($name === '' || mb_strlen($name) > 80) {
            $errors[] = 'Zone name is required (max 80 characters).';
        } elseif ((int) db()->fetchValue('SELECT COUNT(*) FROM delivery_zones WHERE name = ? AND id <> ?', [$name, $ignoreId ?? 0]) > 0) {
            $errors[] = 'A zone called "' . $name . '" already exists.';
        }

        $fee = Forms::decimal($request->input('fee'));
        if ($fee === null || $fee > 9999.99) {
            $errors[] = 'Fee must be an amount of 0 or more, e.g. 4 or 4.50 (0 means free delivery).';
            $fee = 0.0;
        }

        $sortIn = $this->str($request, 'sort_order');
        $sort   = $sortIn === '' ? 0 : Forms::int($sortIn);
        if ($sort === null || $sort < 0 || $sort > 9999) {
            $errors[] = 'Sort order must be a whole number from 0 to 9999.';
            $sort = 0;
        }

        return [$errors, [
            'name'       => $name,
            'fee'        => $fee,
            'sort_order' => $sort,
            'is_active'  => $request->input('is_active') ? 1 : 0,
        ]];
    }
}
