<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

/** Reusable pager: build from a total count, use ->limitSql() in the query, echo ->render() in the view. */
final class Pagination
{
    public int $page;
    public int $pages;
    public int $total;
    public int $perPage;

    public function __construct(int $total, int $page, int $perPage = 25)
    {
        $this->total   = $total;
        $this->perPage = $perPage;
        $this->pages = max(1, (int) ceil($total / $perPage));
        $this->page  = min(max(1, $page), $this->pages);
    }

    public static function fromRequest(Request $request, int $total, int $perPage = 25): self
    {
        $page = Forms::int($request->query('page', 1)) ?? 1;
        return new self($total, $page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** Integers only, so it is safe to append to SQL (PDO can't bind LIMIT with native prepares). */
    public function limitSql(): string
    {
        return ' LIMIT ' . $this->perPage . ' OFFSET ' . $this->offset();
    }

    public function url(int $page): string
    {
        $query = $_GET;
        unset($query['page']);
        if ($page > 1) {
            $query['page'] = $page;
        }
        $qs = http_build_query($query);
        return url(request()->path()) . ($qs !== '' ? '?' . $qs : '');
    }

    public function render(): string
    {
        if ($this->pages <= 1) {
            return $this->total > 0 ? '<div class="pager"><span class="pager-info">' . $this->total . ' result' . ($this->total === 1 ? '' : 's') . '</span></div>' : '';
        }

        $from = $this->offset() + 1;
        $to   = min($this->total, $this->offset() + $this->perPage);
        $html = '<nav class="pager" aria-label="Pagination"><span class="pager-info">' . $from . '-' . $to . ' of ' . $this->total . '</span><div class="pager-links">';

        if ($this->page > 1) {
            $html .= '<a href="' . e($this->url($this->page - 1)) . '" rel="prev">&lsaquo; Prev</a>';
        }
        $last = 0;
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $this->page) <= 2) {
                if ($last && $i - $last > 1) {
                    $html .= '<span class="gap">&hellip;</span>';
                }
                $html .= $i === $this->page
                    ? '<span class="current" aria-current="page">' . $i . '</span>'
                    : '<a href="' . e($this->url($i)) . '">' . $i . '</a>';
                $last = $i;
            }
        }
        if ($this->page < $this->pages) {
            $html .= '<a href="' . e($this->url($this->page + 1)) . '" rel="next">Next &rsaquo;</a>';
        }
        return $html . '</div></nav>';
    }
}
