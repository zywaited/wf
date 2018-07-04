# 说明
- 1 只是测试模拟使用java spring注解，了解其原理
- 2 服务端依赖swoole
- 3 实现部分参考swoft，感谢!
# 自动加载
```sh
composer install --no-dev
```
# 创建和执行
```sh
bin/wf create test
bin/wf run test
```

# 已实现注解
- @Controller 标明控制器及路径
- @RestController 结合Controller和ResponseBody注解
- @RequestMapping 路径和请求方法
- @RequestParam 请求参数
- @Value 加载配置项，路径来自wf自动创建
- @Service 标明service
- @ResponseBody 说明是返回对象
- @Scope 多例
- @Lazy 懒加载
- @Autowired 自动注入

# 实例
## Controller
```php
<?php

namespace Test\Controllers;

/**
 * @@Controller('/index')
 * @RestController('/index')
 */
class Index
{

	/**
	 * @Autowired('\Test\Models\Service\Test')
	 */
	private $_test;

	/**
	 * @RequestMapping(value = 'test')
	 * @ResponseBody()
	 * @RequestParam('name')
	 * @RequestParam('age')
	 */
	public function test(string $name, int $age): array
	{
		return [
			'name' => $name,
			'age' => $age,
			'redis_host' => $this->_test->getHost(),
		];
	}
}
```
## Service
```php
<?php

namespace Test\Models\Service;

/**
 * @Service()
 * @Scope()
 * @Lazy()
 */
class Test
{
	/**
	 * 读取配置
	 * @Value('test.redis.host')
	 */
	private $_redisHost = '';

	public function getHost(): string
	{
		return $this->_redisHost;
	}
}
```
## 访问
```sh
http://127.0.0.1:9000/index/test?name=zj&age=2
```