<?php

namespace Wf\Core;

use Wf\Console;
use Wf\Cli\Project;

class RuntimeError
{
	private static $_exceptionsMaps = [];

	public static function addExceptionMap($className, \Cloure $func)
	{
		if (!isset(self::$_exceptionsMaps[$className])) {
			self::$_exceptionsMaps[$className] = [];
		}

		self::$_exceptionsMaps[$className][] = $func;
	}

	public static function removeExceptionMap(string $className)
	{
		unset(self::$_exceptionsMaps[$className]);
	}

	public static function getExceptionMaps(string $className = null): array
	{
		if (!$className) {
			return self::$_exceptionsMaps;
		}

		return self::$_exceptionsMaps[$className] ?? [];
	}

	public static function register(): void
	{
		error_reporting(E_ALL);
		set_error_handler([__CLASS__, 'error']);
		// swoole不支持该方式捕获异常
		set_exception_handler([__CLASS__, 'exception']);
		register_shutdown_function([__CLASS__, 'shutdown']);
	}

	public static function exception($ex): void
	{
		$message = $ex->getMessage();
		$code = $ex->getCode();
		$file = $ex->getFile();
		$line = $ex->getLine();
		Console::log(Console::ERROR, "File[$file] have a message[$message], code[$code], line[$line]");
		$className = get_class($ex);
		if (isset(self::$_exceptionsMaps[$className])) {
			foreach (self::$_exceptionsMaps[$className] as $func) {
				call_user_func($func, $ex);
			}
		}
	}

	public static function error(
		int $errno,
		string $errstr,
		string $errfile = '',
		int $errline = -1,
		array $errcontext = []): void
	{
		Console::log(Console::ERROR, "File[$errfile] have a message[$errstr], code[$errno], line[$errline]");
	}

	public static function shutdown(): void
	{
		Console::log(Console::WARNING, 'The server app is shutdowm[' . APP_NAME . ']');
	}
}