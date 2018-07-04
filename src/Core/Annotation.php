<?php

namespace Wf\Core;

use Wf\Mime;
use Wf\Bean\Annotations\{
    Declared, Type
};
use Wf\Cli\Project;

class Annotation
{

    private $_definedAnnotations = [];

    private $_currentAnntations = [];

    private $_valueType = [
        'true' => true,
        'false' => false,
        'null' => null,
    ];

    // 用于异常提示
    private $_currentInfo;
    private $_currentAnnotation;

    // 系统的注解类
    public const BEAN_NAMEPSACE = 'Wf\\Bean\\';
    public const ANNOTATIONS_NAMESPACE = self::BEAN_NAMEPSACE . 'Annotations\\';
    public const ANNOTATION_CLASS = self::ANNOTATIONS_NAMESPACE . 'Annotation';
    public const TARGET_CLASS = self::ANNOTATIONS_NAMESPACE . 'Target';
    public const ALIAS_CLASS = self::ANNOTATIONS_NAMESPACE . 'Alias';
    public const REPEAT_CLASS = self::ANNOTATIONS_NAMESPACE . 'Repeat';
    const CACHE_NAME_EXT = '_annotations';

    /**
     * @var Resource
     */
    private $_resource;

    public function __construct(Resource $resource)
    {
        $this->_resource = $resource;
    }

    private function _getDefinedClassAnnotations(\ReflectionClass $class): array
    {
        $className = $class->getName();
        if (!isset($this->_definedAnnotations[$className]['class'])) {
            $this->_definedAnnotations[$className]['class'] = 
                $this->_parseDoc($class->getDocComment());
        }

        return $this->_definedAnnotations[$className]['class'];
    }

    private function _getDefinedPropertyAnnotations(
        string $className,
        \ReflectionProperty $property): array
    {
        $propertyName = $property->getName();
        if (!isset($this->_definedAnnotations[$className]['property'][$propertyName])) {
            $this->_definedAnnotations[$className]['property'][$propertyName] = 
                $this->_parseDoc($property->getDocComment());
        }

        return $this->_definedAnnotations[$className]['property'][$propertyName];
    }

     private function _getDefinedMethodAnnotations(
        string $className,
        \ReflectionMethod $method): array
    {
        $methodName = $method->getName();
        if (!isset($this->_definedAnnotations[$className]['method'][$methodName])) {
            $this->_definedAnnotations[$className]['method'][$methodName] = 
                $this->_parseDoc($method->getDocComment());
        }

        return $this->_definedAnnotations[$className]['method'][$methodName];
    }

    /**
     * 解析获取注解实例
     * 1 忽略方法
     * 2 检测参数合法性
     */
    private function _getAnnotationInstance(string $annotationName, array $params)
    {
        $reflectionAnnotation = $this->_resource->getReflectionClass($annotationName);
        if ($reflectionAnnotation->isInstantiable()) {
            if (!$this->_isConstructValid($reflectionAnnotation)) {
                throw new RuntimeException(
                    "The annotation[{$annotationName}]'s construct must be public"
                );
            }

            $constructParams = [];
            if ($reflectionAnnotation->hasMethod('__construct')) {
                $construct = $reflectionAnnotation->getMethod('__construct');
                foreach ($construct->getParameters() as $index => $parameter) {
                    $constructParams[$index] = [];
                    if ($parameter->hasType()) {
                        $constructParams[$index]['type'] = $parameter->getType()->__toString();
                    }

                    if ($parameter->isOptional()) {
                        $constructParams[$index]['value'] = $parameter->getDefaultValue();
                    }
                }
            }

            if ($params['construct']) {
                $instanceParams = [];
                foreach ($constructParams as $index => $param) {
                    if (!isset($params['params'][$index])) {
                        if (!isset($param['value'])) {
                            throw new RuntimeException(
                                "The annotation[{$annotationName}] has error param in {$this->_currentInfo}"
                            );
                        }

                        $instanceParams[] = $param['value'];
                        continue;
                    }

                    if (isset($param['type']) && $param['type'] != gettype($params['params'][$index])) {
                        throw new RuntimeException(
                            "The annotation[{$annotationName}] has error param in {$this->_currentInfo}"
                        );
                    }

                    $instanceParams[] = $params['params'][$index];
                }

                return $reflectionAnnotation->newInstanceArgs($instanceParams);
            }
        } else {
            if ($params['construct']) {
                throw new RuntimeException(
                    "The annotation[{$annotationName}] has construct method"
                );
            }
        }

        if ($reflectionAnnotation->isInstantiable()) {
            $annotationClass = $reflectionAnnotation->newInstanceWithoutConstructor();
        } else {
            $annotationClass = new \StdClass();
        }

        foreach ($params['params'] as $field => $value) {
            if (!$reflectionAnnotation->hasProperty($field)) {
                throw new RuntimeException(
                    "The annotation[{$annotationName}] has error param in {$this->_currentInfo}"
                );
            }

            if ($reflectionAnnotation->isInstantiable()) {
                $fieldClass  = $reflectionAnnotation->getProperty($field);
                if (!$fieldClass->isPublic()) {
                    throw new RuntimeException(
                        "The annotation[{$annotationName}]'s properties must be public"
                    );
                }

                $fieldClass->setValue($annotationClass, $value);
            } else {
                $annotationClass->{$field} = $value;
            }
        }

        return $annotationClass;
    }

    /**
     * 判断类是否可初始化
     */
    private function _isConstructValid(\ReflectionClass $class): bool
    {
        if ($class->hasMethod('__construct')) {
            $classConstruct = $class->getMethod('__construct');
            if (!$classConstruct->isPublic()) {
                return false;
            }
        }

        return true;
    }

    public function getClassAnnotionsWithCache(string $className): array
    {
        $cacheName = APP_CACHE_PATH . DIRECTORY_SEPARATOR . 
            str_replace('\\', '_', $className) . 
            self::CACHE_NAME_EXT . Mime::SEPARATOR . Mime::SERIALIZE;
        if (APP_ENV == 'production' && file_exists($cacheName)) {
            return Functions::load($cacheName, true, false);
        }

        $annotations = $this->getClassAnnotions($className);
        if (APP_ENV == 'production') {
            Functions::cache($cacheName, $annotations);
        }

        return $annotations;
    }

    /**
     * 获取类的所有注解
     * 1 类注解
     * 2 属性注解
     * 3 方法注解
     */
    public function getClassAnnotions(string $className): array
    {
        $definedAnnotations = [];
        $reflection = $this->_resource->getReflectionClass($className);
        $this->_currentInfo = $className;
        $annotations = $this->_getDefinedClassAnnotations($reflection);
        if (isset($annotations[self::ANNOTATION_CLASS])) {
            return $definedAnnotations;
        }

        if (count($annotations) < 1) {
            return $definedAnnotations;
        }

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(
                "The class[{$className}] must be a public class"
            );
        }

        if (!$this->_isConstructValid($reflection)) {
            throw new RuntimeException(
                "The class[{$className}]'s construct must be public"
            );
        }

        $this->_parseAnnotationInstance($annotations, Declared::TYPE);
        $definedAnnotations[Declared::TYPE] = $this->_currentAnntations;
        // property annotation
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $this->_currentInfo = "{$className}::{$propertyName}";
            $annotations = $this->_getDefinedPropertyAnnotations(
                $className,
                $property
            );
            unset($annotations[self::ALIAS_CLASS]);
            if (count($annotations) < 1) {
                continue;
            }

            $this->_parseAnnotationInstance($annotations, Declared::PROPERTY);
            $definedAnnotations[Declared::PROPERTY][$propertyName]['annotation'] = 
                $this->_currentAnntations;
            if ($property->isDefault()) {
                $definedAnnotations[Declared::PROPERTY][$propertyName]['value'] = true;
            }
        }

        unset($properties);
        // method annotation
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            $isOptional = false;
            $methodName = $method->getName();
            $this->_currentInfo = "{$className}::{$methodName}";
            $annotations = $this->_getDefinedMethodAnnotations(
                $className,
                $method
            );
            if (count($annotations) < 1) {
                continue;
            }

            if ($methodName == '__construct') {
                $this->_parseAnnotationInstance($annotations, Declared::CONSTRUCT);
            } else {
                $this->_parseAnnotationInstance($annotations, Declared::METHOD);
            }

            
            $parameters = $method->getParameters();
            $methodParameters = [];
            foreach ($parameters as $parameter) {
                $parameterName = $parameter->getName();
                if ($parameter->hasType()) {
                    $methodParameters[$parameterName]['type'] = $parameter->getType()->__toString();
                }

                if ($parameter->isOptional()) {
                    $methodParameters[$parameterName]['value'] = $parameter->getDefaultValue();
                    $isOptional = true;
                } elseif ($isOptional) {
                    throw new RuntimeException(
                        "The class[{$className}]'s method[{$methodName}] has a error parameter[{$parameterName}]"
                    );
                }
            }

            $definedAnnotations[Declared::METHOD][$methodName] = [
                'annotation' => $this->_currentAnntations,
                'parameter' => &$methodParameters,
                'returnType' => $method->getReturnType()->__toString() ?? '',
            ];
        }

        return $definedAnnotations;
    }

    private function _parseAnnotationInstance(
        array $annotations,
        string $type,
        $replace = true): void
    {
        if ($replace) {
            // 用于广度集成
            $this->_currentAnntations = $annotations;
        }

        foreach ($annotations as $name => $params) {
            $isRepeat = count($params) > 1;
            // 去除参数类型
            unset($this->_currentAnntations[$name]);
            foreach ($params as $param) {
                $annotationObject = $this->_getAnnotationInstance($name, $param);
                $this->_currentAnntations[$name][] = $annotationObject;
                $this->_parseAnnotationAnnotations(
                    $name,
                    $annotationObject,
                    $isRepeat,
                    $type
                );
            }
        }
    }

    /**
     * 获取注解上所有的注解
     * 1 获取注解
     * 2 判断属性上是否存在alias别名
     * 3 递归提取
     */
    private function _parseAnnotationAnnotations(
        string $annotationName,
        $annotationObject,
        $repeat = false,
        string $type = Declared::TYPE)
    {
        $annotation = $this->_resource->getReflectionClass($annotationName);
        $annotations = $this->_getDefinedClassAnnotations($annotation);
        $this->_checkAnnotationValid($annotationName, $repeat, $type);
        $annotationClass = $this->_getAnnotationInstance(
            self::ANNOTATION_CLASS,
            $annotations[self::ANNOTATION_CLASS][0]
        );
        if ($annotationClass->value == Type::SYSTEM_ANNOTATION) {
            return;
        }

        unset(
            $annotations[self::ANNOTATION_CLASS],
            $annotations[self::TARGET_CLASS],
            $annotations[self::REPEAT_CLASS]
        );

        // property annotation
        $properties = $annotation->getProperties();
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyAnnotations = $this->_getDefinedPropertyAnnotations(
                $annotationName,
                $property
            );
            if (!isset($propertyAnnotations[self::ALIAS_CLASS])) {
                continue;
            }

            $annotationClass = $this->_getAnnotationInstance(
                self::ALIAS_CLASS,
                $propertyAnnotations[self::ALIAS_CLASS][0]
            );
            $aliasClass = $annotationClass->value;
            if (!isset($annotations[$aliasClass]) ||
                !class_exists($aliasClass) && !interface_exists($aliasClass)) {
                throw new RuntimeException(
                    "The annotation[{$annotationName}] not need alias"
                );
            }

            unset($propertyAnnotations[self::ALIAS_CLASS]);
            foreach ($annotations[$aliasClass] as &$params) {
                $params['construct'] = false;
                if (count($params['params']) < 1) {
                    $params['params'][$propertyName] = $annotationObject->{$propertyName};
                    continue;
                }

                foreach ($params['params'] as &$param) {
                    $param[$propertyName] = $annotationObject->{$propertyName};
                }
            }

            unset($params, $param);
        }

        // 去除已经存在的注解
        foreach ($annotations as $name => $params) {
            if (isset($this->_currentAnntations[$name])) {
                unset($annotations[$name]);
                continue;
            }

            $this->_currentAnntations[$name] = $params;
        }

        $this->_parseAnnotationInstance($annotations, Declared::CUSTOMIZE_ANNOTATION, false);
        unset($annotations);
        return;
    }

    /**
     * 检查注解是不是有效
     * 1 是否为注解
     * 2 申明可用地方
     * 3 是否可重复
     */
    private function _checkAnnotationValid(
        string $annotationName,
        bool $repeat = false,
        string $type = Declared::TYPE): bool
    {
        $annotation = $this->_resource->getReflectionClass($annotationName);
        $annotations = $this->_getDefinedClassAnnotations($annotation);
        if (!isset($annotations[self::ANNOTATION_CLASS])) {
            throw new RuntimeException(
                "The class[{$annotationName}] is not a annotation on {$this->_currentInfo}"
            );
        }

        if (!isset($annotations[self::TARGET_CLASS])) {
            throw new RuntimeException(
                "The annotation[{$annotationName}] is can't be declared on {$this->_currentInfo}"
            );
        }
        
        $annotationClass = $this->_getAnnotationInstance(
            self::TARGET_CLASS,
            $annotations[self::TARGET_CLASS][0]
        );
        if (!($annotationClass->value & $type)) {
            throw new RuntimeException(
                "The annotation[{$annotationName}] is can't be declared on {$this->_currentInfo}"
            );
        }

        if ($repeat && !isset($annotations[self::REPEAT_CLASS])) {
            throw new RuntimeException(
                "The annotation[{$annotationName}] is multi declared in {$this->_currentInfo}"
            );
        }

        return true;
    }

    protected function _parseDoc(string $doc): array
    {
    	$annotations = [];
    	$preg = '/@((\\\\?[a-zA-Z]+)+)\((.*)\)/';
    	preg_match_all($preg, $doc, $matches);
    	if (empty($matches) || empty($matches[1])) {
    		return $annotations;
    	}

    	$tmpAnnotations = $matches[1];
    	$params = $matches[3];
    	foreach ($tmpAnnotations as $index => $annotation) {
            $isFind = true;
            $tmpNamespace = '';
    		if (strpos($annotation, '\\') !== 0) {
                $isFind = false;
    			foreach ($this->_resource->getAnnotationNamespaces() as $namespace => $flag) {
                    unset($flag);
    				$annotation = $namespace . $annotation;
                    if (!class_exists($annotation) &&
                        !interface_exists($annotation)) {
                        continue;
                    }

                    $tmpNamespace = $namespace;
                    unset($namespace);
                    $isFind = true;
                    break;
    			}
    		}

    		if (!$isFind ||
                !class_exists($annotation) &&
                !interface_exists($annotation)) {
    			throw new RuntimeException(
                    "The annotation[$annotation] is not exists in {$this->_currentInfo}"
                );
    		}

            $this->_currentAnnotation = $annotation;
    		$annotationParams = ['construct' => true, 'params' => []];
            $params[$index] = trim($params[$index]);
            if (!empty($params[$index])) {
                $tmpAnnotationParams = explode(',', $params[$index]);
                $isArg = false;
        		foreach ($tmpAnnotationParams as $tmpAnnotationParam) {
        			$param = explode('=', $tmpAnnotationParam);
        			if (count($param) == 1) {
        				$isArg = true;
        				$annotationParams['params'][] = $this->_convert(current($param), $tmpNamespace);
        				continue;
        			}

                    $param[0] = trim($param[0]);
        			if ($isArg ||
        				empty($param[0]) ||
        				array_key_exists($param[0], $annotationParams['params'])
        			) {
        				throw new RuntimeException(
                            "The annotation[{$annotation}] has error param in {$this->_currentInfo}"
                        );
        			}


                    $annotationParams['construct'] = false;
        			$annotationParams['params'][$param[0]] = $this->_convert($param[1], $tmpNamespace);
        		}
            }

    		$annotations[$annotation][] = $annotationParams;
    	}

        return $annotations;
    }

    /**
     * 常用数据转换
     */
    protected function _convert(string $value, string $namespace = '')
    {
    	$value = trim($value);

    	// string
    	if (preg_match('/^\'(.*)\'$/', $value, $match) ||
    		preg_match('/^"(.*)"$/', $value, $match)
    	) {
    		return $match[1];
    	}

    	// number
    	if (is_numeric($value)) {
    		return intval($value);
    	}

    	if (array_key_exists($value, $this->_valueType)) {
    		return $this->_valueType[$value];
    	}

    	// class name
    	if (preg_match('/^((\\\\?[a-zA-Z]+)+)::class$/', $value, $match)) {
    		if (class_exists($match[1]) || interface_exists($match[1])) {
    			return $match[1];
    		}
    	}

        // class property
        if (preg_match('/^((\\\\?[a-zA-Z]+)+)::(\\$?)([a-zA-Z_]+)$/', $value, $match)) {
            $className = $match[1];
            $isConst = empty($match[3]);
            $isFind = true;
            if (strpos($className, '\\') !== 0) {
                $isFind = false;
                foreach ([$namespace, self::ANNOTATIONS_NAMESPACE] as $prefix) {
                    $className = "{$namespace}{$className}";
                    if (!class_exists($className)) {
                        continue;
                    }

                    $isFind = true;
                    break;
                }
            }

            if (!$isFind || !class_exists($className)) {
                throw new RuntimeException(
                    "The annotation[{$this->_currentAnnotation}] has error param[{$match[1]}] in {$this->_currentInfo}"
                );
            }

            $classInstance = $this->_resource->getReflectionClass($className);
            if ($isConst && !$classInstance->hasConstant($match[4]) ||
                !$isConst && !$classInstance->hasProperty($match[4])) {
                throw new RuntimeException(
                    "The annotation[{$this->_currentAnnotation}] has error param[{$match[3]}] in {$this->_currentInfo}"
                );
            }

            if ($isConst) {
                return $classInstance->getConstant($match[4]);
            }

            return $classInstance->getProperty($match[4])->getValue();
        }

    	// number index array
    	if (preg_match('/^\\{(.*)\\}$/', $value, $match)) {
    		$arr = explode(';', $match[1]);
    		foreach ($arr as $key => $value) {
    			$arr[$key] = $this->_convert($value, $namespace);
    		}

    		return $arr;
    	}

    	throw new RuntimeException(
            "The annotation[{$this->_currentAnnotation}] has error param in {$this->_currentInfo}"
        );
    }
}