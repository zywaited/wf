<?php

namespace Wf\Cli;

use Wf\Console;
use Wf\Mime;
use Wf\Core\Config;

class Project
{
    public const CONFIG = 'config';
    public const CONFIG_USER = 'config/user';
    public const CACHE_RUNTIME = 'cache';
    public const MODELS_GATEWAY = 'src/Models/Gateway';
    public const MODELS_SERVICE = 'src/Models/Service';
    public const MODELS_INFRA = 'src/Models/Infra';
    public const CONTROLLERS = 'src/Controllers';
    public const APP_PUBLIC = 'public';
    public const TREE = [
        self::CONFIG_USER,
        self::CACHE_RUNTIME,
        self::MODELS_GATEWAY,
        self::MODELS_SERVICE,
        self::MODELS_INFRA,
        self::CONTROLLERS,
        self::APP_PUBLIC,
    ];

    public const AUTOLOAD_NAMESPACES = 'autoload_namespaces';
    public const AUTOLOAD_FILES = 'autoload_files';
    public const AUTOLOAD_PATH = 'autoload_path';
    public const AUTOLOAD_CLASSMAP = 'autoload_classmap';

    public static function create(string $name): void
    {
        Console::log(Console::INFO, "start to create {$name}");
        foreach (self::TREE as $dir) {
            mkdir(PRO_PATH . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $dir, 0755, true);
            Console::log(Console::INFO, "create dir[{$dir}]");
        }

        self::createAutoLoadFile($name);
        self::createOtherFile($name);
    }

    protected static function createAutoLoadFile(string $name): void
    {
        $proPath = PRO_PATH . DIRECTORY_SEPARATOR . $name;
        $file = self::CONFIG . DIRECTORY_SEPARATOR . 
            self::AUTOLOAD_NAMESPACES . Mime::SEPARATOR . Mime::PHP;
        file_put_contents($proPath . DIRECTORY_SEPARATOR . $file, <<<NAMESPACES
<?php
return [
    ucfirst('$name') => APP_PATH . '/src',
];
NAMESPACES
        );
        Console::log(Console::INFO, "create file[$file]");
        $files = [
            self::CONFIG . DIRECTORY_SEPARATOR . self::AUTOLOAD_FILES,
            self::CONFIG . DIRECTORY_SEPARATOR . self::AUTOLOAD_CLASSMAP,
            self::CONFIG . DIRECTORY_SEPARATOR . self::AUTOLOAD_PATH,
        ];
        foreach ($files as $file) {
            file_put_contents(
                $proPath . DIRECTORY_SEPARATOR . $file . Mime::SEPARATOR . Mime::PHP,
                <<<NAMESPACES
<?php
return [
];
NAMESPACES
            );
            Console::log(Console::INFO, "create file[$file]");
        }
    }

    protected static function createOtherFile(string $name): void
    {
        $appConfigFile = self::CONFIG_USER . DIRECTORY_SEPARATOR .
            Config::CONFIG_NAME . Mime::SEPARATOR . Mime::INI;
        file_put_contents(
            PRO_PATH . DIRECTORY_SEPARATOR . $name .
            DIRECTORY_SEPARATOR . $appConfigFile,
            <<<CONFIG
<?php
return [
];
CONFIG
        );
        Console::log(Console::INFO, "create config[$appConfigFile]");
        file_put_contents(
            PRO_PATH . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR .
            self::APP_PUBLIC . DIRECTORY_SEPARATOR . 'index.php',
            <<<INDEXFILE
<?php
    /**
     * you can init some variable
     */
    if (!defined('PRO_PATH')) {
        throw new \Exception('must be started from wf');
    }
    
    define('APP_NAME', '$name');
    defined('APP_PATH') || define('APP_PATH', PRO_PATH . DIRECTORY_SEPARATOR . '$name');
    define('APP_SOURCE_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'src');
    define('APP_CONFIG_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'config');
    define('APP_USER_CONFIG_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'config/user');
    define('APP_CACHE_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'cache');
    defined('APP_ENV') ||
        define('APP_ENV', getenv('APP_ENV') ?: 'production');
INDEXFILE
        );
        Console::log(Console::INFO, 'create file[index.php]');
    }
}
