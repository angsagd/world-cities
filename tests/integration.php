<?php

declare(strict_types=1);

use App\ApiApplication;
use App\Database;
use App\GeoRepository;
use App\Logger;

ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/GeoRepository.php';
require __DIR__ . '/../src/Logger.php';
require __DIR__ . '/../src/ApiApplication.php';

$databaseConfig = require __DIR__ . '/../config/database.php';
$application = new ApiApplication(
    new GeoRepository(new Database($databaseConfig)),
    new Logger(__DIR__ . '/../storage/logs/application.log'),
);

/**
 * @return list<array<string, mixed>>
 */
function request(ApiApplication $application, string $uri, string $method = 'GET'): array
{
    ob_start();
    $application->run([
        'REQUEST_METHOD' => $method,
        'REQUEST_URI' => $uri,
        'SCRIPT_NAME' => '/index.php',
    ]);
    $body = ob_get_clean();

    if (!is_string($body)) {
        throw new RuntimeException('Unable to read response body.');
    }

    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded) || !array_is_list($decoded)) {
        throw new RuntimeException("Response for {$method} {$uri} is not a JSON array.");
    }

    return $decoded;
}

/**
 * @param mixed $actual
 * @param mixed $expected
 */
function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(
            $message . sprintf(' Expected %s, got %s.', var_export($expected, true), var_export($actual, true)),
        );
    }
}

$regions = request($application, '/api/regions');
assertSameValue(6, count($regions), 'Region list is incorrect.');

$country = request($application, '/api/countries/105');
assertSameValue(1, count($country), 'Country detail must contain one row.');
assertSameValue(105, $country[0]['id'] ?? null, 'Country detail has an unexpected ID.');

$subregions = request($application, '/api/regions/3/subregions');
assertSameValue(5, count($subregions), 'Region relation is incorrect.');

$stateCities = request($application, '/api/states/1822/cities');
assertSameValue(true, count($stateCities) > 0, 'State cities relation should not be empty.');

$citySearch = request($application, '/api/cities/search/denp');
assertSameValue(true, count($citySearch) > 0, 'City contains search should return data.');
assertSameValue(true, count($citySearch) <= 100, 'Search result exceeds the limit.');

assertSameValue([], request($application, '/api/cities/search/den'), 'Short search must use exact matching.');
assertSameValue([], request($application, '/api/countries/abc'), 'Invalid ID must return an empty array.');
assertSameValue([], request($application, '/api/countries/0'), 'Zero ID must return an empty array.');
assertSameValue([], request($application, '/api/cities'), 'The deferred city list must return an empty array.');
assertSameValue([], request($application, '/api/not-supported'), 'Unknown route must return an empty array.');
assertSameValue([], request($application, '/api/regions', 'POST'), 'Unsupported method must return an empty array.');

echo "Integration tests passed.\n";
