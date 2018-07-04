<?php

namespace Wf\Cli;

use Wf\Core\Functions;

class ParseCmds
{
    public const CREATE = 'create';
    public const BUILD = 'build';
    public const RUN = 'run';
    public const HELP = 'help';

    public const LONG_CMD_PARAMS = [
        self::HELP => 'help',
    ];

    public const CMDS = [
        self::CREATE => 1,
        self::BUILD => 1,
        self::RUN => 1,
        self::HELP => 1,
    ];

    private static function help(): void
    {
        echo <<<HELP
the valid cmd:
    create project_name: 
        create a new project
    build project_name: 
        compile the project
    run project_name: 
        start the project
    --help: 
        read the help document
HELP;
    }

    private static function create(string $name)
    {
        Project::create($name);
    }

    public static function run(string $name)
    {
        // 加载对应配置
        Functions::import(
            PRO_PATH . DIRECTORY_SEPARATOR . $name  . DIRECTORY_SEPARATOR .
            'public' . DIRECTORY_SEPARATOR . 'index.php'
        );
        Functions::import(ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php');
    }

    public static function parse(): array
    {
        $longCmds = getopt('', array_values(self::LONG_CMD_PARAMS));
        if (!empty($longCmds)) {
            if (isset($longCmds[self::LONG_CMD_PARAMS[self::HELP]])) {
                return [self::HELP];
            }
        }

        $args = $_SERVER['argv'];
        if (!isset($args[1]) || !isset(self::CMDS[$args[1]])) {
            return [self::HELP];
        }

        switch ($args[1]) {
            case self::CREATE:
                if (empty($args[2])) {
                    return [self::HELP];
                }

                return [self::CREATE, $args[2]];
            case self::BUILD:
                if (empty($args[2])) {
                    return [self::HELP];
                }
                return [self::BUILD, $args[2]];
            case self::RUN:
                if (empty($args[2])) {
                    return [self::HELP];
                }

                return [self::RUN, $args[2]];
        }

        return [$args[1]];
    }

    public static function start(): void
    {
        list($cmd, $arg) = self::parse();
        if (!is_callable([__CLASS__, $cmd])) {
            $cmd = self::HELP;
        }

        if (!empty($arg)) {
            call_user_func([__CLASS__, $cmd], $arg);
        } else {
            call_user_func([__CLASS__, $cmd]);
        }
    }
}

