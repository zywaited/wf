<?php

namespace Wf\Core;

class ConfigStore implements \Iterator, \Countable, \ArrayAccess
{
    private $_configStore;
    private $_index;
    private $_count;
    private $_modify;

    public function __construct(array $configStore = [], bool $modify = false)
    {
        $this->_configStore = $configStore;
        $this->_index = 0;
        $this->_count = count($configStore);
        $this->_modify = $modify;
    }

    public function __get($name)
    {
        if (!isset($this->_configStore[$name])) {
            return null;
        }

        $subConfig = &$this->_configStore[$name];
        if (is_array($subConfig)) {
            $subConfig = new self($subConfig, $this->_modify);
            $this->_configStore[$name] = $subConfig;
        }

        return $subConfig;
    }

    public function __set($name, $value)
    {
    	if (!$this->_modify) {
        	throw new RuntiomException('Can\'t modify or set the config');
        }

        $this->_configStore[$name] = $value;
    }

    public function __isset($offset)
    {
        return isset($this->_configStore[$offset]);
    }

    public function toArray(): array
    {
        $configs = [];
        foreach ($this->_configStore as $name => $item) {
            if ($item instanceof self) {
                $item = $item->toArray();
            }

            $configs[$name] = $item;
        }

        return $configs;
    }

    public function key()
    {
        return key($this->_configStore);
    }

    public function current()
    {
        return current($this->_configStore);
    }

    public function next()
    {
        next($this->_configStore);
        $this->_index++;
    }

    public function valid()
    {
        return $this->_index < $this->_count;
    }

    public function count()
    {
        return $this->_count;
    }

    public function rewind()
    {
        reset($this->_configStore);
        $this->_index = 0;
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
    	if (!$this->_modify) {
	        throw new RuntiomException('Can\'t unset the config');
	    }

	    unset($this->_configStore[$offset]);
    }
}