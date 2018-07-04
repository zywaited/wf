<?php

namespace Wf\Bean\Annotations;

/**
 * @Annotation(Type::SYSTEM_ANNOTATION)
 * @Target({Declared::PROPERTY})
 */
class Alias
{
	public $value;

	public function __construct(string $className)
	{
		$this->value = $className;
	}
}