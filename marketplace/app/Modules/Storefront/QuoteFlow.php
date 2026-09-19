<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Phone;
use App\Support\ProductPhotos;

/**
 * Shared flow for the two "customer gives us games" pages:
 *   form  ->  POST quote (instant, itemised, cash AND credit)  ->  POST submit (needs an account)  ->  thanks/{code}
 * Sell = we pay cash or credit. Trade = the same, with a calculator against products from the shop.
 *
 * Anyone can get a quote. Sending the request needs a signed-in customer: guests are sent to the login page with
 * the quote kept in the session, and land back on it (GET /sell/quote) after signing in or registering.
 */
abstract class QuoteFlow extends Controller
{
    private const PENDING_TTL = 604800; // a saved quote is kept for 7 days

    /** 'sell' or 'trade' */
    abstract protected function mode(): string;

    private function isTrade(): bool
    {
        return $this->mode() === 'trade';
    }

    private function path(string $suffix = ''): string
    {
        return '/' . $this->mode() . $suffix;
    }

    private function kind(): string
    {
        return $this->isTrade() ? 'trade_in' : 'sell';
    }

    private function customer(): ?array
    {
        return auth()->hasRole('customer') ? auth()->user() : null;
    }

    // ---------- GET /sell , /trade ----------

    public function form(Request $request): void
    {
        $prefill = null;
        if ($this->isTrade() && old('want_title', []) === []) {
            $slug = $request->query('want');
            if (is_string($slug) && $slug !== '') {
                $prefill = db()->fetch(
                    'SELECT p.slug, p.title FROM products p WHERE p.slug = :s AND ' . Catalog::wantable(),
                    ['s' => mb_substr($slug, 0, 190)]
                );
            }
        }

        $this->render('site/' . $this->mode() . '/index', [
            'mode'       => $this->mode(),
            'platforms'  => Catalog::platforms(),
            'factors'    => Quoter::conditionFactors(),
            'titles'     => Quoter::referenceTitles(),
            'wantTitles' => $this->isTrade() ? Quoter::wantableTitles() : [],
            'rows'       => Quoter::formRows(),
            'wantRows'   => $this->isTrade() ? $this->formWantRows($prefill) : [],
            'prefill'    => $prefill,
            'saved'      => $this->pending() !== null,
            'customer'   => $this->customer(),
            'nav'        => $this->mode(),
            'crumbs'     => [['Home', '/'], [$this->isTrade() ? 'Trade in' : 'Sell your games', null]],
            'meta'       => $this->pageMeta(),
        ]);
    }

    // ---------- POST /sell/quote ----------

    public function quote(Request $request): void
    {
        [$rows, $wantRows, $errors] = $this->readLists($request);
        if ($errors) {
            $this->back($this->path(), implode(' ', $errors), Quoter::inputForBack($request));
        }
        $this->savePending($rows, $wantRows);
        $this->showQuote($rows, $wantRows, $this->defaultOptions(), []);
    }

    /** GET /sell/quote: the quote saved in the session (used when coming back after signing in). */
    public function resume(Request $request): void
    {
        $p = $this->pending();
        if ($p === null) {
            $this->redirect($this->path());
        }
        $this->showQuote($p['rows'], $p['wants'], $this->defaultOptions(), []);
    }

    /** POST /sell/edit: go back to the form with the list still filled in. */
    public function edit(Request $request): void
    {
        $this->back($this->path(), null, Quoter::inputForBack($request));
    }

    /** GET on a POST-only address (refresh, bookmark): send people to the form. */
    public function toForm(Request $request): void
    {
        $this->redirect($this->path());
    }

    // ---------- POST /sell/submit ----------

    public function submit(Request $request): void
    {
        // honeypot: real people never fill this hidden field
        if ((string) $request->input('website', '') !== '') {
            $this->redirect('/');
        }

        [$rows, $wantRows, $errors] = $this->readLists($request);
        if ($errors) {
            $this->back($this->path(), implode(' ', $errors), Quoter::inputForBack($request));
        }

        // Keep the quote either way, so a guest lands back on it after signing in.
        $this->savePending($rows, $wantRows);
        $user = $this->customer();
        if ($user === null) {
            $this->app->session()->flash('success', 'Sign in or create a free account to send your request. Your quote is saved and waiting for you.');
            $this->redirect('/account/login?next=' . $this->path('/quote'));
        }

        [$options, $errors] = Quoter::readOptions($request);
        $phone = Phone::normalize((string) ($user['phone'] ?? ''));
        if (!Phone::isValid($phone)) {
            $errors[] = 'Your account needs a valid phone / WhatsApp number so we can reach you. Please add it in your profile first.';
        }
        $ip = $request->ip();
        if (!$errors && Quoter::throttled('buyback', $ip, $phone)) {
            $errors[] = 'You have sent several requests in the last hour. Please wait a little, or message us on WhatsApp if it is urgent.';
        }
        if ($errors) {
            $this->showQuote($rows, $wantRows, $options, $errors);
            return;
        }

        // Photos are optional and only uploaded here, at the final submit (never with the quote step).
        // stageLoose() re-encodes every image (EXIF/GPS removed) and, on any error, has already deleted what it staged.
        $photoResult = ProductPhotos::stageLoose($request->file('photos'), Quoter::MAX_PHOTOS);
        if ($photoResult['errors']) {
            $this->showQuote($rows, $wantRows, $options, array_merge($photoResult['errors'], ['Please choose your photos again: your list and answers are kept.']));
            return;
        }
        $staged = $photoResult['staged'];

        // Every figure is recomputed here from platform + title + condition. Nothing price-like is read from the form.
        try {
            $given = Quoter::priceGames($rows, $this->isTrade() ? 'trade' : 'sell');
            $wanted = $this->isTrade() ? Quoter::priceWanted($wantRows) : null;
            $code = Quoter::saveRequest($this->kind(), ['phone' => $phone] + $user, $options, $given, $wanted, $staged);
        } catch (\Throwable $e) {
            ProductPhotos::discard($staged); // no orphan files when the request could not be saved
            throw $e;
        }
        Quoter::recordHit('buyback', $ip);
        $this->app->session()->remove($this->pendingKey());
        $this->redirect($this->path('/thanks/' . $code));
    }

    // ---------- GET /sell/thanks/{code} ----------

    public function thanks(Request $request, string $code): void
    {
        header('Cache-Control: no-store');
        $code = strtoupper($code);
        if (!preg_match('/^(BB|TI)-[A-Z0-9]{6}$/', $code)) {
            Response::abort(404);
        }
        $req = db()->fetch(
            'SELECT code, user_id, name, area, kind, items, offered_total, estimate_cash, estimate_credit, preferred_method, collection, wanted_items, photos, created_at
               FROM buyback_requests WHERE code = :c AND kind = :k',
            ['c' => $code, 'k' => $this->kind()]
        ) ?? Response::abort(404);
        // Requests sent from an account are private to that account.
        if ($req['user_id'] !== null && (int) $req['user_id'] !== (int) ($this->customer()['id'] ?? 0)) {
            Response::abort(404);
        }

        $items = json_decode((string) $req['items'], true);
        $items = is_array($items) ? $items : [];
        $photos = $req['photos'] !== null ? json_decode((string) $req['photos'], true) : null;
        $photoCount = is_array($photos) ? count($photos) : 0;
        $wanted = $req['wanted_items'] !== null ? json_decode((string) $req['wanted_items'], true) : null;
        $wanted = is_array($wanted) ? $wanted : [];
        $first = explode(' ', trim((string) $req['name']))[0];
        $cash = (float) $req['estimate_cash'];
        $credit = (float) $req['estimate_credit'];
        if ($req['user_id'] === null) { // requests sent as a guest before accounts existed
            $cash = $cash > 0 ? $cash : (float) $req['offered_total'];
            $credit = $credit > 0 ? $credit : (float) $req['offered_total'];
        }
        $method = (string) $req['preferred_method'];

        $wantedTotal = 0.0;
        foreach ($wanted as $w) {
            if (!empty($w['matched'])) {
                $wantedTotal += (float) $w['price'];
            }
        }
        $balance = $wantedTotal - $credit; // > 0 the customer pays, < 0 they keep credit

        $msg = "Hi CyberGaming, I just sent " . ($this->isTrade() ? 'a trade-in' : 'a sell') . " request {$req['code']}.\nGames:\n" . Quoter::summaryLines($items)
            . "\nEstimate: cash " . money($cash) . ' / credit ' . money($credit) . ' (I prefer ' . ($method === 'cash' ? 'cash' : 'store credit') . ')'
            . ($this->isTrade() && $wanted ? "\nI want:\n" . implode("\n", array_map(static fn (array $w): string => '- ' . $w['title'] . (!empty($w['matched']) ? ': ' . money($w['price']) : ': (to be checked)'), $wanted)) : '')
            . "\nName: " . $req['name'];

        $this->render('site/buyback/thanks', [
            'mode'        => $this->mode(),
            'req'         => $req,
            'items'       => $items,
            'photoCount'  => $photoCount,
            'wanted'      => $wanted,
            'first'       => $first,
            'cash'        => $cash,
            'credit'      => $credit,
            'method'      => $method,
            'wantedTotal' => $wantedTotal,
            'balance'     => $balance,
            'waLink'      => wa_link($msg),
            'nav'         => $this->mode(),
            'meta'        => ['title' => ($this->isTrade() ? 'Trade-in request ' : 'Sell request ') . $req['code'] . ' | CyberGaming', 'noindex' => true, 'description' => 'Your request has been received.'],
        ]);
    }

    // ---------- internals ----------

    /** @return array{0:array,1:array,2:string[]} game rows, wanted rows, errors */
    private function readLists(Request $request): array
    {
        [$rows, $errors] = Quoter::readGames($request);
        $wantRows = [];
        if ($this->isTrade()) {
            [$wantRows, $wantErrors] = Quoter::readWanted($request);
            $errors = array_merge($errors, $wantErrors);
        }
        return [$rows, $wantRows, $errors];
    }

    private function defaultOptions(): array
    {
        return ['method' => 'credit', 'collection' => 'dropoff', 'pickup_note' => '', 'note' => ''];
    }

    private function pendingKey(): string
    {
        return 'pending_quote_' . $this->mode();
    }

    private function savePending(array $rows, array $wantRows): void
    {
        $this->app->session()->put($this->pendingKey(), ['rows' => $rows, 'wants' => $wantRows, 'at' => time()]);
    }

    /** @return ?array{rows:array,wants:array} */
    private function pending(): ?array
    {
        $p = $this->app->session()->get($this->pendingKey());
        if (!is_array($p) || !isset($p['rows'], $p['wants'], $p['at']) || !is_array($p['rows']) || !is_array($p['wants']) || !$p['rows']
            || (int) $p['at'] < time() - self::PENDING_TTL) {
            return null;
        }
        // rows saved before the include checkboxes existed have no box/cover/manual keys: default to "not included"
        $p['rows'] = array_values(array_filter(array_map(static function ($row): ?array {
            if (!is_array($row) || !isset($row['platform'], $row['title'], $row['condition'])) {
                return null;
            }
            return [
                'platform'  => (string) $row['platform'],
                'title'     => (string) $row['title'],
                'condition' => (string) $row['condition'],
            ] + Quoter::includesOf($row);
        }, $p['rows'])));
        if (!$p['rows']) {
            return null;
        }
        return $p;
    }

    /** Render the itemised quote plus the "send my request" block. */
    private function showQuote(array $rows, array $wantRows, array $options, array $errors): void
    {
        header('Cache-Control: no-store');
        $given = Quoter::priceGames($rows, $this->isTrade() ? 'trade' : 'sell');
        $wanted = $this->isTrade() ? Quoter::priceWanted($wantRows) : null;

        $this->render('site/' . $this->mode() . '/quote', [
            'mode'     => $this->mode(),
            'given'    => $given,
            'wanted'   => $wanted,
            'balance'  => $wanted ? $wanted['total'] - $given['total_credit'] : 0.0,
            'options'  => $options,
            'errors'   => $errors,
            'customer' => $this->customer(),
            'loginUrl' => '/account/login?next=' . $this->path('/quote'),
            'registerUrl' => '/account/register?next=' . $this->path('/quote'),
            'nav'      => $this->mode(),
            'crumbs'   => [['Home', '/'], [$this->isTrade() ? 'Trade in' : 'Sell your games', $this->path()], ['Your quote', null]],
            'meta'     => [
                'title'       => ($this->isTrade() ? 'Your trade-in quote' : 'Your instant quote') . ' | CyberGaming',
                'description' => 'Your itemised quote.',
                'noindex'     => true,
            ],
        ]);
    }

    /** @return array<int,array{title:string,slug:string}> */
    private function formWantRows(?array $prefill): array
    {
        $titles = old('want_title', []);
        $slugs = old('want_slug', []);
        $rows = [];
        for ($i = 0; $i < Quoter::MAX_WANT; $i++) {
            $rows[] = [
                'title' => is_array($titles) && is_string($titles[$i] ?? null) ? $titles[$i] : '',
                'slug'  => is_array($slugs) && is_string($slugs[$i] ?? null) ? $slugs[$i] : '',
            ];
        }
        if ($prefill) {
            $rows[0] = ['title' => (string) $prefill['title'], 'slug' => (string) $prefill['slug']];
        }
        return $rows;
    }

    private function pageMeta(): array
    {
        $crumbs = [['Home', '/'], [$this->isTrade() ? 'Trade in' : 'Sell your games', null]];
        if ($this->isTrade()) {
            $title = 'Trade In Games for Store Credit in Lebanon | CyberGaming';
            $desc = 'Trade in your used PS4, PS5, Switch and Xbox games for CyberGaming store credit worth more than cash. Instant quote, then spend it on anything in the shop.';
        } else {
            $title = 'Sell Your Used Games in Lebanon: Instant Cash or Credit Quote | CyberGaming';
            $desc = 'Sell used PS4, PS5, Switch and Xbox games in Lebanon. Get an instant quote in cash or higher-value store credit, then hand them over after a quick inspection.';
        }
        return [
            'title'       => $title,
            'description' => Seo::clip($desc),
            'canonical'   => url($this->path()),
            'jsonld'      => [Seo::breadcrumbs($crumbs)],
        ];
    }
}
