<?php

namespace Wf\Core;

use Swoole\Http\Request as ServerRequest;

class Request
{
	/**
	 * @var ServerRequest
	 */
	private $_request;

    /**
     * @var Request
     */
    private static $_instance;

    private function __construct()
    {

    }

    public static function instance(): Request
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function setRequest(ServerRequest $rq): Request
    {
        $this->_request = $rq;
        return $this;
    }

	public function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_request->get;
        }
        
        if (isset($this->_request->get[$key])) {
            return $this->_request->get[$key];
        }
        return $default;
    }

    public function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_request->post;
        }
        
        if (isset($this->_request->post[$key])) {
            return $this->_request->post[$key];
        }

        return $default;
    }

    public function getCookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_request->cookie;
        }
    
        if (isset($this->_request->cookie[$key])) {
            return $this->_request->cookie[$key];
        }
        
        return $default;
    }

    public function getParam($key, $default = null)
    {
        if (isset($this->_request->get[$key])) {
            return $this->_request->get[$key];
        } elseif (isset($this->_request->post[$key])) {
            return $this->_request->post[$key];
        }

        return $default;
    }

    public function getServer($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_request->server;
        }
    
        if (isset($this->_request->server[$key])) {
            return $this->_request->server[$key];
        }
        
        return $default;
    }

    public function clear(): void
    {
        unset($this->_request);
        $this->_request = null;
    }
}