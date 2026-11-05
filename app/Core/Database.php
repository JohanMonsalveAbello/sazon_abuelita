<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/** Conexión PDO única. Prepares nativos: los parámetros nunca se mezclan con el SQL. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                config('database.host'),
                config('database.port'),
                config('database.name')
            );
            self::$pdo = new PDO($dsn, (string) config('database.user'), (string) config('database.pass'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            // MySQL y PHP deben coincidir en la hora para comparar fechas de reserva.
            self::$pdo->prepare('SET time_zone = ?')->execute([date('P')]);
        }
        return self::$pdo;
    }

    /** Ejecuta $trabajo en una transacción: todo se guarda o nada se guarda (RNF-07). */
    public static function transaction(callable $trabajo): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $resultado = $trabajo();
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
