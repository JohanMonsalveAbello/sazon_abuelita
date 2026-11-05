<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Request;
use App\Models\AuditoriaModel;

/** Deja constancia de quién hizo qué y cuándo (RF-11). Jamás guarda secretos. */
final class AuditService
{
    public static function registrar(string $accion, string $entidad, ?int $entidadId = null, array $detalle = [], ?int $usuarioId = null): void
    {
        foreach (array_keys($detalle) as $clave) {
            if (preg_match('/pass|token|csrf|hash/i', (string) $clave)) {
                unset($detalle[$clave]);
            }
        }
        $json = $detalle === [] ? null : mb_substr((string) json_encode($detalle, JSON_UNESCAPED_UNICODE), 0, 1000);
        (new AuditoriaModel())->registrar($usuarioId ?? Auth::id(), $accion, $entidad, $entidadId, $json, Request::current()->ip());
    }
}
