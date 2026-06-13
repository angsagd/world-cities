<?php

declare(strict_types=1);

namespace App;

use JsonException;
use Throwable;

final class ApiApplication
{
    public function __construct(
        private readonly GeoRepository $repository,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $server
     */
    public function run(array $server): void
    {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = $this->requestPath($requestUri, (string) ($server['SCRIPT_NAME'] ?? ''));

        $this->sendHeaders($this->isSearchPath($path) ? 3600 : 86400);

        if ($method === 'OPTIONS') {
            $this->send([]);
            return;
        }

        if ($method !== 'GET') {
            $this->send([]);
            return;
        }

        try {
            $this->send($this->dispatch($path));
        } catch (Throwable $exception) {
            $this->logger->error($path, $exception);
            $this->send([]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dispatch(string $path): array
    {
        $segments = $this->segments($path);

        if ($segments === ['api', 'regions']) {
            return $this->repository->all('regions');
        }

        if ($segments === ['api', 'subregions']) {
            return $this->repository->all('subregions');
        }

        if ($segments === ['api', 'countries']) {
            return $this->repository->all('countries');
        }

        if ($segments === ['api', 'states']) {
            return $this->repository->all('states');
        }

        if ($this->matches($segments, ['api', 'countries', 'search', '*'])) {
            return $this->search('countries', $segments[3]);
        }

        if ($this->matches($segments, ['api', 'states', 'search', '*'])) {
            return $this->search('states', $segments[3]);
        }

        if ($this->matches($segments, ['api', 'cities', 'search', '*'])) {
            return $this->search('cities', $segments[3]);
        }

        if ($this->matches($segments, ['api', 'regions', '*', 'subregions'])) {
            return $this->related('subregions', 'region_id', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'subregions', '*', 'countries'])) {
            return $this->related('countries', 'subregion_id', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'countries', '*', 'states'])) {
            return $this->related('states', 'country_id', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'states', '*', 'cities'])) {
            return $this->related('cities', 'state_id', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'regions', '*'])) {
            return $this->find('regions', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'subregions', '*'])) {
            return $this->find('subregions', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'countries', '*'])) {
            return $this->find('countries', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'states', '*'])) {
            return $this->find('states', $segments[2]);
        }

        if ($this->matches($segments, ['api', 'cities', '*'])) {
            return $this->find('cities', $segments[2]);
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function find(string $table, string $rawId): array
    {
        $id = $this->positiveInteger($rawId);

        return $id === null ? [] : $this->repository->findById($table, $id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function related(string $table, string $foreignKey, string $rawId): array
    {
        $id = $this->positiveInteger($rawId);

        return $id === null ? [] : $this->repository->related($table, $foreignKey, $id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function search(string $table, string $rawKeyword): array
    {
        $keyword = trim(rawurldecode($rawKeyword));

        if ($keyword === '' || !mb_check_encoding($keyword, 'UTF-8')) {
            return [];
        }

        if ($table === 'cities') {
            return $this->repository->searchCitiesWithRelations($keyword);
        }

        return $this->repository->searchByName($table, $keyword);
    }

    private function positiveInteger(string $value): ?int
    {
        if (preg_match('/^[1-9][0-9]*$/D', $value) !== 1) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $id === false ? null : $id;
    }

    /**
     * @param list<string> $segments
     * @param list<string> $pattern
     */
    private function matches(array $segments, array $pattern): bool
    {
        if (count($segments) !== count($pattern)) {
            return false;
        }

        foreach ($pattern as $index => $expected) {
            if ($expected !== '*' && $segments[$index] !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function segments(string $path): array
    {
        $trimmed = trim($path, '/');

        return $trimmed === '' ? [] : explode('/', $trimmed);
    }

    private function requestPath(string $requestUri, string $scriptName): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        if (str_ends_with($scriptName, '.php')) {
            $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

            if ($basePath !== '' && $basePath !== '.' && str_starts_with($path, $basePath . '/')) {
                $path = substr($path, strlen($basePath));
            }
        }

        if ($path === '/index.php') {
            return '/';
        }

        if (str_starts_with($path, '/index.php/')) {
            return substr($path, strlen('/index.php'));
        }

        return $path;
    }

    private function isSearchPath(string $path): bool
    {
        return str_contains($path, '/search/');
    }

    private function sendHeaders(int $maxAge): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400');
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * @param list<array<string, mixed>> $data
     */
    private function send(array $data): void
    {
        try {
            echo json_encode(
                array_values($data),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            $this->logger->error('json-encoding', $exception);
            echo '[]';
        }
    }
}
