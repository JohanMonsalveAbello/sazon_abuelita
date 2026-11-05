<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\BusinessRuleException;

/**
 * Guarda fotos de platos. No se confía en el nombre ni en el tipo enviados por el navegador:
 * el tipo real se lee del contenido y el nombre final es aleatorio.
 */
final class ImagenService
{
    private const TIPOS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    private const MAX_BYTES = 2_097_152; // 2 MB

    /** Devuelve la ruta relativa dentro de public/assets/img o null si no se envió archivo. */
    public function guardar(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            throw new BusinessRuleException('No se pudo recibir la imagen. Intenta de nuevo.');
        }
        if ($archivo['size'] > self::MAX_BYTES) {
            throw new BusinessRuleException('La imagen supera el máximo de 2 MB.');
        }
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false || !isset(self::TIPOS[$info[2]])) {
            throw new BusinessRuleException('La imagen debe ser JPG, PNG o WEBP.');
        }
        $nombre = 'platos/' . bin2hex(random_bytes(12)) . '.' . self::TIPOS[$info[2]];
        if (!move_uploaded_file($archivo['tmp_name'], BASE_PATH . '/public/assets/img/' . $nombre)) {
            throw new \RuntimeException('No se pudo mover la imagen subida.');
        }
        return $nombre;
    }
}
