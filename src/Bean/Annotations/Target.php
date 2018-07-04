<?php

namespace Wf\Bean\Annotations;

/**
 * @Annotation(Type::SYSTEM_ANNOTATION)
 * @Target({Declared::ANNOTATION})
 */
class Target
{
	public $value = Declared::UNDEFINED;

	public function __construct(array $types = [])
	{
		foreach ($types as $type) {
			$this->value |= $type;
		}
	}
}