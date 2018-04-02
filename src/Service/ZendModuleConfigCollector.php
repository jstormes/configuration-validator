<?php
namespace ConfigurationValidator\Service;

use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\Interfaces\ICollector;
use Exception;

/**
 * Based upon Zend's module structure, combine all configuration files
 */
class ZendModuleConfigCollector {
    public function collect(array $zendAppConfig) {
        $results = [];
        if(array_key_exists('module_listener_options', $zendAppConfig)) {
            if(array_key_exists('config_glob_paths', $zendAppConfig['module_listener_options'])) {
                foreach($zendAppConfig['module_listener_options']['config_glob_paths'] as $globConfigs) {
                    foreach(glob($globConfigs, GLOB_BRACE) as $globConfig) {
                        if(file_exists($globConfig)) {
                            $results = array_merge_recursive($results, require $globConfig);
                        }
                    }
                }
            }
        }
        return $results;
    }
}