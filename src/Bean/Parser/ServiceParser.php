<?php

namespace Wf\Bean\Parser;

class ServiceParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$objectDefinition->setBeanName($annotationObject->value);
	}
}