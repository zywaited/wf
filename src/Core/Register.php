<?php

namespace Wf\Core;

class Register
{
	private static $_data = [];

	protected static function _isValid($name): bool
	{
		if (is_string($name)) {
			$name = trim($name);
			if (empty($name)) {
				return false;
			}
		}

		return is_numeric($name);
	}

	public static function set($name, $value, bool $force = true)
	{
		if (isset(self::$_data[$name])) {
			if (!self::$_data[$name]['modify']) {
				throw new RuntimeException('Register is exists name');
			}
		}

		if (is_null($value)) {
			throw new RuntimeException('value is null');
		}

		if (!self::_isValid($name)) {
			throw new RuntimeException('name is empty');
		}

		self::$_data[$name] = [
			'modify' => $force,
			'value' => $value
		];
	}

	public static function get($name, $default = null)
	{
		if (!self::_isValid($name)) {
			return $default;
		}

		return self::$_data[$name]['value'] ?? $default;
	}

	public static function remove($name)
	{
		if (!self::_isValid($name)) {
			return null;
		}

		if (!isset(self::$_data[$name])) {
			return null;
		}

		$data = self::$_data[$name];
		if (!$data['modify']) {
			throw new RuntimeException('Can\'t unset register name');
		}

		$value = $data['value'];
		unset(self::$_data[$name]);
		return $value;
	}
}