<?php
declare(strict_types=1);

/**
 * Front controller: único punto de entrada PHP accesible desde el navegador.
 */
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false; // php -S: deja servir archivos estáticos reales.
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/bootstrap.php';

(new App\Core\Application())->run();
