<?php

use Ensi\LaravelLogsHelper\LaravelStdoutLogsHelper;
use Ensi\LaravelLogsHelper\LogsConfigMaker;
use Ensi\LaravelLogsHelper\Tests\Stubs\ConfigStub;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;

test('addStdoutStacks success', function () {
    $config = LaravelStdoutLogsHelper::addStdoutStacks(ConfigStub::original());

    assertArrayHasKey('first:stdout', $config['channels']);
    assertEquals('monolog', $config['channels']['first:stdout']['driver']);

    assertArrayHasKey('first:original', $config['channels']);
    assertEquals('single', $config['channels']['first:original']['driver']);

    assertArrayHasKey('first', $config['channels']);
    assertEquals('stack', $config['channels']['first']['driver']);

    assertArrayHasKey('second', $config['channels']);
    assertEquals('daily', $config['channels']['second']['driver']);

    assertArrayHasKey('other', $config['channels']);
    assertEquals('slack', $config['channels']['other']['driver']);
});

test('makeDailyChannel success', function () {
    $channelSpec = LogsConfigMaker::daily('/path/to/file.log');
    assertArrayHasKey('driver', $channelSpec);
    assertArrayHasKey('path', $channelSpec);
    assertArrayHasKey('level', $channelSpec);
    assertArrayHasKey('days', $channelSpec);
});

test('makeStdoutChannel success', function () {
    $channelSpec = LogsConfigMaker::stdout();
    assertArrayHasKey('driver', $channelSpec);
    assertArrayHasKey('handler', $channelSpec);
    assertArrayHasKey('level', $channelSpec);
    assertArrayHasKey('with', $channelSpec);
});

test('addStdoutStacks level success', function () {
    $makeChannels = function ($baseLevel, $stdoutLevel) {
        return LaravelStdoutLogsHelper::addStdoutStacks([
            'channels' => [
                'first' => LogsConfigMaker::daily('/path/to/file.log', 3, $baseLevel, $stdoutLevel),
            ],
        ]);
    };

    $config = $makeChannels('debug', null);
    assertEquals('debug', $config['channels']['first:original']['level']);
    assertEquals('debug', $config['channels']['first:stdout']['level']);

    $config = $makeChannels('debug', 'info');
    assertEquals('debug', $config['channels']['first:original']['level']);
    assertEquals('info', $config['channels']['first:stdout']['level']);
});
