<?php

namespace Wf\Bean\Proxy;

use Wf\Core\Functions;
use Wf\Mime;

class Normal extends Base
{
	public function create(\ReflectionClass $ref, $object)
	{
		list($namespace, $proxyName, $name) = $this->getClassInfo($ref->getName());
		$isParsed = false;
		if (APP_ENV == 'production') {
			$cacheFile = APP_CACHE_PATH . DIRECTORY_SEPARATOR . $proxyName . Mime::SEPARATOR . Mime::PHP;
			if (\file_exists($cacheFile)) {
				Functions::import($cacheFile);
				$isParsed = true;
			}
		}

		if (!$isParsed) {
			$reflectionMethods = $ref->getMethods(
				\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
			);
			$handlerName = '__handler__';
			// 函数覆盖
			$methods = $this->_getMethodsTemplate($reflectionMethods, $handlerName);
			$template = <<<PROXYCLASS
namespace {$namespace};
class {$proxyName} extends {$name}
{
	private \${$handlerName};
    public function __construct(\$handler)
    {
        \$this->{$handlerName} = \$handler;
    }
    {$methods}
}
PROXYCLASS;
			if (APP_ENV == 'production') {
                $template = '<?php' . PHP_EOL . $template;
				file_put_contents($cacheFile, $template);
				Functions::import($cacheFile);
			} else {
				eval($template);
			}
		}

		$proxyClass = new \ReflectionClass("{$namespace}\\{$proxyName}");
		return $proxyClass->newInstance($object);
	}

    private function _getMethodsTemplate(array $reflectionMethods, string $handlerPropertyName): string
    {
        $template = '';
        foreach ($reflectionMethods as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();
            if ($reflectionMethod->isConstructor() || $reflectionMethod->isStatic()) {
                continue;
            }

            $methodParameters = $this->_getParameterTemplate($reflectionMethod);
            $reflectionMethodReturn = $reflectionMethod->getReturnType();
            $returnType = '';
            if ($reflectionMethodReturn !== null) {
                $returnType = $reflectionMethodReturn->__toString();
                $returnType = $returnType === 'self' ? $reflectionMethod->getDeclaringClass()->getName() : $returnType;
                $returnType = ": {$returnType}";
            }

            $template .= <<<METHOD

    public function {$methodName}({$methodParameters}){$returnType}
    {
    	return \$this->{$handlerPropertyName}->invoke('{$methodName}', func_get_args());
    }
METHOD;
        }

        return $template;
    }

    private function _getParameterTemplate(\ReflectionMethod $reflectionMethod): string
    {
        $template = '';
        $reflectionParameters = $reflectionMethod->getParameters();
        $paramCount = \count($reflectionParameters);
        foreach ($reflectionParameters as $reflectionParameter) {
            $paramCount--;
            $type = $reflectionParameter->getType();
            if ($type !== null) {
                $type = $type->__toString();
                $template .= " $type ";
            }

            $paramName = $reflectionParameter->getName();
            if ($reflectionParameter->isPassedByReference()) {
                $template .= " &\${$paramName} ";
            } elseif ($reflectionParameter->isVariadic()) {
                $template .= " ...\${$paramName} ";
            } else {
                $template .= " \${$paramName} ";
            }

            if ($reflectionParameter->isOptional() && $reflectionParameter->isVariadic() === false) {
                $template .= $this->_getParameterDefaultValue($reflectionParameter);
            }

            if ($paramCount !== 0) {
                $template .= ',';
            }
        }

        return $template;
    }

    private function _getParameterDefaultValue(\ReflectionParameter $reflectionParameter): string
    {
        $template = '';
        $defaultValue = $reflectionParameter->getDefaultValue();
        if ($reflectionParameter->isDefaultValueConstant()) {
            $defaultConst = $reflectionParameter->getDefaultValueConstantName();
            $template = " = {$defaultConst}";
        } elseif (\is_bool($defaultValue)) {
            $value = $defaultValue ? 'true' : 'false';
            $template = " = {$value}";
        } elseif (\is_string($defaultValue)) {
            $template = " = ''";
        } elseif (\is_int($defaultValue)) {
            $template = ' = 0';
        } elseif (\is_array($defaultValue)) {
            $template = ' = []';
        } elseif (\is_float($defaultValue)) {
            $template = ' = []';
        } elseif (\is_object($defaultValue) || null === $defaultValue) {
            $template = ' = null';
        }

        return $template;
    }
}
