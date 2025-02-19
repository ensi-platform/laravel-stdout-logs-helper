<?php

namespace App\Domain\Common;

use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Utils;

class DateSizeRotatingFileHandler extends StreamHandler
{
    // Supported date formats
    public const FILE_PER_DAY = 'Y-m-d';
    public const FILE_PER_MONTH = 'Y-m';
    public const FILE_PER_YEAR = 'Y';

    // Filename format
    public const FILENAME_FORMAT = '{filename}-{date}-{size}';

    // Storage of all channels with the current handler
    protected static array $allRotatingHandlers = [];
    protected static bool $allHandlersLoaded = false;

    protected string $defaultFilepath; // The original path to the file
    protected LogFileData $logFile; // Data about the current log file
    protected bool|null $mustRotate = null; // true if you need to start writing logs to a new file.
    protected \DateTimeImmutable $nextDateRotation; // Date for the next rotation
    protected string $dateFormat;
    protected string $fileNameRegex;

    public function __construct(
        string $filename,
        protected ?int $oneFileSizeLimitBytes = null,
        protected ?int $channelSizeLimitBytes = null,
        string $dateFormat = self::FILE_PER_DAY,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false,
    ) {
        self::$allRotatingHandlers[$filename] = $this;

        if (is_null($this->oneFileSizeLimitBytes)) {
            $this->oneFileSizeLimitBytes = config('laravel-logs-helper.rotation_size.one_file_size_limit_bytes', 0);
        }
        if (is_null($this->channelSizeLimitBytes)) {
            $this->channelSizeLimitBytes = config('laravel-logs-helper.rotation_size.channel_size_limit_bytes', 0);
        }

        $this->defaultFilepath = Utils::canonicalizePath($filename);
        $this->setDateFormat($dateFormat);
        $this->setFileNameRegex();
        $this->setActualFileInfo();
        $this->close();

        parent::__construct($this->url, $level, $bubble, $filePermission, $useLocking);

    }

    public function close(): void
    {
        parent::close();

        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }

    protected function write(LogRecord $record): void
    {
        // on the first record written, if the log is new, we rotate (once per day) after the log has been written so that the new file exists
        if (null === $this->mustRotate) {
            $this->mustRotate = null === $this->url || !file_exists($this->url);
        }

        // if the next rotation is expired or size is big, then we rotate immediately
        $isDatetimeRotten = $this->nextDateRotation <= $record->datetime;
        $isSizeRotten = $this->isSizeRotten($this->logFile);
        if ($isDatetimeRotten || $isSizeRotten) {
            $this->mustRotate = true;
            $this->close(); // triggers rotation
        }

        parent::write($record);
    }

    protected function rotate(): void
    {
        $this->mustRotate = false;
        $this->setActualFileInfo();

        if ($this->channelSizeLimitBytes) {
            static::rotateFiles($this->findAllLogFiles(), $this->channelSizeLimitBytes);
        }

        static::rotateAllFiles();
    }

    protected static function rotateFiles(array $files, int $maxSumSize): void
    {
        if (!$files) {
            return;
        }

        $totalSize = 0;
        foreach ($files as $logFile) {
            $totalSize += $logFile->getSizeFile();
        }

        if ($maxSumSize >= $totalSize) {
            // no files to remove
            return;
        }

        static::sortFiles($files);

        do {
            /** @var LogFileData $file */
            $file = array_shift($files);
            $totalSize -= $file->getSizeFile();
            $filePath = $file->filePath;

            if (!file_exists($filePath)) {
                // if file already deleted by other process, skip current process
                return;
            }

            if (is_writable($filePath)) {
                // suppress errors here as unlink() might fail if two processes are cleaning up/rotating at the same time
                @unlink($filePath);
            }

        } while ($totalSize > $maxSumSize);
    }

    protected function rotateAllFiles(): void
    {
        $totalLimit = config('laravel-logs-helper.rotation_size.total_size_limit_bytes', 0);
        if (!$totalLimit) {
            return;
        }

        static::rotateFiles($this->getGlobalFilesList(), $totalLimit);
    }

    protected static function sortFiles(array &$files): array
    {
        usort($files, function (LogFileData $a, LogFileData $b) {
            $dateCpm = strcmp($a->date, $b->date);
            if ($dateCpm != 0) {
                return $dateCpm;
            }

            $iCpm = $a->i <=> $b->i;
            if ($iCpm != 0) {
                return $iCpm;
            }

            return strcmp($a->prefixName, $b->prefixName);
        });

        return $files;
    }

    /**
     * @param bool $onlyCurrentDate
     * @return LogFileData[]
     */
    public function findAllLogFiles(bool $onlyCurrentDate = false): array
    {
        $globPattern = $this->getGlobPattern($onlyCurrentDate);
        $files = [];
        foreach (glob($globPattern) ?: [] as $filePath) {
            $files[] = $this->generateLogFile($filePath);
        }

        return $files;
    }

    protected function generateLogFile(string $filePath): LogFileData
    {
        $matches = [];
        preg_match("/^{$this->fileNameRegex}$/", pathinfo($filePath, PATHINFO_BASENAME), $matches);

        return new LogFileData(
            $filePath,
            $matches['prefix'],
            $matches['date'],
            (int)$matches['i'],
        );
    }

    protected function isSizeRotten(LogFileData $file): bool
    {
        if (!$this->oneFileSizeLimitBytes) {
            return false;
        }

        return $this->oneFileSizeLimitBytes <= $file->getSizeFile();
    }

    protected function setActualFileInfo(): void
    {
        $files = $this->findAllLogFiles(true);
        $this->sortFiles($files);
        /** @var LogFileData $file */
        $file = end($files);
        if (!$file) {
            $sizeI = 1;
        } else {
            $sizeI = $file->i;
            if ($this->isSizeRotten($file)) {
                $sizeI++;
            }
        }

        $fileInfo = pathinfo($this->defaultFilepath);
        $resultFilename = str_replace(
            ['{filename}', '{date}', '{size}'],
            [$fileInfo['filename'], date($this->dateFormat), $sizeI],
            ($fileInfo['dirname'] ?? '') . '/' . static::FILENAME_FORMAT
        );

        if (isset($fileInfo['extension'])) {
            $resultFilename .= '.' . $fileInfo['extension'];
        }

        $this->url = $resultFilename;
        $this->logFile = $this->generateLogFile($this->url);

        $this->nextDateRotation = match (str_replace(['/', '_', '.'], '-', $this->dateFormat)) {
            self::FILE_PER_MONTH => (new \DateTimeImmutable('first day of next month'))->setTime(0, 0),
            self::FILE_PER_YEAR => (new \DateTimeImmutable('first day of January next year'))->setTime(0, 0),
            default => (new \DateTimeImmutable('tomorrow'))->setTime(0, 0),
        };
    }

    protected function getGlobPattern($onlyCurrentDate = false): string
    {
        $fileInfo = pathinfo($this->defaultFilepath);
        $datePattern = $onlyCurrentDate ?
            date($this->dateFormat) :
            str_replace(['Y', 'y', 'm', 'd'], ['[0-9][0-9][0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]'], $this->dateFormat);

        $glob = str_replace(
            ['{filename}', '{date}', '{size}'],
            [$fileInfo['filename'], $datePattern, '[0-9]*'],
            ($fileInfo['dirname'] ?? '') . '/' . static::FILENAME_FORMAT
        );
        if (isset($fileInfo['extension'])) {
            $glob .= '.' . $fileInfo['extension'];
        }

        return $glob;
    }

    protected function setDateFormat(string $dateFormat): void
    {
        if (0 === preg_match('{^[Yy](([/_.-]?m)([/_.-]?d)?)?$}', $dateFormat)) {
            throw new InvalidArgumentException(
                'Invalid date format - format must be one of ' .
                'RotatingFileHandler::FILE_PER_DAY ("Y-m-d"), RotatingFileHandler::FILE_PER_MONTH ("Y-m") ' .
                'or RotatingFileHandler::FILE_PER_YEAR ("Y"), or you can set one of the ' .
                'date formats using slashes, underscores and/or dots instead of dashes.'
            );
        }
        $this->dateFormat = $dateFormat;
    }

    protected function setFileNameRegex(): void
    {
        $fileInfo = pathinfo($this->defaultFilepath);
        $datePattern = str_replace(
            ['Y', 'y', 'm', 'd'],
            ['[0-9]{4}', '[0-9]{2}', '[0-9]{2}', '[0-9]{2}'],
            $this->dateFormat
        );

        $regexFileName = preg_quote($fileInfo['filename']);
        $regex = str_replace(
            ['{filename}', '{date}', '{size}'],
            ["(?<prefix>{$regexFileName})", "(?<date>{$datePattern})", '(?<i>[0-9]+)'],
            static::FILENAME_FORMAT
        );
        if (isset($fileInfo['extension'])) {
            $regex .= '.' . $fileInfo['extension'];
        }

        $this->fileNameRegex = $regex;
    }

    /**
     * @return self[]
     */
    protected function getAllRotatedHandlers(): array
    {
        if (!self::$allHandlersLoaded) {
            $this->loadAllRotatedLogChannels();
        }

        return self::$allRotatingHandlers;
    }

    protected function loadAllRotatedLogChannels(): void
    {
        $logManager = logger();
        foreach (config('logging.channels') as $channelName => $channelConfig) {
            $channelDriver = $channelConfig['driver'] ?? null;
            $channelHandler = $channelConfig['handler'] ?? null;
            if ($channelDriver == 'monolog' && $channelHandler == self::class) {
                $logManager->channel($channelName); // trigger __constructor
            }
        }

        self::$allHandlersLoaded = true;
    }

    protected function getGlobalFilesList(): array
    {
        $allFiles = [];
        foreach ($this->getAllRotatedHandlers() as $rotatingHandler) {
            $allFiles = array_merge($allFiles, $rotatingHandler->findAllLogFiles());
        }

        return $allFiles;
    }
}
