<?php

namespace Ensi\LaravelLogsHelper;

class LaravelStdoutLogsHelper
{
    public static array $ignoreEnvs = ['testing'];

    public static function addStdoutStacks(array $config): array
    {
        if (self::isIgnore()) {
            return $config;
        }

        $newChannels = [];
        foreach ($config['channels'] as $name => $channelSpec) {
            if ($channelSpec['stdout_mirror'] ?? false) {
                unset($channelSpec['stdout_mirror']);

                [$stdoutName, $originalName] = self::getNamesForStack($name);

                $newChannels[$originalName] = $channelSpec;

                $stdoutLevel = $channelSpec['stdout_level'] ?? $channelSpec['level'];
                $newChannels[$stdoutName] = LogsConfigMaker::stdout($stdoutLevel);

                $newChannels[$name] = LogsConfigMaker::stack($name, [$stdoutName, $originalName]);
            } else {
                $newChannels[$name] = $channelSpec;
            }
        }
        $config['channels'] = $newChannels;

        return $config;
    }

    public static function getNamesForStack(string $name): array
    {
        if (self::isIgnore()) {
            return [$name];
        }

        return ["{$name}:stdout", "{$name}:original"];
    }

    protected static function isIgnore(): bool
    {
        if (!function_exists('env')) {
            return false;
        }

        return in_array(env('APP_ENV', 'production'), self::$ignoreEnvs);
    }
}
