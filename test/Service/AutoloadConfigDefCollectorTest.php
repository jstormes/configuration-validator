<?php
use ConfigurationValidator\Service\AutoloadConfigDefCollector;
use Composer\Autoload\ClassLoader;

class AutoloadConfigDefCollectorTest extends BaseTestCase
{
    public function setUp() {
        $this->classLoader = $this->getMockBuilder(ClassLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPrefixesPsr4', 'getClassMap'])
            ->getMock();
    }

    public function testCollect() {
        $svc = $this->getMockBuilder(AutoloadConfigDefCollector::class)
            ->setConstructorArgs([$this->classLoader])
            ->setMethods(['drilldown'])
            ->getMock();
        $this->classLoader->expects($this->at(0))->method('getPrefixesPsr4')->willReturn(['dir1A', 'dir1B']);
        $this->classLoader->expects($this->at(1))->method('getClassMap')->willReturn(['dir2A', 'dir2B']);
        $svc->expects($this->at(0))->method('drilldown')->with('dir1A');
        $svc->expects($this->at(1))->method('drilldown')->with('dir1B');
        $svc->expects($this->at(2))->method('drilldown')->with('dir2A');
        $svc->expects($this->at(3))->method('drilldown')->with('dir2B');
        $svc->collect();
    } 

    public function testDrilldown() {
        $svc = $this->getMockBuilder(AutoloadConfigDefCollector::class)
            ->setConstructorArgs([$this->classLoader])
            ->setMethods(['checkDirForConfigYaml'])
            ->getMock();

        $results = [];
        $tempDir = sys_get_temp_dir();
        $tempDirA = $tempDir . '/autoloadTestA';
        $tempDirB = $tempDir . '/autoloadTestB';
        try {
            if(! is_dir($tempDirA)) {
                mkdir($tempDirA);
            }
            if(! is_dir($tempDirB)) {
                mkdir($tempDirB);
            }

            $svc->method('checkDirForConfigYaml')
                ->will($this->returnCallback(function($t) use ($svc) {
                    $r = $this->getProperty($svc, 'configData');
                    $r[] = $t;
                    $this->setProperty($svc, 'configData', $r);
                }));
            $this->callMethod($svc, 'drilldown', [[$tempDir, $tempDirA, $tempDirB]]);
            $results = $this->getProperty($svc, 'configData');
            $this->assertEquals(realpath($tempDir), realpath($results[0]));
            $this->assertEquals(realpath($tempDirA), realpath($results[1]));
            $this->assertEquals(realpath($tempDirB), realpath($results[2]));
        } finally {
            rmdir($tempDirA);
            rmdir($tempDirB);
        }
    }

    public function testReadYamlFileValid() {
        $tempFile = sys_get_temp_dir() . '/bogus.yaml';
        try {
            file_put_contents($tempFile, "test1:\r\n  test1A: 123\r\n  test1B: 456\r\ntest2:");
            $svc = new AutoloadConfigDefCollector($this->classLoader);
            $yaml = $this->callMethod($svc, "readYamlFile", [$tempFile]);
            $this->assertEquals("test1", array_keys($yaml)[0]);
            $this->assertEquals("123", $yaml["test1"]["test1A"]);
            $this->assertEquals("456", $yaml["test1"]["test1B"]);
            $this->assertEquals("test2", array_keys($yaml)[1]);
        } finally {
            unlink($tempFile);
        }
    }

    public function testCheckDirForConfigYaml() {
        $tempDir1 = sys_get_temp_dir() . '/testCheckDir1';
        $tempDir2 = sys_get_temp_dir() . '/testCheckDir2';
        $tempFile1 = $tempDir1 . '/config-definition.yaml';
        $tempFile2 = $tempDir2 . '/config-definition.yml';
        try {
            mkdir($tempDir1);
            mkdir($tempDir2);
            file_put_contents($tempFile1, "test1:\r\n  test1A: 123\r\n  test1B: 456\r\ntest2:");
            file_put_contents($tempFile2, "test1:\r\n  test1C: 123\r\n  test1D: 456\r\ntest2:\r\n  test2A: 123");
            $svc = new AutoloadConfigDefCollector($this->classLoader);
            $results = [];
            $this->callMethod($svc, "checkDirForConfigYaml", [$tempDir1]);
            $this->callMethod($svc, "checkDirForConfigYaml", [$tempDir2]);
            $results = $this->getProperty($svc, 'configData');
            $this->assertEquals("test1", array_keys($results)[0]);
            $this->assertEquals("123", $results["test1"]["test1A"]);
            $this->assertEquals("456", $results["test1"]["test1B"]);
            $this->assertEquals("123", $results["test1"]["test1C"]);
            $this->assertEquals("456", $results["test1"]["test1D"]);
            $this->assertEquals("test2", array_keys($results)[1]);
            $this->assertEquals("123", $results["test2"]["test2A"]);            
        } finally {
            unlink($tempFile1);
            unlink($tempFile2);
            rmdir($tempDir1);
            rmdir($tempDir2);
        }
    }
    
    public function testReadYamlFileInvalid() {
        $tempFile = sys_get_temp_dir() . '/bogus.yaml';
        try {
            file_put_contents($tempFile, "");
            $svc = new AutoloadConfigDefCollector($this->classLoader);
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("$tempFile is not a valid YAML file");
            $this->callMethod($svc, "readYamlFile", [$tempFile]);
        } finally {
            unlink($tempFile);
        }
    }

    public function testReadYamlFileMissing() {
        $svc = new AutoloadConfigDefCollector($this->classLoader);
        $yaml = $this->callmethod($svc, "readYamlFile", ["bogus"]);
        $this->assertEquals(null, $yaml);
    }
}
