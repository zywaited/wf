<?php

namespace Wf\Core;

use Wf\Mime;
use Swoole\Http\{
	Request as ServerRequest, Response as ServerResponse
};

class App
{
	/**
	 * @var App
	 */
	private static $_instance;

	private function __construct()
	{

	}

	public static function instance(): App
	{
		if (!self::$_instance) {
			$instance = new self();
			self::$_instance = $instance;
			$instance->_run();
		}

		return self::$_instance;
	}

	private function _run(): void
	{
		// 错误捕获
		RuntimeError::register();
		// 处理注解
		Context::instance();
		// 处理路由
		Router::instance();
		// 处理异常
		Exceptions::instance();
	}

	public function handleRequest(ServerRequest $request, ServerResponse $rp)
	{
		$context = Context::instance();
		$rq = (Request::instance())->setRequest($request);
		$context->setRequest($rq)->setResponse($rp);
		$data = null;
		$isResponse = false;
		try {
			// 解析控制器、方法和参数
			list(
				$controller,
				$action,
				$parameters,
				$isResponse
			) = (Router::instance())->parse($rq);
			$controllerInstance = $context->getRef($controller);
			if ($isResponse) {
				$data = call_user_func_array([$controllerInstance, $action], $parameters);
			} else {
				call_user_func_array([$controllerInstance, $action], $parameters);
			}
		} catch (\Exception $e) {
			$handler = (Exceptions::instance())->getExceptionHandler($e);
			if (empty($handler)) {
				$isResponse = true;
				$data = (Exceptions::instance())->default($e);
			}
		}

		$rq->clear();
		$context->resetRequest();
		if (!$isResponse) {
			return;
		}

		(Response::instance())->send($rp, $data);
	}
}