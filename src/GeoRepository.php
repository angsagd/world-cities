<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use PDO;

final class GeoRepository
{
    private const TABLES = [
        'regions',
        'subregions',
        'countries',
        'states',
        'cities',
    ];

    public function __construct(private readonly Database $database)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(string $table): array
    {
        $table = $this->allowedTable($table);
        $statement = $this->database->connection()->query(
            "SELECT * FROM `{$table}` ORDER BY `name` ASC",
        );

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findById(string $table, int $id): array
    {
        $table = $this->allowedTable($table);
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$table}` WHERE `id` = :id LIMIT 1",
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function related(string $table, string $foreignKey, int $id): array
    {
        $table = $this->allowedTable($table);
        $allowedForeignKeys = [
            'region_id',
            'subregion_id',
            'country_id',
            'state_id',
        ];

        if (!in_array($foreignKey, $allowedForeignKeys, true)) {
            throw new InvalidArgumentException('Unsupported foreign key.');
        }

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$table}` WHERE `{$foreignKey}` = :id ORDER BY `name` ASC",
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchByName(string $table, string $keyword): array
    {
        $table = $this->allowedTable($table);
        $exactMatch = mb_strlen($keyword, 'UTF-8') <= 3;
        $operator = $exactMatch ? '=' : 'LIKE';
        $value = $exactMatch ? $keyword : '%' . $keyword . '%';

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$table}` WHERE `name` {$operator} :keyword
             ORDER BY `name` ASC LIMIT 100",
        );
        $statement->bindValue(':keyword', $value, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function allowedTable(string $table): string
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Unsupported table.');
        }

        return $table;
    }
}
