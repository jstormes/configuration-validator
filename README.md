# Configuration Validator
## Overview
This is an Autoload implemented utility that provides a mechanism to ensure that deployed configuration files meet the requirements of any dependencies.  It is meant to be executed in development or at the time of deployment.

An application and/or its dependencies each define a configuration definition file.  When the Configuration Validation utility is executed, the utility compares the Application Configuration versus and Configuration Definitions inlcuded in dependencies.

Currently, Configuration Definition files are located using directories included Autoload PSR-4 and Classmap directories.  Application Configuration files are retrieved using Zend's module manager convention (module_listener_options/module_paths).  It's envisioned that additional mechanisms to retrieve Configuration Definitions and Application Configurations will be added in the future.  

## Installation and Usage
### Installation
To use this:

1. Include the repo and the require line in your project's composer.json file
2. Add the composer script line

````
{
    "repositories": [
        // ...
        { "type": "vcs", "url": "/Projects/ConfigurationValidator" }
    ],
    "require": {
        // ...
        "configuration-validator": "master"
    },
    "scripts": {
        "config-validate": "config-validate"
    }
}
````

3. Run ```composer install``` (or ```composer update```)
### Usage
From the application's main directory, run ```composer config-validate``` to run the validation.  If everything checks out, a success message will be displayed and the exit code will be zero.

If the validation fails, due to missing or invalid configuration entries, warnings will be displayed and the exit code will be -2.

If the validation cannot run due to a fatal error, the exception message will be displayed and the exit code will be -1.

### CI/CD Integration
During deployment, after installing/updating configuration files, run ```composer config-validate``` to validate the configuration.  Any exit code other than zero should be treated as a failure and stop the deployment.

## Configuration Definitions
### Configuration Definition File Format
Configuration Definition files are in YAML format.  They can be named ```config-definition.yaml``` or ```config-definition.yml```, and located in any directory defined as a PSR-4 or Classmap path in an Autoload project's composer.json.  For example:

````
service1:
   endpoint: url
   credentials:
      key: string
      secret: string
options:
   timeout:
      type: integer
      required: false
   copyright
````

Every leaf in the tree corresponds to a Configuration Item, located with the hiearchy of its parent nodes.  Configuration Items have two properties, *type* and *required*.  By default, Configuration Items can be of *any* type, and are required.  

In the example above, the final Configuration Item, "copyright" can be any value, but must exist in the Application Configuration.  The "timeout" property is not requiredto exist in the Application Configuration file, but must be an integer (whole number) if it is.

For "service1", its endpoint Configuration Item is required and must be a valid URL.  The "key" and "secret" Configuration Items are required to be defined (under "credentials") and be non-empty strings.

For this example, a working configuration file may look something like this:
````
<?php
return [
   'service1' => [
       'endpoint' => 'https://foo.com/service1',
       'credentials' => [
           'key' => 'abc',
           'secret' => 'def'
       ]
   ],
   'options' => [
       'copyright' => '(c) Me 2018'
   ]
]
````
### Configuration Definition Item Types
The following types are currently supported:

* any: can be any value, including empty
* string: can be anything that PHP can render as a string, but not empty
* number: any numeric value
* integer: any whole value (positive or negative) but without a decimal portion
* boolean: any boolean-ish value (true/value, y/n, yes/no)
* url: any well-structured URL 

## Development

### Unit Testing
Unit tests, including code coverage, can be executed by running ```vendor/bin/phpunit``` from the library's main folder.

### Todo's

* Add support for PSR-0
* Add support for other frameworks' Application Configuration file stores (Symphony?)
* Add more aggressive location and/or configuration settings (somewhere) for things like Autoload and Application Configuration files