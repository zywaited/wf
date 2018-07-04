<?php

namespace Wf\Bean\Collector;

use Wf\Bean\Annotations\{
	Controller as AnnotationsController,
	RequestMapping,
	RequestMethod,
    ControllerAdvice,
    ResponseBody
};
use Wf\Core\{
    Router,
    RuntimeException
};

class ControllerCollector
{
	private static $_requestMappings = [];

    private static $_exceptions = [];

	public static function collect(
        string $className,
        $objectAnnotation,
        $methodInjector = null,
        string $methodName = ''
    ) {
        if ($objectAnnotation instanceof AnnotationsController) {
            self::$_requestMappings[$className]['prefix'] = 
                Router::SEQ . trim($objectAnnotation->value, Router::SEQ);
            return;
        }

        if ($objectAnnotation instanceof ControllerAdvice) {
            self::$_exceptions[$className]['advice'] = true;
            return;
        }

        if ($objectAnnotation instanceof RequestMapping) {
            if (!isset(self::$_requestMappings[$className]['routes'])) {
                self::$_requestMappings[$className]['routes'] = [];
            }

            $routes = &self::$_requestMappings[$className]['routes'];
            $routes[$methodName]['route'] = Router::SEQ . trim($objectAnnotation->value, Router::SEQ);
            $routes[$methodName]['methodInjector'] = $methodInjector;
            $routes[$methodName]['httpMethods'] = $objectAnnotation->methods;
            return;
        }

        if ($objectAnnotation instanceof ResponseBody) {
            $re = $ex = false;
            if (isset(self::$_requestMappings[$className])) {
                $re = true;
            }

            if (isset(self::$_exceptions[$className])) {
                $ex = true;
            }

            if ($re && $ex) {
                throw new RuntimeException("the annotation[ControllerAdvice] and [Controller] must be one");
            }

            if ($re) {
                // 控制器上
                if (empty($methodName)) {
                    self::$_requestMappings[$className]['response'] = true;
                    return;
                }

                self::$_requestMappings[$className]['routes'][$methodName]['response'] = true;
                return;
            } elseif ($ex) {
                // 控制器上
                if (empty($methodName)) {
                    self::$_exceptions[$className]['response'] = true;
                    return;
                }

                self::$_exceptions[$className]['methods'][$methodName]['response'] = true;
                return;
            } else {
                if (empty($methodName)) {
                    self::$_requestMappings[$className]['response'] = true;
                    self::$_exceptions[$className]['response'] = true;
                    return;
                }

                self::$_requestMappings[$className]['routes'][$methodName]['response'] = true;
                self::$_exceptions[$className]['methods'][$methodName]['response'] = true;
                return;
            }
        }
    }

    public static function handler(
        string $className,
        $objectAnnotation,
        $methodInjector,
        string $methodName)
    {

    }

	public static function getRequestMapping(): array
	{
		return self::$_requestMappings;
	}

	public static function clearRequestMappings(): void
	{
		self::$_requestMappings = [];
	}

    public static function getExceptions(): array
    {
        return self::$_exceptions;
    }

    public static function clearExceptions(): void
    {
        self::$_exceptions = [];
    }
}