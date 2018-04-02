<?php
use ConfigurationValidator\Service\ZendModuleConfigCollector;

class ZendModuleConfigCollectorTest extends BaseTestCase
{
    public function testZendModuleConfigCollector() {
        $tempDir = sys_get_temp_dir() . '/testZendDir';
        $tempFile1 = $tempDir . '/foo1.php';
        $tempFile2 = $tempDir . '/foo2.php';
        try {
            mkdir($tempDir);
            file_put_contents($tempFile1, "<?php\r\nreturn ['foo' => ['abc' => '123']];\r\n");
            file_put_contents($tempFile2, "<?php\r\nreturn ['foo' => ['def' => '234']];\r\n");
            
            $svc = new ZendModuleConfigCollector();
            $config = $svc->collect(['module_listener_options' => ['config_glob_paths' => [$tempDir . '/*.php']]]);
            $this->assertEquals('123', $config['foo']['abc']);
            $this->assertEquals('234', $config['foo']['def']);
        } finally {
            unlink($tempFile1);
            unlink($tempFile2);
            rmdir($tempDir);
        }
    }
}
