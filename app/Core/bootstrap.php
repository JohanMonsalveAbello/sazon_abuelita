<?php
declare(strict_types=1);

// Autocarga PSR-4 propia: App\Core\Router => app/Core/Router.php
spl_autoload_register(static function (string $clase): void {
    if (str_starts_with($clase, 'App\\')) {
        $ruta = BASE_PATH . '/app/' . str_replace('\\', '/', substr($clase, 4)) . '.php';
        if (is_file($ruta)) {
            require $ruta;
        }
    }
});

App\Core\Env::load(BASE_PATH . '/.env');

require BASE_PATH . '/app/Core/helpers.php';

// Los errores técnicos nunca llegan al usuario: se registran en storage/logs.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-errors.log');
error_reporting(E_ALL);
date_default_timezone_set((string) config('app.timezone'));

App\Core\ErrorHandler::register();
