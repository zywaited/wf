<?php

/**
 * swoole http server config
 */
return [
	'host' => '127.0.0.1',
	'port' => 9000,
	'worker_num' => 1,
	'daemonize' => false,
	'backlog' => 128,
];