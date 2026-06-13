<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private ?PDO $connection = null;

    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     socket: ?string,
     *     charset: string
     * } $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsnParts = [
            'dbname=' . $this->config['database'],
            'charset=' . $this->config['charset'],
        ];

        if ($this->config['socket'] !== null && $this->config['socket'] !== '') {
            $dsnParts[] = 'unix_socket=' . $this->config['socket'];
        } else {
            $dsnParts[] = 'host=' . $this->config['host'];
            $dsnParts[] = 'port=' . $this->config['port'];
        }

        $this->connection = new PDO(
            'mysql:' . implode(';', $dsnParts),
            $this->config['username'],
            $this->config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        );

        return $this->connection;
    }
}
