<?php
namespace ConfigurationValidator\Service;

class Utility {
    /**
     * Determine if the value represents a true/false value,
     * return True if it does, and set the $boolean value
     *
     * @param any $value
     * @param bool $boolean
     * @return bool
     */
    public static function getBoolean($value, &$boolean) {
        if(is_numeric($value)) {
            $boolean = ($value != 0);
            return true;
        }
        if(is_bool($value)) {
            $boolean = ($value == true);
            return true;
        }
        switch(strtolower($value)) {
            case "true":
            case "yes":
            case "y":
                $boolean = true;
                return true;
            case "false":
            case "no":
            case "n":
                $boolean = false;
                return true;
            default:
                return false;
        }
    }
}

