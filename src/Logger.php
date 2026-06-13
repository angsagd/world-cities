<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class Logger
{
    public function __construct(private readonly string $file)
    {
    }

    public function error(string $endpoint, Throwable $exception): void
    {
        $directory = dirname($this->file);

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $message = sprintf(
            "[%s] endpoint=%s error=%s trace=%s%s",
            date('Y-m-d H:i:s'),
            $endpoint,
            $exception->getMessage(),
            $exception->getTraceAsString(),
            PHP_EOL,
        );

        @error_log($message, 3, $this->file);
    }
}
