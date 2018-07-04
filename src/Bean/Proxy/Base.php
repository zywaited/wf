<?php

namespace Wf\Bean\Proxy;

abstract class Base
{
	public function getClassInfo(string $name)
	{
		$name = ltrim($name, '\\');
		$info = explode('\\', $name);
		$name = array_pop($info);
		$namespace = implode('\\', $info);
		$proxyName = "{$name}Proxy";
		return [$namespace, $proxyName, $name];
	}

	public abstract function create(\ReflectionClass $ref, $object);
}