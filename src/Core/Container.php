<?php

namespace Wf\Core;

use Wf\Mime;
use Wf\Bean\Annotations\{
	Declared, Single
};
use Wf\Bean\Parser\Base;

class Container
{
	/**
	 * @var ObjectDefinition[]
	 */
	private $_objectDefineds = [];

	private $_singleDefineds = [];

	private $_beanDefineds = [];

	private $_parentDefineds = [];

	/**
	 * @var Resource
	 */
	private $_resource;

	/**
	 * @var Annotation
	 */
	private $_annotation;

	private $_initMethod = 'init';

	private $_baseProxy = 'Wf\\Bean\\Proxy\\Base';

	public function __construct()
	{
		$this->_resource = new Resource();
		$this->_annotation = new Annotation($this->_resource);
	}

	public function start(): void
	{
		$this->parseObjectDefineds();
		foreach ($this->_objectDefineds as $name => $objectDefinition) {
			// 父类映射
			foreach ($objectDefinition->getParentClassNames() as $parentClassName) {
				if (isset($this->_parentDefineds[$parentClassName])) {
					continue;
				}

				$this->_parentDefineds[$parentClassName] = $name;
			}

			// bean映射
			if ($objectDefinition->isBean()) {
				$beanName = $objectDefinition->getBeanName();
				if (isset($tihs->_beanDefineds[$beanName])) {
					throw new RuntimeException(
						"the bean[{$name}] exists on class[{$objectDefinition->getClassName()}]"
					);
				}

				$this->_beanDefineds[$beanName] = $name;
			}

			// 是否是懒加载
			if ($objectDefinition->isLazy()) {
				continue;
			}

			$this->set($name, $objectDefinition);
		}
	}

	public function get(string $name)
	{
		$name = ltrim($name, '\\');
		if (isset($this->_singleDefineds[$name])) {
			return $this->_singleDefineds[$name];
		}

		while (!isset($this->_objectDefineds[$name])) {
			// 判断是否存在子类
			if (!isset($this->_parentDefineds[$name])) {
				throw new RuntimeException("the bean[{$name}] not exists");
			}

			$name = $this->_parentDefineds[$name];
		}

		return $this->set($name, $this->_objectDefineds[$name]);
	}

	public function getBean(string $name)
	{
		if (!isset($this->_beanDefineds[$name])) {
			throw new RuntimeException("the bean[{$name}] not exists");
		}

		return $this->get($this->_beanDefineds[$name]);
	}

	public function set(string $name, ObjectDefinition $objectDefinition)
	{
		$scope = $objectDefinition->getScope();
        $className = $objectDefinition->getClassName();
        $propertyInjects = $objectDefinition->getPropertyInjectors();
        $constructorInject = $objectDefinition->getConstructInjector();

        // 构造函数
        $constructorParameters = [];
        if ($constructorInject !== null) {
            $constructorParameters = $this->_injectConstructor($className, $constructorInject);
        }

        $reflectionClass = $this->_resource->getReflectionClass($className);
        $properties = $reflectionClass->getProperties();
        $object = $this->newBeanInstance($reflectionClass, $constructorParameters);

        // 属性注入
        $this->injectProperties($object, $properties, $propertyInjects);

        // 执行初始化方法
        if ($reflectionClass->hasMethod($this->_initMethod) && 
        	$reflectionClass->getMethod($this->_initMethod)->isPublic()) {
            $object->{$this->_initMethod}();
        }

        // 是否需要代理
        if ($this->_checkProxyValid($objectDefinition->getProxyClass())) {
        	$proxyClass = $objectDefinition->getProxyClass();
        	$object = (new $proxyClass())->create($reflectionClass, $object);
        }

        // 单例处理
        if ($scope === Single::SINGLE) {
            $this->_singleDefineds[$name] = $object;
        }

        return $object;
	}

	public function parseObjectDefineds(): void
	{
		$definitions = $this->_resource->getDefinitionsWithCache();
		foreach ($definitions as $className) {
			$annotations = $this->_annotation->getClassAnnotionsWithCache($className);
			if (empty($annotations)) {
				continue;
			}

			$objectDefinition = new ObjectDefinition();
			$objectDefinition->setClassName($className);
			$reflectionClass = $this->_resource->getReflectionClass($className);
			$parentReflectionClass = $reflectionClass->getParentClass();
			if ($parentReflectionClass) {
				$objectDefinition->setParentClassName($parentReflectionClass->getName());
				unset($parentReflectionClass);
			}

			foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
				$objectDefinition->setParentClassName($interfaceName);
			}

			// class
			$tmpAnnotations = $annotations[Declared::TYPE] ?? [];
			$this->_parseObjectAnnotations(
				$objectDefinition,
				null,
				$tmpAnnotations,
				Declared::TYPE
			);
			// property
			$tmpAnnotations = $annotations[Declared::PROPERTY] ?? [];
			foreach ($tmpAnnotations as $name => $tmpAnnotation) {
				$propertyObject = new PropertyInjector($name);
				$this->_parseObjectAnnotations(
					$objectDefinition,
					$propertyObject, 
					$tmpAnnotation['annotation'], 
					Declared::PROPERTY
				);
				$propertyObject->setHasDefaultValue(array_key_exists('value', $tmpAnnotation));
				$objectDefinition->setPropertyInjector($name, $propertyObject);
				unset($propertyObject);
			}

			// method
			$tmpAnnotations = $annotations[Declared::METHOD] ?? [];
			foreach ($tmpAnnotations as $name => $tmpAnnotation) {
				$methodObject = new MethodInjector($name, $tmpAnnotation['returnType']);
				foreach ($tmpAnnotation['parameter'] as $parameterName => $parameter) {
					$parameterObject = new ArgInjector($parameterName);
					if (array_key_exists('type', $parameter)) {
						$parameterObject->setType($parameter['type']);
					}

					$parameterObject->setHasDefaultValue(array_key_exists('value', $parameter));
					$methodObject->setParamInjector($parameterName, $parameterObject);
					unset($parameterObject);
				}

				$this->_parseObjectAnnotations(
					$objectDefinition,
					$methodObject,
					$tmpAnnotation['annotation'],
					Declared::METHOD
				);
				if ($name == '__construct') {
					$objectDefinition->setConstructInjector($methodObject);
				} else {
					$objectDefinition->setMethodInjector($name, $methodObject);
				}

				unset($methodObject);
			}

			$this->_objectDefineds[$className] = $objectDefinition;
		}

		$this->_resource->clear();
	}

	protected function _parseObjectAnnotations(
		ObjectDefinition $objectDefinition,
		$typeObject,
		array &$annotations,
		string $type)
	{
		foreach ($annotations as $name => $annotationObjects) {
			$parser = $this->_resource->getAnnotationParser($name);
			if (!$parser && !($parser instanceof Base)) {
				continue;
			}

			if ($type == Declared::METHOD) {
				$parameters = $typeObject->getParameterNames();
			}

			foreach ($annotationObjects as $index => $annotationObject) {
				$parser->parse(
					$objectDefinition,
					$typeObject,
					$annotationObject,
					$type,
					$parameters[$index] ?? ''
				);
			}
		}
	}

	/**
	 * 控制器注入
	 */
	private function _injectConstructor(string $className, MethodInjection $constructorInject): array
    {
        $constructorParameters = [];

        /* @var ArgsInjector $parameter */
        foreach ($constructorInject->getParamInjectors() as $parameter) {
            $argValue = $parameter->getValue();
            if ($parameter->isRefBean()) {
                $argValue = $this->get($argValue);
            }

            if (empty($argValue)) {
            	if ($parameter->hasDefaultValue()) {
					break;
            	}

            	if ($parameter->getType()) {
            		$argValue = $this->get($parameter->getType());
            	} else {
					throw new RuntimeException(
						"the class[{$className}] 's has a error parameter[{$parameter->getArgName()}]"
					);
            	}
            }

            $constructorParameters[] = $argValue;
        }

        return $constructorParameters;
    }

    private function newBeanInstance(\ReflectionClass $reflectionClass, array $constructorParameters)
    {
        if ($reflectionClass->hasMethod('__construct')) {
            return $reflectionClass->newInstanceArgs($constructorParameters);
        }

        return $reflectionClass->newInstance();
    }

  	/**
  	 * 属性注入
  	 */
    private function injectProperties($object, array $properties, $propertyInjects)
    {
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertyName = $property->getName();
            if (!isset($propertyInjects[$propertyName])) {
                continue;
            }

            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }

            /* @var PropertyInjection $propertyInject */
            $propertyInject = $propertyInjects[$propertyName];
            $injectProperty = $propertyInject->getValue();
            if ($propertyInject->isRefBean()) {
                $injectProperty = $this->get($injectProperty);
            }

            if ($injectProperty !== null) {
                $property->setValue($object, $injectProperty);
            }
        }
    }

    /**
     * 判断是否存在代理类
     */
    private function _checkProxyValid(string $proxy): bool
    {
    	return !empty($proxy) &&
    			class_exists($proxy) &&
    			$this->_resource->getReflectionClass($proxy)->isSubclassOf($this->_baseProxy);
    	;
    }
}