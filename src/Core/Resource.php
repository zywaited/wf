<?php

namespace Wf\Core;

use Wf\Cli\Project;
use Wf\Mime;

class Resource
{
    private $_definedRefClass = [];

    private $_definedParserClass = [];

    private $_definedProxyClass = [];

	private $_directories = [];

    private $_annotationsNamespaces = [
        Annotation::ANNOTATIONS_NAMESPACE => 1,
    ];

    public const CACHE_NAME = 'class_scan_path';

	public function addClassPath(string $path, string $namespace = ''): Resource
	{
		if (empty($path) || !is_dir($path)) {
			return false;
		}

        if (empty($namespace)) {
            $this->_directories[] = realpath($path);
        } else {
		  $this->_directories[$namespace] = realpath($path);
        }

		return $this;
	}

	public function addAnnotationNamespace(string $namespace): Resource
	{
		$this->_annotationsNamespaces[$namespace] = 1;
		return $this;
	}

    public function getClassDirectories(): array
    {
    	return $this->_directories;
    }

    public function getAnnotationNamespaces(): array
    {
    	return $this->_annotationsNamespaces;
    }

    /**
     * 解析后不需要
     */
    public function clear(): void
    {
    	$this->_definedParserClass = [];
        $this->_definedProxyClass = [];
        $this->_definedRefClass =[];
    }

    /**
     * 获取解析器
     */
    public function getAnnotationParser(string $annotationClassName)
    {
        $hasNamespace = false;
        $isFirst = true;
        while (($pos = strrpos($annotationClassName, "\\")) !== false) {
            if ($isFirst) {
                $className = substr($annotationClassName, $pos + 1);
                $parserClassName = "{$className}Parser";
                $annotationClassName = substr($annotationClassName, 0, $pos);
                $isFirst = false;
                continue;
            }

            $hasNamespace = true;
            $classPrefix = substr($annotationClassName, 0, $pos);
            $parserClassName = "{$classPrefix}\\Parser\\{$parserClassName}";
            break;
        }
        
        if (!$hasNamespace) {
            $parserClassName = "{$annotationClassName}Parser";
        }

        if (!array_key_exists($parserClassName, $this->_definedParserClass) &&
            class_exists($parserClassName)) {
            $this->_definedParserClass[$parserClassName] = new $parserClassName;
        } else {
            $this->_definedParserClass[$parserClassName] = null;
        }

        return $this->_definedParserClass[$parserClassName];
    }

    /**
     * 获取代理生成器
     */
    public function getAnnotationProxy(string $annotationClassName)
    {
        $hasNamespace = false;
        $isFirst = true;
        while (($pos = strrpos($annotationClassName, "\\")) !== false) {
            if ($isFirst) {
                $className = substr($annotationClassName, $pos + 1);
                $proxyClassName = "{$className}Proxy";
                $annotationClassName = substr($annotationClassName, 0, $pos);
                $isFirst = false;
                continue;
            }

            $hasNamespace = true;
            $classPrefix = substr($annotationClassName, 0, $pos);
            $proxyClassName = "{$classPrefix}\\Proxy\\{$parserClassName}";
            break;
        }
        
        if (!$hasNamespace) {
            $proxyClassName = "{$annotationClassName}Proxy";
        }

        if (!array_key_exists($proxyClassName, $this->_definedProxyClass) &&
            class_exists($proxyClassName)) {
            $this->_definedProxyClass[$proxyClassName] = $proxyClassName;
        } else {
            $this->_definedProxyClass[$proxyClassName] = null;
        }

        return $this->_definedProxyClass[$proxyClassName];
    }

    public function getReflectionClass(string $className): \ReflectionClass
    {
        if (!isset($this->_definedRefClass[$className])) {
            $this->_definedRefClass[$className] = new \ReflectionClass($className);
        }

        return $this->_definedRefClass[$className];
    }

    /**
     * 获取所有的定义类
     */
	public function getDefinitions(): array
    {
        $namespaces = Functions::load(
            APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
            Project::AUTOLOAD_NAMESPACES . Mime::SEPARATOR . Mime::PHP
        );
        foreach ($namespaces as $namespace => $path) {
            $this->addClassPath($path, $namespace);
        }

        $paths = Functions::load(
            APP_CONFIG_PATH . DIRECTORY_SEPARATOR . 
            Project::AUTOLOAD_PATH . Mime::SEPARATOR . Mime::PHP
        );
        foreach ($paths as $path) {
            $this->addClassPath($path);
        }
        
    	$definitions = [];
        $scanDirectories = [];
    	foreach ($this->_directories as $namespace => $path) {
            if (is_numeric($namespace)) {
                $namespace = '';
            }

            $dirId = hash('sha256', $path);
            if (isset($scanDirectories[$dirId])) {
                continue;
            }

            $scanDirectories[$dirId] = 1;
    		$directories = [new \DirectoryIterator($path)];
	    	$subLen = strlen($path) + 1;
	    	while (count($directories)) {
	    		$directory = array_pop($directories);
		    	foreach ($directory as $file) {
		    		if ($file->isDot()) {
		    			continue;
		    		}

		    		if ($file->isDir()) {
                        $dirId = hash('sha256', $file->getPathname());
                        if (!isset($scanDirectories[$dirId])) {
                            $scanDirectories[$dirId] = 1;
                            $directories[] = new \DirectoryIterator($file->getPathname());
                        }

		    			continue;
		    		}

		    		$fileName = $file->getPathname();
		    		$info = pathinfo($fileName);
		    		if ($info['extension'] != Mime::PHP) {
		    			continue;
		    		}

		    		$namespaceClass = $namespace .
                        '\\' .
                        str_replace(
		    			   DIRECTORY_SEPARATOR,
		    			   '\\',
		    			   substr($info['dirname'], $subLen) .
                           DIRECTORY_SEPARATOR . $info['filename']
		    		    );
		    		if (!class_exists($namespaceClass) &&
                        !interface_exists($namespaceClass))
                    {
		    			continue;
		    		}

		    		$definitions[] = $namespaceClass;
		    	}
		    }
    	}

	    return $definitions;
    }

    public function getDefinitionsWithCache(): array
    {
        $cacheName = APP_CACHE_PATH . DIRECTORY_SEPARATOR .
            self::CACHE_NAME . Mime::SEPARATOR . Mime::SERIALIZE;
        if (APP_ENV == 'production' && file_exists($cacheName)) {
            return Functions::load($cacheName, true, false);
        }

        $definitions = $this->getDefinitions();
        if (APP_ENV == 'production') {
            Functions::cache($cacheName, $definitions);
        }

        return $definitions;
    }
}