<?php

namespace Wf\Bean\Annotations;

/**
 * 控制器
 * @Annotation()
 * @Target({Declared::TYPE})
 */
class Service
{
	public $value = '';

	public function __construct(string $beanName = '')
	{
		$this->value = $beanName;
	}
}