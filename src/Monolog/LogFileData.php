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
        if (!file_exists($this->filePath)) {
            return 0;
        }

        clearstatcache(filename: $this->filePath);

        return filesize($this->filePath);
    }
}
