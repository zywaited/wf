<?php

namespace Wf\Core;

use Wf\Cli\Project;
use Wf\Mime;
use Swoole\Http\Response;

class Context
{
	/**
	 * @var ConfigStore
	 */
	private $_config;

	/**
	 * @var \Swoole\Http\Request
	 */
	private $_request;

	/**
	 * @var \Swoole\Http\Response
	 */
	private $_response;

	/**
	 * @var Container
	 */
	private $_container;

	/**
	 * @var Context
	 */
	private static $_instance;

	private function __construct()
	{

	}

	public static function instance(): Context
	{
		if (!self::$_instance) {
			$instance = new self();
			self::$_instance = $instance;
			$instance->_init();
		}

		return self::$_instance;
	}

	private function _init()
	{
		$this->_config = (new Config())->getAllConfigs();
		$this->_container = new Container();
		$this->_container->start();
	}

	public function getRequst()
	{
		return $this->_request;
	}

	public function getResponse()
	{
		return $this->_response;
	}

	public function resetRequest()
	{
		$this->_request = null;
		$this->_response = null;
	}

	public function getConfig(): ConfigStore
	{
		return $this->_config;
	}

	public function getBean(string $beanName)
	{
		return $this->_container->getBean($beanName);
	}

	public function getRef(string $ref)
	{
		return $this->_container->get($ref);
	}

	public function setRequest(Request $rq): Context
	{
		$this->_request = $rq;
		return $this;
	}

	public function setResponse(Response $rp): Context
	{
		$this->_response = $rp;
		return $this;
	}
}