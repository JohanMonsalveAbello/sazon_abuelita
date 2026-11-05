<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public function __construct(
        public array $items,
        public int $total,
        public int $pagina,
        public int $porPagina
    ) {
    }

    public function paginas(): int
    {
        return max(1, (int) ceil($this->total / $this->porPagina));
    }

    public function desde(): int
    {
        return $this->total === 0 ? 0 : ($this->pagina - 1) * $this->porPagina + 1;
    }

    public function hasta(): int
    {
        return min($this->total, $this->pagina * $this->porPagina);
    }
}
