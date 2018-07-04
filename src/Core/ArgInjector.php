<?php

namespace Wf\Core;

class ArgInjector
{
	private $_argName = '';

	private $_type = '';

	private $_value;

	private $_ref = false;

	private $_hasDefaultValue = false;

	private $_aliasName = '';

	public function __construct(string $argName)
	{
		$this->_argName = $argName;
	}

	public function setType(string $type): ArgInjector
	{
		$this->_type = $type;
		return $this;
	}

	public function setValue($value): ArgInjector
	{
		$this->_value = $value;
		return $this;
	}

	public function setRef(bool $ref)
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

	public function getArgName(): string
	{
		return $this->_argName;
	}

	public function getType(): string
	{
		return $this->_type;
	}

	public function hasType(): bool
	{
		return !empty($this->_type);
	}

	public function setHasDefaultValue(bool $hasDefaultValue): ArgInjector
	{
		$this->_hasDefaultValue = $hasDefaultValue;
		return $this;
	}

	public function hasDefaultValue(): bool
	{
		return $this->_hasDefaultValue;
	}

	public function setAliasName(string $name): ArgInjector
	{
		$this->_aliasName = $name;
		return $this;
	}

	public function getAliasName(): string
	{
		return $this->_aliasName;
	}

	public function getFinalName(): string
	{
		if (!empty($this->getAliasName())) {
			return $this->getAliasName();
		}

		return $this->getArgName();
	}
}