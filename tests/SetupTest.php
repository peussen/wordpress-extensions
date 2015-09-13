<?php

namespace HarperJones\Wordpress;

use HarperJones\Wordpress\Setup;
Use Mockery as m;

function add_action($a,$b)
{
    return SetupTest::$functions->add_action($a,$b);
}

class sampleClass
{

}

class SetupTest extends \PHPUnit_Framework_TestCase
{
    public static $functions;

    public function setUp()
    {
        self::$functions = m::mock();

    }

    public function tearDown()
    {
        m::close();
    }

    public function testOnRegistrationCallable()
    {
        self::$functions->shouldReceive('add_action')->with('init',[$this,'setupHelper'])->once();

        Setup::on('init',[$this,'setupHelper']);
    }

    public function testOnClassInit()
    {
        self::$functions->shouldReceive('add_action')->with('init',m::type('callable'))->once();

        Setup::on('init',__NAMESPACE__ . '\\sampleClass');
    }

    public function testDenamespace()
    {
        $this->assertEquals('sampleClass',Setup::deNamespace(__NAMESPACE__ . '\\sampleClass'));
        $this->assertEquals('sampleClass',Setup::deNamespace(new sampleClass()));

    }

    public function testNamespace()
    {
        $this->assertEquals('HarperJones\\Wordpress\\',Setup::getNamespace(__NAMESPACE__ . '\\sampleClass'));
        $this->assertEquals('HarperJones\\Wordpress\\',Setup::getNamespace(new sampleClass()));
    }

    public function setupHelper()
    {

    }
}