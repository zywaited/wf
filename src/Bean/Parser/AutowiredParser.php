<?php

namespace Wf\Bean\Parser;

use Wf\Core\RuntimeExceptions;

class AutowiredParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$className = $annotationObject->value;
		if (!class_exists($className) && !interface_exists($className)) {
			throw new RuntimeException(
				"Autowired, class or interface[{$className}] not exists on class[{$objectDefinition->getClassName()}]"
			);	
		}

		$typeObject->setRef(true);
		$typeObject->setValue($className);
	}
}