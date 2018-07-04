<?php

namespace Wf\Core;

use Wf\Cli\Project;
use Wf\Mime;

class Loader
{
	private static $loaderMap = [];

	/**
	 * @var Loader
	 */
	private static $_instance;

	private function __construct()
	{

	}

	public static function instance(): Loader
	{
		if (!self::$_instance) {
			$instance = new self();
			self::$_instance = $instance;
			$instance->register();
			$instance->_addLoaderPath();
		}

		return self::$_instance;
	}

	public function loadClass(string $class): void
	{
		if (!empty(($file = $this->_findClassFile($class)))) {
			Functions::import($file);
		}
	}

	public function setPsr4(string $namespace, string $path, $pre = false): void
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		if (!$path) {
			return;
		}

		if (!$namespace) {
			$this->setPath($path, $pre);
			return;
		}

		if (!isset(Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace])) {
			Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace] = [];
		}

		if ($pre) {
			array_unshift(Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace], $path);
		} else {
			Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace][] = $path;
		}
	}

	public function setPath(string $path, $pre = false): void
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		if (!$path) {
			return;
		}

		if (!isset(Loader::$loaderMap[Project::AUTOLOAD_PATH])) {
			Loader::$loaderMap[Project::AUTOLOAD_PATH] = [];
		}

		if ($pre) {
			array_unshift(Loader::$loaderMap[Project::AUTOLOAD_PATH], $path);
		} else {
			Loader::$loaderMap[Project::AUTOLOAD_PATH][] = $path;
		}
	}

	public function setClassMap(string $class, string $file, $pre = false): void
	{
		if (!$class || !$file) {
			return;
		}

		if (!isset(Loader::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class])) {
			Loader::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class] = [];
		}

		if ($pre) {
			array_unshift(Loader::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class], $path);
		} else {
			Loader::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class][] = $path;
		}
	}

	public function register(\Closure $loader = null): void
	{
		spl_autoload_register($loader ?: [$this, 'loadClass'], true, false);
	}

	protected function _addLoaderPath(): void
	{
		$namespaces = Functions::load(
			APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
			Project::AUTOLOAD_NAMESPACES . Mime::SEPARATOR . Mime::PHP
		);
		foreach ($namespaces as $namespace => $path) {
			$this->setPsr4($namespace, $path);
		}

		$classMaps = Functions::load(
			APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
			Project::AUTOLOAD_CLASSMAP . Mime::SEPARATOR . Mime::PHP
		);
		foreach ($classMaps as $class => $file) {
			$this->setClassMap($class, $file);
		}

		$paths = Functions::load(
			APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
			Project::AUTOLOAD_PATH . Mime::SEPARATOR . Mime::PHP
		);
		foreach ($paths as $path) {
			$this->setPath($path);
		}

		$files = Functions::load(
			APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
			Project::AUTOLOAD_FILES . Mime::SEPARATOR . Mime::PHP
		);
		foreach ($files as $file) {
			if (!empty($file)) {
				Functions::import($file);
			}
		}
	}

	protected function _findClassFile(string $class): string
	{
		/**
		 * search rules
		 * example: Wf\Core\Function.php
		 * 1 Wf\Core\Function => path (classMap)
		 * 2 Wf\Core => path (full namespace)
		 * 3 Wf => path (first namespace)
		 * 4 paths => path
		 */
		if (!strpos($class, '\\')) {
			if (strpos($class, '_')) {
				$class = str_replace('_', '\\', $class);
			}
		}

		$maps = [];
		if (isset(Loader::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class])) {
			$maps = $self::$loaderMap[Project::AUTOLOAD_CLASSMAP][$class];
			foreach ($maps as $file) {
				if (file_exists($file)) {
					return $file;
				}
			}
		}
		
		$classArr = explode('\\', $class);
		$className = array_pop($classArr);
		$namespaceNum = count($classArr);
		if ($namespaceNum > 0) {
			$namespace = implode('\\', $classArr);
			if (isset(Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace])) {
				$maps = Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace];
				foreach ($maps as $path) {
					$file = $path . DIRECTORY_SEPARATOR . $className . Mime::SEPARATOR . Mime::PHP;
					if (file_exists($file)) {
						return $file;
					}
				}
			}

			if ($namespaceNum > 1) {
				$namespace = array_shift($classArr);
				if (isset(Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace])) {
					$maps = Loader::$loaderMap[Project::AUTOLOAD_NAMESPACES][$namespace];
					$className = implode(DIRECTORY_SEPARATOR, $classArr) .
						DIRECTORY_SEPARATOR . $className;
					foreach ($maps as $path) {
						$file = $path . DIRECTORY_SEPARATOR . $className . Mime::SEPARATOR . Mime::PHP;
						if (file_exists($file)) {
							return $file;
						}
					}
				}
			}
		}

		if (isset(Loader::$loaderMap[Project::AUTOLOAD_PATH])) {
			$className = implode(DIRECTORY_SEPARATOR, $classArr);
			$maps = Loader::$loaderMap[Project::AUTOLOAD_PATH];
			foreach ($maps as $path) {
				$file = $path . DIRECTORY_SEPARATOR . $className . Mime::SEPARATOR . Mime::PHP;
				if (file_exists($file)) {
					return $file;
				}
			}
		}

		return '';
	}
}