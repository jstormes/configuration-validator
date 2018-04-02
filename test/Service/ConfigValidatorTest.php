<?php
use ConfigurationValidator\Service\ConfigValidator;

class ConfigValidatorTest extends BaseTestCase {
    public function testValidate() {
        $svc = $this->getMockBuilder(ConfigValidator::class)
            ->setMethods(['performValidation'])
            ->getMock();

        $svc->method('performValidation')->will($this->returnCallback(
            function(string $parentKey, array $configDef, array $config, array &$warnings) { 
                $warnings[] = 'foo';
            }
        ));

        $warnings = $svc->validate([], []);
        $this->assertEquals($warnings[0], 'foo');
    }

    public function testPerformValidation() {
        $svc = $this->getMockBuilder(ConfigValidator::class)
            ->setMethods(['validateSection', 'validateNode'])
            ->getMock();
    
        $svc->method('validateSection')->will($this->returnCallback(
            function(string $parentKey, string $configDefKey, array $configDefSection, array $config, array &$warnings) { 
                $warnings[] = 'foo';
            }
        ));
        $svc->method('validateNode')->will($this->returnCallback(
            function(string $parentKey, string $configDefKey, StdClass $configDefValue, array $config, array &$warnings) { 
                $warnings[] = 'bar';
            }
        ));

        $warnings = [];
        
        $this->callMethod($svc, 'performValidation', ['', ['section1' => [], 'value1' => (object) []], [], &$warnings]);
        $this->assertEquals($warnings[0], 'foo');
        $this->assertEquals($warnings[1], 'bar');
    }

    public function testValidateSectionValid() {
        $svc = $this->getMockBuilder(ConfigValidator::class)
            ->setMethods(['performValidation'])
            ->getMock();
    
        $svc->method('performValidation')->will($this->returnCallback(
            function(string $parentKey, array $configDef, array $config, array &$warnings) { 
                $warnings[] = 'foo';
            }
        ));

        $warnings = [];
        $this->callMethod($svc, 'validateSection', ['', 'section1', ['subsection1' => []],
            ['section1' => ['subsection1' => []]], &$warnings]);

        $this->assertEquals($warnings[0], 'foo');
    }

    public function testValidateSectionInvalid() {
        $svc = new ConfigValidator();
        
        $warnings = [];
        $this->callMethod($svc, 'validateSection', ['', 'section1', 
            ['subsection1' => ['testvalue' => (object) ['required' => true]]],
            [], &$warnings]);

        $this->assertEquals($warnings[0], 'Missing element: section1/subsection1/testvalue');
    }

    public function testValidateNodeValid() {
        $svc = new ConfigValidator();
        
        $warnings = [];
        $this->callMethod($svc, 'validateNode', ['', 'value1', (object) ['required' => true, 'type' => 'any'],
            ['value1' => 'test'], &$warnings]);

        $this->assertEquals(0, count($warnings));
    }

    public function testValidateNodeMissing() {
        $svc = new ConfigValidator();
        
        $warnings = [];
        $this->callMethod($svc, 'validateNode', ['', 'value1', (object) ['required' => true, 'type' => 'any'],
            [], &$warnings]);

        $this->assertEquals('Missing element: value1', $warnings[0]);
    }

    public function testValidateNodeInvalid() {
        $svc = $this->getMockBuilder(ConfigValidator::class)
            ->setMethods(['validateValue'])
            ->getMock();

        $svc->method('validateValue')->will($this->throwException(new Exception('foo')));
                
        $warnings = [];
        $this->callMethod($svc, 'validateNode', ['', 'value1', (object) ['required' => true, 'type' => 'any'],
            ['value1' => 0], &$warnings]);

        $this->assertEquals('Invalid element: value1 (foo)', $warnings[0]);
    }

    public function testValidateValueAny() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [123, 'any']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['', 'any']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [null, 'any']));
    }

    public function testValidateValueString() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [123, 'string']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['123', 'string']));
    }

    public function testValidateValueEmptyString() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('value must not be empty');
        $this->callMethod($svc, 'validateValue', [' ', 'string']);
    }

    public function testValidateValueNullString() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('value must not be empty');
        $this->callMethod($svc, 'validateValue', [null, 'string']);
    }
    
    public function testValidateValueNumber() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [123, 'number']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [123, 'integer']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [-123.1, 'number']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [0, 'integer']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['0', 'number']));
    }

    public function testValidateValueInvalidInteger() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"-123.1" appears to have a decimal portion, should be integer');
        $this->callMethod($svc, 'validateValue', [-123.1, 'integer']);
    }

    public function testValidateValueInvalidNumber() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"abc" does not appear to be numeric');
        $this->callMethod($svc, 'validateValue', ['abc', 'number']);
    }

    public function testValidateValueMissingdNumber() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"" does not appear to be numeric');
        $this->callMethod($svc, 'validateValue', ['', 'number']);
    }

    public function testValidateValueBoolean() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [true, 'boolean']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['true', 'boolean']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', [1, 'boolean']));
    }

    public function testValidateValueBooleanInvalid() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"xxx" does not appear to be boolean');
        $this->callMethod($svc, 'validateValue', ['xxx', 'boolean']);
    }

    public function testValidateValueUrl() {
        $svc = new ConfigValidator();
        // Any of these should be fine, just test to make sure no exceptions are thrown
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['http://www.foo.com/bar', 'url']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['ftps://www.foo.com/bar/', 'url']));
        $this->assertEquals(null, $this->callMethod($svc, 'validateValue', ['https://www.foo.com/bar?abc=123', 'url']));
    }

    public function testValidateValueInvalidUrl() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"xxx" does not appear to be a valid URL');
        $this->callMethod($svc, 'validateValue', ['xxx', 'url']);
    }

    public function testValidateValueInvalidUrl2() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"http:/foo://xxx" does not appear to be a valid URL');
        $this->callMethod($svc, 'validateValue', ['http:/foo://xxx', 'url']);
    }

    public function testValidateValueInvalidUrlNoScheme() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"www.foo.com" does not appear to be a valid URL');
        $this->callMethod($svc, 'validateValue', ['www.foo.com', 'url']);
    }
    
    public function testValidateValueInvalidType() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"xxx" is not an expected type');
        $this->callMethod($svc, 'validateValue', ['xxx', 'xxx']);
    }

    public function testValidateValueDiretoryValid() {
        $svc = new ConfigValidator();
        $this->assertEquals(null, $this->callmethod($svc, 'validateValue', [sys_get_temp_dir(), 'directory']));
    }

    public function testValidateValueDiretoryInvalid() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"::#\\" is not an existing, accessible directory');
        $this->callmethod($svc, 'validateValue', ['::#\\', 'directory']);
    }
    
    public function testValidateValueFileValid() {
        $tmpFile = tempnam(sys_get_temp_dir(), 'testConfigFile');
        try {
            $svc = new ConfigValidator();
            $this->assertEquals(null, $this->callmethod($svc, 'validateValue', [$tmpFile, 'file']));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testValidateValueFileInvalid() {
        $svc = new ConfigValidator();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"::#\\" is not an existing, accessible file');
        $this->callmethod($svc, 'validateValue', ['::#\\', 'file']);
    }
    
}
