<?php
declare(strict_types=1);

/**
 * Paginator — calcula offset/limit y renderiza enlaces "« 1 2 3 »".
 */
final class Paginator
{
    public int $page;
    public int $perPage;
    public int $total;
    public int $totalPages;
    public int $offset;

    public function __construct(int $total, int $perPage = 10, ?int $page = null)
    {
        $this->total      = max(0, $total);
        $this->perPage    = max(1, $perPage);
        $page             = $page ?? (int) ($_GET['page'] ?? 1);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page       = max(1, min($page, $this->totalPages));
        $this->offset     = ($this->page - 1) * $this->perPage;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset + 1;
    }

    public function to(): int
    {
        return min($this->offset + $this->perPage, $this->total);
    }

    /**
     * @param string                $baseUrl     URL absoluta sin querystring (ej. BASE_URL.'/producto/index')
     * @param array<string, scalar> $extraParams parámetros a preservar en cada enlace (filtros)
     */
    public function render(string $baseUrl, array $extraParams = []): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $link = function (int $page, string $label, bool $active = false, bool $disabled = false) use ($baseUrl, $extraParams): string {
            $params = array_merge($extraParams, ['page' => $page]);
            $href   = $baseUrl . '?' . http_build_query($params);
            $cls    = 'paginator__link'
                    . ($active ? ' is-active' : '')
                    . ($disabled ? ' is-disabled' : '');
            $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            return $disabled
                ? '<span class="' . $cls . '">' . $safeLabel . '</span>'
                : '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $safeLabel . '</a>';
        };

        $out  = '<nav class="paginator" aria-label="Paginación">';
        $out .= '<span class="paginator__info">'
              . $this->from() . '–' . $this->to() . ' de ' . $this->total
              . '</span>';
        $out .= '<div class="paginator__pages">';
        $out .= $link(max(1, $this->page - 1), '‹', false, $this->page === 1);

        // Ventana compacta de páginas: 1, …, current-2..current+2, …, last
        $window = 2;
        $start  = max(1, $this->page - $window);
        $end    = min($this->totalPages, $this->page + $window);

        if ($start > 1) {
            $out .= $link(1, '1');
            if ($start > 2) {
                $out .= '<span class="paginator__sep">…</span>';
            }
        }
        for ($p = $start; $p <= $end; $p++) {
            $out .= $link($p, (string) $p, $p === $this->page);
        }
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $out .= '<span class="paginator__sep">…</span>';
            }
            $out .= $link($this->totalPages, (string) $this->totalPages);
        }

        $out .= $link(min($this->totalPages, $this->page + 1), '›', false, $this->page === $this->totalPages);
        $out .= '</div></nav>';
        return $out;
    }
}
