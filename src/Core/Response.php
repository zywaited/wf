<?php

namespace Wf\Core;

use Swoole\Http\Response as ServerResponse;
use Wf\Mime;

class Response
{
    /**
     * @var Response
     */
    private static $_instance;

    private $_maps = [];

    private function __construct()
    {

    }

    public static function instance(): Response
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function send(ServerResponse $rp, $data)
    {
    	if (count($this->_maps) < 1) {
    		// 自动处理
    		$type = gettype($data);
    		switch ($type) {
    			case 'array':
    			case 'object':
    				$rp->header('Content-Type', Mime::CONTENT_TYPE_JSON);
    				$data = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
    				break;
    			default:
    				break;
    		}

    		$rp->end($data);
    		return;
    	}

    	foreach ($this->_maps as $func) {
    		call_user_func($func, $rq, $data);
    	}
    }
}