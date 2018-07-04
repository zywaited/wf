<?php

namespace Wf\Bean\Parser;

use Wf\Bean\Collector\ControllerCollector;

class RequestMappingParser implements Base
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
			$annotationObject,
			$typeObject,
			$typeObject->getMethodName()
		);
	}
}