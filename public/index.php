<?php

$serverConfig = Wf\Core\Functions::load(ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'server.php');

try {
	$server = new Swoole\Http\Server(
		$serverConfig['host'] ?? '127.0.0.1',
		$serverConfig['port'] ?? 9000
	);

	// 配置
	$server->set([
		'worker_num' => $serverConfig['worker_num'] ?? 1,
	    'daemonize' => $serverConfig['daemonize'] ?? false,
	    'backlog' => $serverConfig['backlog'] ?? 128,
	]);

	// 注册自动加载，解析项目类
	$server->on('start', function () {
		Wf\Core\Loader::instance();
		Wf\Core\App::instance();
	});

	// 接受请求
	$server->on('request', function ($request, $response) {
		(Wf\Core\App::instance())->handleRequest($request, $response);
	});

	// 结束操作
	$server->on('close', function() {

	});

	$server->start();	
} catch (Exception $e) {
	Wf\Core\RuntimeException::exception($e);
}