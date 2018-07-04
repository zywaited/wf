<?php

namespace Wf\Bean\Annotations;

/**
 * 声明注解
 * @Annotation(Type::SYSTEM_ANNOTATION)
 * @Target({Declared::ANNOTATION})
 */
class Annotation
{
	public $value;

	public function __construct($type = Type::CUSTOMIZE_ANNOTATION)
	{
		$this->value = $type;
	}
}