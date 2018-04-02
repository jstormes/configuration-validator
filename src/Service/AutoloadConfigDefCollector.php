<?php
namespace ConfigurationValidator\Service;

use Composer\Autoload\ClassLoader;
use ConfigurationValidator\Service\Interfaces\ICollector;
use Exception;

class AutoloadConfigDefCollector extends ConfigDefCollector {

    public function __construct(ClassLoader $loader) {
        $this->loader = $loader;
    }

    /**
     * Collects all "raw" confiugration data and returns it
     *
     * @return array
     */
    public function collect() {
        foreach($this->loader->getPrefixesPsr4() as $dir) {
            $this->drilldown($dir);
        }
        foreach($this->loader->getClassMap() as $dir) {
            $this->drilldown($dir);
        }

        return $this->configData;
    }

    /**
     * Recursively make way down through associative array, looking for directory entries
     *
     * @param [type] $item
     * @return void
     */
    protected function drilldown($item) {
        if(is_array($item)) {
            foreach($item as $subitem) {
                $this->drilldown($subitem);
            }
        } else {
            $dir = realpath($item); // We need this for is_dir to work consistently
            if(is_dir($dir)) {
                $this->checkDirForConfigYaml($dir);
            }
        }
    }

    /**
     * Check the specified directory for a configuration file 
     *
     * @param string $dirName
     * @return void
     */
    protected function checkDirForConfigYaml($dirName) {
        for($i = 0; $i < 2; $i++) {
            switch($i) {
                case 0:
                    $path = $dirName . '/config-definition.yaml';
                    break;
                case 1:
                    $path = $dirName . '/config-definition.yml';
                    break;
            }
            $configDef = $this->readYamlFile($path);
            if($configDef) {
                $this->configData = array_merge_recursive($this->configData, $configDef);
                break;
            }
        }
    }

    /**
     * Read a Yaml file
     *
     * @param string $filename
     * @return void
     */
    protected function readYamlFile($filename) {
        if(file_exists($filename)) {
            $config = spyc_load_file($filename);
            if(count($config) == 0) {
                throw new Exception("$filename is not a valid YAML file");
            }
            return $config;
        } else {
            return null;
        }
    }

}