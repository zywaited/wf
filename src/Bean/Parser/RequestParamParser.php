<?php

namespace Wf\Bean\Parser;

class RequestParamParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$argInjector = $typeObject->getParamInjectorByName($argName);
		$argInjector->setAliasName($annotationObject->value);
	}
}