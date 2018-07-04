<?php

namespace Wf\Bean\Parser;

use Wf\Bean\Collector\ControllerCollector;

class ControllerParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		ControllerCollector::collect(
			$objectDefinition->getClassName(),
			$annotationObject
		);
	}
}