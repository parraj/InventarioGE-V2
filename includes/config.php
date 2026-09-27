<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$path = realpath(__DIR__ . '/../');

if ($path && file_exists($path . '/.env')) {
    $dotenv = Dotenv::createImmutable($path);
    $dotenv->load();
} else {
    die('Error: archivo .env no encontrado');
}

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * DB
 */
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_NAME', env('DB_NAME'));

if (!DB_USER || !DB_NAME) {
    die('Error: configuración de base de datos incompleta');
}