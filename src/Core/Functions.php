<?php

namespace Wf\Core;

class Functions
{
	public static function import(string $file): void
	{
		static $includeMap = [];
		$id = hash('sha256', $file);
		if (!isset($includeMap[$id])) {
			if (file_exists($file)) {
				$includeMap[$id] = 1;
				require $file;
			} else {
				throw new RuntimeException("Can't import file[$file]");
			}
		}
	}

	public static function load(
		string $file, 
		$serialize = false,
		$throws = true)
	{
		if (!file_exists($file)) {
			if ($throws) {
				throw new RuntimeException("Can't load file[$file]");
			}

			return null;
		}

		if (!$serialize) {
			return require $file;
		}

		return unserialize(file_get_contents($file));
	}

	public static function cache(string $file, $cache, $serialize = true): void
	{
		if (!$cache) {
			return;
		}

		// 写入到缓存中
		if (!$serialize) {
	        file_put_contents(
	            $file,
	            '<?php' . PHP_EOL
	            . 'return '
	            . var_export($cache, true) . PHP_EOL
	            . ';'
	        );

	        return;
	    }

	    file_put_contents($file, serialize($cache));
	}
}