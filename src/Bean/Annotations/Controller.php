<?php

namespace Wf\Bean\Annotations;

/**
 * 控制器
 * @Annotation()
 * @Target({Declared::TYPE; Declared::CUSTOMIZE_ANNOTATION})
 */
class Controller
{
	public $value = '';

	public function __construct(string $prefix = '')
	{
		$this->value = $prefix;
	}
}