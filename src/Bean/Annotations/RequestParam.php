<?php

namespace Wf\Bean\Annotations;

/**
 * 参数
 * @Annotation()
 * @Target({Declared::METHOD})
 * @Repeat()
 */
class RequestParam
{
	public $value;

	public function __construct(string $name)
	{
		$this->value = $name;
	}
}