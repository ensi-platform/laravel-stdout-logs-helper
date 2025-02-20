<?php

namespace Ensi\LaravelLogsHelper\Monolog;

class LogFileData
{
    public function __construct(
        public string $filePath,
        public string $prefixName,
        public string $date,
        public int $i,
    ) {
    }

    public function getSizeFile(): int
    {
        return file_exists($this->filePath) ? filesize($this->filePath) : 0;
    }
}
