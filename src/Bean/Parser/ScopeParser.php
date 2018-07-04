<?php

namespace Wf\Bean\Parser;

use Wf\Bean\Annotations\Single;

class ScopeParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$scope = $annotationObject->value == Single::PROTOTYPE ? Single::PROTOTYPE : Single::SINGLE;
		$objectDefinition->setScope($scope);
	}
}