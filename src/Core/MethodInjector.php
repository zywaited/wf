<?php

namespace Wf\Core;

class MethodInjector
{
	private $_methodName = '';

	/**
	 * 参数
	 * @var ArgInjector[]
	 */
	private $_paramInjections = [];

	private $_returnType = '';

	public function __construct(string $methodName, string $returnType = '')
	{
		$this->_methodName = $methodName;
		$this->_returnType = $returnType;
	}

	public function setParamInjector(string $paramName, ArgInjector $arg): MethodInjector
	{
		$this->_paramInjections[$paramName] = $arg;
		return $this;
	}

	public function setParamInjectors(array $args): MethodInjector
	{
		$this->_paramInjections = array_merge($this->_paramInjections, $args);
		return $this;
	}

	/**
	 * @return ArgInjector | null
	 */
	public function getParamInjectorByName(string $paramName)
	{
		return $this->_paramInjections[$paramName] ?? null;
	}

	public function getParamInjectors(): array
	{
		return $this->_paramInjections;
	}

	public function getMethodName(): string
	{
		return $this->_methodName;
	}

	public function getParameterNames(): array
	{
		return array_keys($this->_paramInjections);
	}
}