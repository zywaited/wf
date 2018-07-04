<?php

namespace Wf\Bean\Annotations;

/**
 * 配置
 * @Annotation()
 * @Target({Declared::PROPERTY})
 */
class Value
{
	public $value;

	public function __construct(string $name)
	{
		$this->value = $name;
	}
}