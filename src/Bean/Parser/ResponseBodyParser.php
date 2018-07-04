<?php

namespace Wf\Bean\Parser;

use Wf\Bean\Annotations\Declared;
use Wf\Bean\Collector\ControllerCollector;

class ResponseBodyParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		if (Declared::TYPE == $type) {
			ControllerCollector::collect(
				$objectDefinition->getClassName(),
				$annotationObject
			);
			return;
		}
		
		ControllerCollector::collect(
			$objectDefinition->getClassName(),
			$annotationObject,
			$typeObject,
			$typeObject->getMethodName()
		);
	}
}