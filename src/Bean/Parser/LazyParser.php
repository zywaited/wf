<?php

namespace Wf\Bean\Parser;

class LazyParser implements Base
{
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = '')
	{
		$objectDefinition->setLazy(true);
	}
}