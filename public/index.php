<?php

// ============================================================
//  Punto di ingresso del programma
//  - carica le variabili d'ambiente dal file .env
//  - registra l'autolaoder 
//  - passa controllo al router
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/Database.php';

// carica le classi 
spl_autoload_register(function (string $class): void {
    $base = __DIR__ . '/../app/';
    $dirs = ['Controllers/', 'Services/', 'Repositories/', 'Models/'];

    foreach ($dirs as $dir) {
        $file = $base . $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../routes/router.php';

$router = new Router();

require_once __DIR__ . '/../routes/api.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri);
