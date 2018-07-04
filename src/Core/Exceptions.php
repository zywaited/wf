<?php

namespace Wf\Core;

use Wf\Bean\Collector\ControllerCollector;

class Exceptions
{
	/**
	 * @var Exceptions
	 */
	private static $_instance;

	private $_maps = [];

	private function __construct()
	{

	}

	public static function instance(): Exceptions
	{
		if (!self::$_instance) {
			$instance = new self();
			self::$_instance = $instance;
			$instance->_parseEx();
		}

		return self::$_instance;
	}

	private function _parseEx()
	{
		$exMaps = ControllerCollector::getExceptions();
		// 待完善
	}

	public function default(\Exception $e)
	{
		RuntimeError::exception($e);
		return [
			'status_code' => -1,
			'status_msg' => $e->getMessage(),
		];
	}

	public function getExceptionHandler(\Exception $e)
	{
		if (isset($this->_maps[get_class($e)])) {
			return $this->_maps[get_class($e)];
		}

		return [];
	}
}