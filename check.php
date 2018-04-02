<?php
$autoLoader = require __DIR__ . './vendor/autoload.php';
$zendAppConfig = require "./config/application.config.php";

use ConfigurationValidator\Service\AutoloadConfigDefCollector;
use ConfigurationValidator\Service\ZendModuleConfigCollector;
use ConfigurationValidator\Service\ConfigValidator;

echo getcwd() . PHP_EOL;
$configDefCollector = new AutoloadConfigDefCollector($autoLoader);
$configCollector = new ZendModuleConfigCollector();

$configDef = $configDefCollector->getConfigDef();
$config = $configCollector->collect($zendAppConfig);

$validator = new ConfigValidator();
try {
    $warnings = $validator->validate($configDef, $config);
    if(count($warnings) == 0) {
        echo "Validation successful!";
    } else {
        fputs(STDERR, "One or more configuration problems were identified" . PHP_EOL);
        foreach($warnings as $warning) {
            fputs(STDERR, "- $warning" . PHP_EOL);
        }
    }
} catch(Exception $e) {
    fputs(STDERR, $e->getMessage());
    exit(-1);
}