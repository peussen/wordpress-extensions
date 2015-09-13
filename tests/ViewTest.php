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
    if ( $template === 'templates/doesnotexist.php' ) {
        return '';
    }
    return __DIR__ . '/' . $template;
}

/**
 * Class ViewTest
 * @package HarperJones\Wordpress\Theme
 * @runTestsInSeparateProcesses
 */
class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function testCreation()
    {
        $this->setExpectedException('HarperJones\\Wordpress\\WordpressException');
        new View('sometemplate');
    }

    public function testCreationNonExistingTemplate()
    {
        define('ABSPATH', __DIR__);
        $this->setExpectedException('HarperJones\\Wordpress\\Theme\\InvalidTemplate');
        new View('doesnotexist');

    }

    public function testCreationExistingTemplate()
    {
        define('ABSPATH', __DIR__);
        $view = new View('exist');
        $this->assertEquals('HarperJones\\Wordpress\\Theme\\View',get_class($view));
    }

    public function testAttributeSetAndGet()
    {
        define('ABSPATH', __DIR__);
        $view = new View('exist');
        $view->set('foo','bar');

        $this->assertEquals('bar',$view->get('foo'));
    }

    public function testAttributeSetThroughConstructor()
    {
        define('ABSPATH', __DIR__);
        $view = new View('exists', ['foo' => 'bar']);

        $this->assertEquals('bar',$view->get('foo'));
    }

    public function testAttributeInheritance()
    {
        define('ABSPATH', __DIR__);
        $view = new View('inherit', ['foo' => 'bar']);

        $this->assertEquals('{"foo":"bar"}',$view->render());
    }

}