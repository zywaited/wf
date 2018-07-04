<?php

namespace Wf\Bean\Annotations;

/**
 * 路由
 * @Annotation()
 * @Target({Declared::METHOD})
 */
class RequestMapping
{
	public $value = '';

	public $methods = [RequestMethod::GET, RequestMethod::POST];
}