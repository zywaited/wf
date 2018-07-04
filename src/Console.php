<?php

namespace Wf;

class Console
{
    public const WARNING = 'WARNING';
    public const INFO = 'INFO';
    public const ERROR = 'ERROR';

    public const DEBUG = [
        self::WARNING => 1,
        self::INFO => 1,
        self::ERROR => 1,
    ];

    public static function log(string $mode, string $logs): void
    {
        if (!isset(self::DEBUG[$mode])) {
            $mode = self::INFO;
        }

        echo '[' . date('Y-m-d H:i') . "] [{$mode}] {$logs}" . PHP_EOL;
    }
}
