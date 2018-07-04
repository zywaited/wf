<?php

namespace Wf\Core;

use Wf\Cli\Project;
use Wf\Mime;

class Config
{
    const CACHE_NAME = 'app_config_inc';
    const CONFIG_NAME = 'app_config';
    
    /**
     * ini文件分隔符
     * @var string
     */
    protected $_separator = '.';
    
    /**
     * @param string $key
     * @return ConfigStore
     */
    public function getAllConfigs(): ConfigStore
    {
        $configs = [];
        if (APP_ENV == 'production') {
            // 判断缓存文件是否存在
            $cacheConfigName = APP_CACHE_PATH . DIRECTORY_SEPARATOR . self::CACHE_NAME .
                Mime::SEPARATOR . Mime::SERIALIZE;
            $configs = $this->_loadConfig($cacheConfigName);
        }
    
        if (!empty($configs)) {
            return $configs;
        }

        $configs = $this->_loadNeedConfig(
            APP_USER_CONFIG_PATH . DIRECTORY_SEPARATOR . self::CONFIG_NAME
        );
        $configStore = new ConfigStore($configs);   
        if (APP_ENV == 'production') {
            Functions::cache($cacheConfigName, $configStore);
        }
        
        return $configStore;
    }
    
    /**
     * @param $configPreName
     * @return array
     */
    protected function _loadNeedConfig($configPreName): array
    {
        $types = [Mime::INI, Mime::PHP];
        foreach ($types as $type) {
            $configs = $this->_loadConfig($configPreName . Mime::SEPARATOR . $type, $type);
            if (!empty($configs)) {
                return $configs;
            }
        }

        return [];
    }
    
    protected function _loadConfig($configName, $type = Mime::SERIALIZE)
    {
        if (file_exists($configName)) {
            switch ($type) {
                case Mime::INI:
                    return $this->_parseArr(parse_ini_file($configName, true));
                case Mime::PHP:
                    return Functions::load($configName);
                default:
                    return Functions::load($configName, true, false);
            }
        }

        return [];
    }
    
    /**
     * @param $loadConfig
     * @param $sectionName
     * @param array $config
     * @return array|mixed
     * @throws \Exception
     */
    protected function _processSection($loadConfig, $sectionName, array $config = [])
    {
        $iniArr = $loadConfig[$sectionName];
        
        foreach ($iniArr as $key => $value) {
            $config = $this->_processKey($config, $key, $value);
        }

        return $config;
    }
    
    /**
     * @param $config
     * @param $key
     * @param $value
     * @return mixed
     * @throws RuntimeException
     */
    protected function _processKey($config, $key, $value)
    {
        if (strpos($key, $this->_separator) !== false) {
            $pieces = explode($this->_separator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if (!isset($config[$pieces[0]])) {
                    $config[$pieces[0]] = [];
                } elseif (!is_array($config[$pieces[0]])) {
                    throw new RuntimeException('The config parse error');
                }
                $config[$pieces[0]] = $this->_processKey($config[$pieces[0]], $pieces[1], $value);
            } else {
                throw new RuntimeException('The config parse error');
            }
        } else {
            $config[$key] = $value;
        }

        return $config;
    }
    
    /**
     * @param $firstArr
     * @param $secondArr
     * @return array
     */
    protected function _arrayMergeRecursive($firstArr, $secondArr)
    {
        if (is_array($firstArr) && is_array($secondArr)) {
            foreach ($secondArr as $key => $value) {
                if (isset($firstArr[$key])) {
                    $firstArr[$key] = $this->_arrayMergeRecursive($firstArr[$key], $value);
                } else {
                    $firstArr[$key] = $value;
                }
            }
        } else {
            $firstArr = $secondArr;
        }

        return $firstArr;
    }
    
    /**
     * @param array $arr
     * @return array
     * @throws \Exception
     */
    protected function _parseArr(array $arr): array
    {
        $initArr = [];
        foreach ($arr as $sectionName => $sectionData) {
            if (!is_array($sectionData)) {
                $initArr = $this->_arrayMergeRecursive($initArr, $this->_processKey([], $sectionName, $sectionData));
            } else {
                $initArr[$sectionName] = $this->_processSection($arr, $sectionName);
            }
        }

        return $initArr;
    }
}