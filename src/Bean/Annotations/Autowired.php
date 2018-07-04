<?php

namespace Wf\Bean\Annotations;

/**
 * 自动装载
 * @Annotation()
 * @Target({Declared::PROPERTY})
 */
class Autowired
{
	// class name
	public $value;

	public function __construct(string $className)
	{
		$this->value = $className;
	}
}