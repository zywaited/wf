<?php

namespace Wf\Core;

use Wf\Bean\Annotations\Single;

class ObjectDefinition
{
	private $_beanName = '';

	/**
	 * 类名, 必须
	 * @var string
	 */
	private $_className = '';

    private $_parentClassNames = [];

	/**
	 * 单例标识
	 * @var int
	 */
	private $_scope = Single::SINGLE;

    private $_lazy = false;

    /**
     * 构造函数
     * @var MethodInjector | null
     */
    private $_constructorInjection = null;

    /**
     * 属性
     * @var PropertyInjector[]
     */
    private $_propertyInjections = [];

    /**
     * 各方法
     * @var MethodInjector[]
     */
    private $_methodInjections = [];

    /**
     * @var string 代理生成类
     */
    private $_proxy = '';

    public function setBeanName(string $beanName): ObjectDefinition
    {
    	$this->_beanName = $beanName;
    	return $this;
    }

    public function getBeanName(): string
    {
    	return $this->_beanName;
    }

    public function isBean(): bool
    {
    	return !empty($this->_beanName);
    }

    public function setLazy(bool $lazy): ObjectDefinition
    {
        $this->_lazy = $lazy;
        return $this;
    }

    public function isLazy(): bool
    {
        return $this->_lazy;
    }

    public function setClassName(string $className): ObjectDefinition
    {
    	$this->_className = $className;
    	return $this;
    }

    public function getClassName(): string
    {
    	return $this->_className;
    }

    public function setParentClassName(string $parentClassName): ObjectDefinition
    {
        $this->_parentClassNames[$parentClassName] = $parentClassName;
        return $this;
    }

    public function getParentClassNames(): array
    {
        return $this->_parentClassNames;
    }

    public function hasParent()
    {
        return !empty($this->_parentClassName);
    }

    public function setScope(int $scope): ObjectDefinition
    {
    	$this->_scope = $scope;
    	return $this;
    }

    public function getScope(): int
    {
    	return $this->_scope;
    }

    public function isSingleScope(): bool
    {
    	return $This->_scope = Single::SINGLE;
    }

    public function setConstructInjector(MethodInjector $construct): ObjectDefinition
    {
    	$this->_constructorInjection = $construct;
    	return $this;
    }

    public function setPropertyInjector(string $propertyName, PropertyInjector $property): ObjectDefinition
    {
    	$this->_propertyInjections[$propertyName] = $property;
    	return $this;
    }

    public function setPropertyInjectors(array $properties): ObjectDefinition
    {
    	$this->_propertyInjections = array_merge($this->_propertyInjections, $properties);
    	return $this;
    }

    /**
     * @return MethodInjector | null
     */
    public function getConstructInjector()
    {
    	return $this->_constructorInjection;
    }

    /**
     * @return PropertyInjector | null
     */
    public function getPropertyInjectorByName(string $propertyName)
    {
    	return $this->_propertyInjections[$propertyName] ?? null;
    }

    public function getPropertyInjectors(): array
    {
    	return $this->_propertyInjections;
    }

    public function setMethodInjector(string $methodName, MethodInjector $method): ObjectDefinition
    {
    	$this->_methodInjections[$methodName] = $method;
    	return $this;
    }

    public function setMethodInjectors(array $methods): ObjectDefinition
    {
    	$this->_methodInjections = array_merge($this->_methodInjections, $methods);
    	return $this;
    }

    public function getMethodInjectors(): array
    {
    	return $this->_methodInjections;
    }

    /**
     * @return MethodInjector | null
     */
    public function getMethodInjectorByName(string $methodName)
    {
    	return $this->_methodInjections[$methodName] ?? null;
    }

    public function getProxyClass(): string
    {
        return $this->_proxy;
    }

    public function setProxyClass(string $proxy): ObjectDefinition
    {
        $this->_proxy = $proxy;
        return $this;
    }
}