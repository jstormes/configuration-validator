<?php
namespace ConfigurationValidator\Service;

use StdClass;
use Exception;

/**
 * This class is the base class for collecting configuration, and then formatting it
 * so it can be used for configuration validation.  For the most part, this process
 * is to make sure we are properly identifying lists of configuration settings versus
 * the properties of those settings ("type" and "required")
 */
abstract class ConfigDefCollector {
    /**
     * Holds the configuration that will be read from files (or wherever)
     *
     * @var array
     */
    protected $configData = [];

    /**
     * Hold the formatted configuration definition
     *
     * @var array
     */
    protected $configDef = [];

    /**
     * Utility function that collects, formats and returns configuration definition
     *
     * @return array
     */
    public function getConfigDef() {
        if(count($this->configDef) == 0) {
            $this->collect();
            $this->format();
        }
        return $this->configDef;
    }
    
    /**
     * Child class must implement this funtion that collects "raw" configuration data
     */
    abstract public function collect();

    /**
     * Format the raw config data into a workable hierarchy,
     * if a node is an array, it will be the parent of other nodes,
     * nodes must have a "type" and a "required" flag
     *
     * @return void
     */
    public function format() {
        $configDef = [];
        foreach($this->configData as $key => $value) {
            $this->formatYamlNode('', $configDef, $key, $value);
        }
        $this->configDef = $configDef;
    }

    /**
     * Formats the YAML node into an array if it has children,
     * or an object if it is a node
     *
     * @param string $parentKey
     * @param string $key
     * @param array  $results
     * @param any    $value
     * @return void
     */
    protected function formatYamlNode($parentKey, &$results, $key, $value) {
        // Determine if the only thing below this level are field properties
        $isTerminal = true;
        $name = null;
        $type = null;
        $required = true;

        // There are two things children can be, properties (i.e. "required" or "type")
        // or cihld nodes.  Only way to tell is to see if the subkeys match what we expect
        if(is_array($value)) {
            foreach($value as $subkey => $subvalue) {
                switch($subkey) {
                    case "required":
                        $r = false;
                        if(Utility::getBoolean($subvalue, $r)) {
                            $required = $r;
                        } else {
                            throw new Exception("Invalid required value \"$subvalue\" specified for " . $parentKey . $key);
                        }
                        $required = $r;
                        $isTerminal = true;
                        break;
                    case "type":
                        if($this->isValidType($subvalue)) {
                            $type = $subvalue;
                            $isTerminal = true;
                        } else {
                            throw new Exception("Invalid type \"$subvalue\" specified for " . $parentKey . $key);
                        }
                        break;
                    default:
                        // If node contains items other than "required" or "type" assume it's a list
                        $isTerminal = false;
                        break;
                }
                if(! $isTerminal) {
                    // If child nodes, then revert properties back to defaults
                    $required = true;
                    $type = null;
                    break;
                }
            }
        }

        // To try and "guess" whether the node we are working with is a value of 
        // an ordinal (# => value) or associative array (key => value)
        $isOrdinal = is_numeric($key);
        $name = $isOrdinal ? $value : $key;
        $hasChildren = is_array($value);
        if($isTerminal) {
            // If this is a terminal value, then return an object
            $result = new StdClass();
            if(! $hasChildren) {
                // Look for inline definitions of type or not required (false)
                if((! isset($type)) && $this->isValidType($value)) {
                    $type = $value;
                } else {
                    $r = false;
                    if(Utility::getBoolean($value, $r)) {
                        $required = $r;
                    }
                }
            }
            $result->required = $required;
            $result->type = isset($type) ? $type : "any";
            $results[$name] = $result;
        } else {
            // If we are here, then value be children
            $children = [];
            foreach($value as $subkey => $subvalue) {
                $this->formatYamlNode($parentKey . $key . '/', $children, $subkey, $subvalue);
            }
            if(count($children) > 0) {
                $results[$name] = $children;
            }
        }
    }

    /**
     * Returns True if the passed value is a valid type indicator
     *
     * @param string $type
     * @return boolean
     */
    protected function isValidType($type) {
        return in_array($type, ConfigValidator::types);
    }
}