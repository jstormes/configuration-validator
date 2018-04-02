<?php
namespace ConfigurationValidator\Service;

use Exception;
use StdClass;
use is_dir;
use is_file;

class ConfigValidator {
    public const types = ["any", "string", "integer", "number", "boolean", "directory", "file", "url"];

    public function validate(array $configDef, array $config) {
        $warnings = [];
        $this->performValidation('', $configDef, $config, $warnings);
        return $warnings;
    }

    /**
     * Recursively called function to drill down through configuration definition and config,
     * populating warnins for anything that looks wrong
     *
     * @param string $parentKey
     * @param array $configDef
     * @param array $config
     * @param array $warnings
     * @return void
     */
    protected function performValidation(string $parentKey, array $configDef, array $config, array &$warnings) {
        foreach($configDef as $configDefKey => $configDefValue) {
            // Value can be an array, which means there are children, 
            // or an object, whch means we should validate the node
            if(is_array($configDefValue)) {
                $this->validateSection($parentKey, $configDefKey, $configDefValue, $config, $warnings);
            } else {
                $this->validateNode($parentKey, $configDefKey, $configDefValue, $config, $warnings);
            }
        }
    }

    /**
     * Validate a configuration section, recursively validating sections/nodes therein
     *
     * @param string $parentKey
     * @param string $configDefKey
     * @param array $configDefSection
     * @param array $config
     * @param array $warnings
     * @return void
     */
    protected function validateSection(string $parentKey, string $configDefKey, array $configDefSection, 
        array $config, array &$warnings) {
        if(array_key_exists($configDefKey, $config)) {
            $this->performValidation($parentKey . $configDefKey . '/', 
                $configDefSection, $config[$configDefKey], $warnings);
        } else {
            $this->identifyMissingNodes($parentKey . $configDefKey . '/', 
                $configDefSection, $warnings);
        }
    }

    /**
     * Identifiy any required elements/nodes that are missing by virtue of their
     * parent not being defined
     *
     * @param string $parentKey
     * @param array $configDefSection
     * @param array $warnings
     * @return void
     */
    protected function identifyMissingNodes(string $parentKey, array $configDefSection, array &$warnings) {
        foreach($configDefSection as $configDefKey => $configDefValue) {
            if(is_array($configDefValue)) {
                $this->identifyMissingNodes($parentKey . $configDefKey . '/', $configDefValue, $warnings);
            } else if($configDefValue->required) {
                $warnings[] = "Missing element: " . $parentKey . $configDefKey;
            }
        }
    }
        
    /**
     * Validate a configuration node (value)
     *
     * @param string $parentKey
     * @param string $configDefKey
     * @param StdClass $configDefValue
     * @param array $config
     * @param array $warnings
     * @return void
     */
    protected function validateNode(string $parentKey, string $configDefKey, StdClass $configDefValue,
        array $config, array &$warnings) {
        try {
            if(array_key_exists($configDefKey, $config)) {
                $this->validateValue($config[$configDefKey], $configDefValue->type);
            } else {
                if($configDefValue->required) {
                    $warnings[] = "Missing element: " . $parentKey . $configDefKey;
                }
            }
        } catch(Exception $e) {
            $warnings[] = "Invalid element: " . $parentKey. $configDefKey . ' (' . $e->getMessage() . ')';
        }
    }

    /**
     * Validate a value, based upon type; if invalid throw exception
     *
     * @param any $value
     * @param string $type
     * @return void
     */
    protected function validateValue($value, string $type) {
        switch($type) {
            case "any":
                break;
            case "string":
                if((! isset($value)) || (strlen(trim($value)) == 0)) {
                    throw new Exception("value must not be empty");
                }
                break;
            case "number":
            case "integer":
                if(! is_numeric($value)) {
                    throw new Exception("\"$value\" does not appear to be numeric, should be an integer");
                }
                if($type === "integer") {
                    $i = intval($value);
                    $f = floatval($value);
                    if($i != $f) {
                        throw new Exception("\"$value\" appears to have a decimal portion, should be integer");
                    }
                }
                break;
            case "boolean":
                $b = false;
                if(! Utility::getBoolean($value, $b)) {
                    throw new Exception("\"$value\" does not appear to be boolean");
                }
                break;
            case "directory":
                if(! is_dir($value)) {
                    throw new Exception("\"$value\" is not an existing, accessible directory");
                }
                break;
            case "file":
                if(! is_file($value)) {
                    throw new Exception("\"$value\" is not an existing, accessible file");
                }
                break;
            case "url":
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    throw new Exception("\"$value\" does not appear to be a valid URL");
                }
                break;
            default:
                throw new Exception("\"$type\" is not an expected type");
        }
    }
}