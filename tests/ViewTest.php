<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme;


use HarperJones\Wordpress\Theme\View;
use HarperJones\Wordpress\WordpressException;
use Mockery as m;

function locate_template($template)
{
    return ViewTest::$functions->locate_template($template);
//    if ( $template === 'templates/doesnotexist.php' ) {
//        return '';
//    }
//    return __DIR__ . '/' . $template;
}

/**
 * Class ViewTest
 * @package HarperJones\Wordpress\Theme
 * @runTestsInSeparateProcesses
 */
class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    static public $functions;

    public function setUp()
    {
        self::$functions = m::mock();
    }

    public function testCreation()
    {
        $this->setExpectedException('HarperJones\\Wordpress\\WordpressException');
        new View('sometemplate');
    }

    public function testCreationNonExistingTemplate()
    {
        define('ABSPATH', __DIR__);
        $this->setExpectedException('HarperJones\\Wordpress\\Theme\\InvalidTemplate');
        self::$functions->shouldReceive('locate_template')->with('templates/doesnotexist.php')->once()->andReturn('');
        new View('doesnotexist');

    }

    public function testCreationExistingTemplate()
    {
        define('ABSPATH', __DIR__);
        self::$functions->
            shouldReceive('locate_template')->
            with('templates/exists.php')->
            once()->
            andReturn(ABSPATH . '/templates/exists.php');
        $view = new View('exists');
        $this->assertEquals('HarperJones\\Wordpress\\Theme\\View',get_class($view));
    }

    public function testAttributeSetAndGet()
    {
        define('ABSPATH', __DIR__);
        self::$functions->
            shouldReceive('locate_template')->
            with('templates/exists.php')->
            once()->
            andReturn(ABSPATH . '/templates/exists.php');
        $view = new View('exists');
        $view->set('foo','bar');

        $this->assertEquals('bar',$view->get('foo'));
    }

    public function testAttributeSetThroughConstructor()
    {
        define('ABSPATH', __DIR__);
        self::$functions->
            shouldReceive('locate_template')->
            with('templates/exists.php')->
            once()->
            andReturn(ABSPATH . '/templates/exists.php');
        $view = new View('exists', ['foo' => 'bar']);

        $this->assertEquals('bar',$view->get('foo'));
    }

    public function testAttributeInheritance()
    {
        define('ABSPATH', __DIR__);
        self::$functions->
            shouldReceive('locate_template')->
            with('templates/inherit.php')->
            once()->
            andReturn(ABSPATH . '/templates/inherit.php');
        self::$functions->
            shouldReceive('locate_template')->
            with('templates/exists.php')->
            once()->
            andReturn(ABSPATH . '/templates/exists.php');
        $view = new View('inherit', ['foo' => 'bar']);

        $this->assertEquals('{"foo":"bar"}',$view->render());
    }

}