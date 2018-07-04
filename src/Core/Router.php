<?php

namespace Wf\Core;

use Wf\Bean\Collector\ControllerCollector;

class Router
{
	private $_routes = [];

	const SEQ = '/';

	/**
	 * @var Router
	 */
	private static $_instance;

	private function __construct()
	{

	}

	public static function instance()
	{
		if (!self::$_instance) {
			$instance = new self();
			self::$_instance = $instance;
			$instance->_parsedRouters();
		}

		return self::$_instance;
	}

	protected function _parsedRouters()
	{
		$requestMappings = ControllerCollector::getRequestMapping();
		foreach ($requestMappings as $controllerName => $routes) {
			if (!isset($routes['prefix'])) {
				throw new RuntimeException(
					"the annotation[controller] must be exists on controller[{$controllerName}]"
				);
			}

			$isResponseBody = !empty($routes['response']);
			foreach ($routes['routes'] as $action => $route) {
				$router = rtrim($routes['prefix'], self::SEQ) . self::SEQ . ltrim($route['route'], self::SEQ);
				if (isset($this->_routes[$router])) {
					throw new RuntimeException("the router[{$router}] exists on controller[{$controllerName}]");
				}

				$methodInjector = $route['methodInjector'];
				$this->_routes[$router] = [
					'httpMethods' => $route['httpMethods'],
					'controller' => $controllerName,
					'action' => $methodInjector->getMethodName(),
					'parameters' => $methodInjector->getParamInjectors(),
					'response' => $isResponseBody || !empty($route['response']),
				];
			}
		}
	}

	public function getRoutes(): array
	{
		return $this->_routes;
	}

	public function getRoute(string $router): array
	{
		if (!isset($this->_routes[$router])) {
			throw new RuntimeException("the router[{$router}] not exists");
		}

		return $this->_routes[$router];
	}

	public function getRouteWithMethod(string $router, string $method): array
	{
		$route = $this->getRoute($router);
		if (!in_array($method, $route['httpMethods'])) {
			throw new RuntimeException("the router[{$router}] not allow method[{$method}]");
		}

		unset($route['httpMethods']);
		return $route;
	}

	/**
	 * 解析出对应的路由规则
	 */
	public function parseUrl(Request $rq): string
	{
		$pathInfo = '';
		if (!empty($rq->getServer('path_info'))) {
            $pathInfo = $rq->getServer('path_info');
        } else {
            $requestUri = urldecode($rq->getServer('request_uri'));
            // 是否存在GET
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }

            $filename = $rq->getServer('script_filename') ? 
            	basename($rq->getServer('script_filename')) : '';
            if ($rq->getServer('script_name') &&
            	basename($rq->getServer('script_name')) === $filename)
            {
                $baseUrl = $rq->getServer('script_name');
            } elseif ($rq->getServer('php_self') && 
            	basename($rq->getServer('php_self')) === $filename) 
            {
                $baseUrl = $rq->getServer('php_self');
            } elseif ($rq->getServer('orig_script_name') &&
            	basename($rq->getServer('orig_script_name')) === $filename) 
            {
                $baseUrl = $rq->getServer('orig_script_name');
            } else {
                $baseUrl = self::SEQ;
            }
            
            if (!empty($baseUrl)) {
                if (0 === strpos($requestUri, $baseUrl)) {
                    $pathInfo = substr($requestUri, strlen($baseUrl));
                } elseif (0 === strpos($requestUri, dirname($baseUrl))) {
                    $pathInfo = substr($requestUri, strlen(dirname($baseUrl)));
                }
            }
            
        }

        return !empty($pathInfo) ? self::SEQ . trim($pathInfo, self::SEQ) : self::SEQ;
	}

	/**
	 * 解析控制器
	 */
	public function parseAction(Request $rq, string $router, string $method): array
	{
		$route = $this->getRouteWithMethod($router, $method);
		$parameters = [];

		/** 
		 * @var $argInjector ArgInjector 
		 */
		foreach ($route['parameters'] as $parameterName => $argInjector) {
			// 参数类型只处理最简单
			$argName = $argInjector->getFinalName();
			$argValue = $rq->getParam($argName);
			if (empty($argValue) && !$argInjector->hasDefaultValue()) {
				throw new RuntimeException("the router[{$router}] parameter[{$argName}] must be exists");
			}

			if (empty($argValue)) {
				break;
			}

			if ($argInjector->hasType()) {
				switch ($argInjector->getType()) {
					case 'boolean':
						$argValue = boolval($argValue);
						break;
					case 'integer':
						$argValue = intval($argValue);
						break;
					case 'string':
					default:
						break;
				}
			}

			$parameters[] = $argValue;
		}

		return [$route['controller'], $route['action'], $parameters, $route['response']];
	}

	public function parse(Request $rq): array
	{
		$router = $this->parseUrl($rq);
		$method = $rq->getServer('request_method');
		return $this->parseAction($rq, $router, $method);
	}
}