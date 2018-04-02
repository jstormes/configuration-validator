<?php
use ConfigurationValidator\Service\ConfigDefCollector;

class ConfigDefCollectorTest extends BaseTestCase
{
    public function setUp() {
        $this->yaml = [
            "aws" => [
                "version" => [
                    "required" => false
                ],
                "region" => [
                    "required" => true
                ],
                "credentials" => [
                    "key" => [
                        "type" => "string"
                    ],
                    "secret" => "string"
                ]
            ],
            "app" => [
                "janus" => [
                    "url" => "url"
                ],
                "folder" => false
            ]
        ];

        $this->svc = $this->getMockBuilder(ConfigDefCollector::class)
            ->setMethods(['collect'])
            ->getMock();
    }

    public function testGetConfigDef() {
        $svc = $this->getMockBuilder(ConfigDefCollector::class)
            ->setMethods(['collect', 'format'])
            ->getMock();
        $svc->expects($this->at(0))->method('collect');
        $svc->expects($this->at(1))->method('format')->will($this->returnCallback(function() use ($svc) {
            $this->setProperty($svc, 'configDef', ['foo']);
        }));
        $this->assertEquals(['foo'], $svc->getConfigDef());
    }

    public function testFormatterValid() {
        $this->setProperty($this->svc, "configData", $this->yaml);
        $this->svc->format();
        $config = $this->getProperty($this->svc, "configDef", $this->yaml);
        $this->assertEquals('any', $config['aws']['version']->type);
        $this->assertEquals(false, $config['aws']['version']->required);
        $this->assertEquals('any', $config['aws']['region']->type);
        $this->assertEquals(true, $config['aws']['region']->required);
        $this->assertEquals('string', $config['aws']['credentials']['key']->type);
        $this->assertEquals(true, $config['aws']['credentials']['key']->required);
        $this->assertEquals('string', $config['aws']['credentials']['secret']->type);
        $this->assertEquals(true, $config['aws']['credentials']['secret']->required);
        $this->assertEquals('url', $config['app']['janus']['url']->type);
        $this->assertEquals(true, $config['app']['janus']['url']->required);
        $this->assertEquals('any', $config['app']['folder']->type);
        $this->assertEquals(false, $config['app']['folder']->required);
    }

    public function testFormatterBadType() {
        $this->yaml['aws']['version']['type']= 'BOGUS';
        $this->setProperty($this->svc, "configData", $this->yaml);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid type \"BOGUS\" specified for aws/version");
        $this->svc->format();
    }

    public function testFormatterBadRequired() {
        $this->yaml['aws']['version']['required']= 'BOGUS';
        $this->setProperty($this->svc, "configData", $this->yaml);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid required value \"BOGUS\" specified for aws/version");
        $this->svc->format();
    }
}
