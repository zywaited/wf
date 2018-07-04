<?php

namespace Wf\Bean\Annotations;

/**
 * 多例
 * @Annotation()
 * @Target({Declared::TYPE})
 */
class Scope
{
	public $value;

	public function __construct(string $scope = Single::PROTOTYPE)
	{
		$this->value = $scope;
	}
}