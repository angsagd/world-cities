<?php

declare(strict_types=1);

use App\ApiApplication;
use App\Database;
use App\GeoRepository;
use App\Logger;

ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/GeoRepository.php';
require __DIR__ . '/src/Logger.php';
require __DIR__ . '/src/ApiApplication.php';

try {
    $databaseConfig = require __DIR__ . '/config/database.php';

    $application = new ApiApplication(
        new GeoRepository(new Database($databaseConfig)),
        new Logger(__DIR__ . '/storage/logs/application.log'),
    );

    $application->run($_SERVER);
} catch (Throwable $exception) {
    $logger = new Logger(__DIR__ . '/storage/logs/application.log');
    $logger->error((string) ($_SERVER['REQUEST_URI'] ?? '/bootstrap'), $exception);

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('X-Content-Type-Options: nosniff');

    echo '[]';
}
