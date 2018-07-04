<?php

namespace Wf\Bean\Parser;

use Wf\Bean\Annotations\Declared;

interface Base
{
	/**
	 * 解析对应的注解
	 */
	function parse(
		$objectDefinition,
		$typeObject,
		$annotationObject,
		string $type,
		string $argName = ''
	);
}