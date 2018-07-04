<?php

namespace Wf\Core;

class PropertyInjector
{
	private $_propertyName = '';

	/**
	 * 属性值，如果ref为true，则引用bean
	 */
	private $_value;

	/**
	 * @var bool
	 */
	private $_hasDefaultValue = false;

	/**
	 * @var bool
	 */
	private $_ref = false;

	public function __construct(string $propertyName)
	{
		$this->_propertyName = $propertyName;
	}

	public function setValue($value): PropertyInjector
	{
		$this->_value = $value;
		return $this;
	}

	public function setRef(bool $ref): PropertyInjector
	{
		$this->_ref = $ref;
		return $this;
	}

	public function isRefBean(): bool
	{
		return $this->_ref;
	}

	public function getValue()
	{
		return $this->_value;
	}

	public function getPropertyName(): string
	{
		return $this->_propertyName;
	}

	public function setHasDefaultValue(bool $hasDefaultValue): PropertyInjector
	{
		$this->_hasDefaultValue = $hasDefaultValue;
		return $this;
	}

	public function hasDefaultValue(): bool
	{
		return $this->_hasDefaultValue;
	}
}