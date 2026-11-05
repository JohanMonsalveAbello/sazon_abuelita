<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/** Base de los modelos: toda consulta pasa por sentencias preparadas con parámetros enlazados. */
abstract class Model
{
    protected function db(): PDO
    {
        return Database::connection();
    }

    protected function ejecutar(string $sql, array $parametros = []): PDOStatement
    {
        $stmt = $this->db()->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_null($valor) => PDO::PARAM_NULL,
                is_bool($valor) => PDO::PARAM_BOOL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue(is_int($clave) ? $clave + 1 : $clave, $valor, $tipo);
        }
        $stmt->execute();
        return $stmt;
    }

    protected function todos(string $sql, array $parametros = []): array
    {
        return $this->ejecutar($sql, $parametros)->fetchAll();
    }

    protected function uno(string $sql, array $parametros = []): ?array
    {
        return $this->ejecutar($sql, $parametros)->fetch() ?: null;
    }

    protected function escalar(string $sql, array $parametros = []): mixed
    {
        return $this->ejecutar($sql, $parametros)->fetchColumn();
    }

    protected function insertar(string $sql, array $parametros = []): int
    {
        $this->ejecutar($sql, $parametros);
        return (int) $this->db()->lastInsertId();
    }

    /**
     * Las columnas de ORDER BY no se pueden enlazar como parámetros;
     * por eso solo se aceptan las de una lista blanca fija definida en cada modelo.
     *
     * @param array<string,string> $permitidas nombre público => columna SQL
     */
    protected function ordenSql(array $permitidas, string $sort, string $dir, string $porDefecto): string
    {
        if (!isset($permitidas[$sort])) {
            return $porDefecto;
        }
        return $permitidas[$sort] . (strtolower($dir) === 'desc' ? ' DESC' : ' ASC');
    }

    /** $select y $desde se separan para reutilizar filtros en el conteo total. */
    protected function paginar(string $select, string $desde, array $parametros, string $orden, int $pagina, ?int $porPagina = null): Paginator
    {
        $porPagina ??= (int) config('app.por_pagina');
        $total = (int) $this->escalar("SELECT COUNT(*) {$desde}", $parametros);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $sql = "SELECT {$select} {$desde} ORDER BY {$orden} LIMIT ? OFFSET ?";
        $items = $this->todos($sql, [...$parametros, $porPagina, ($pagina - 1) * $porPagina]);
        return new Paginator($items, $total, $pagina, $porPagina);
    }
}
