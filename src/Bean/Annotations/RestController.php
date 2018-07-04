<?php

namespace Wf\Bean\Annotations;

/**
 * 控制器
 * @Annotation()
 * @Target({Declared::TYPE})
 * @ResponseBody()
 * @Controller()
 */
class RestController
{
	/**
	 * @Alias('Wf\Bean\Annotations\Controller')
	 */
	public $value = '';

	public function __construct(string $prefix = '')
	{
		$this->value = $prefix;
	}
}