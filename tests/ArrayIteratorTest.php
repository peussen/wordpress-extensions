<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace {
  function get_template_part()
  {
    echo "Success";
  }
}

namespace HarperJones\Wordpress\Tests {

  use HarperJones\Wordpress\Iterator;
  use phpmock\phpunit\PHPMock;

  class ArrayIteratorTest extends \PHPUnit_Framework_TestCase
  {
    use PHPMock;

    static public $functions;

    public function testWithNoDataAndWithoutDebug()
    {
      $iterator = new Iterator\ArrayIterator([]);

      ob_start();
      $iterator->apply('test');
      $content = ob_get_clean();

      $this->assertEmpty($content);

      ob_start();
      $iterator->each(function($item) { echo "Called"; });
      $content = ob_get_clean();

      $this->assertEmpty($content);
    }

    public function testTemplateWithOneRow()
    {
      $iterator = new Iterator\ArrayIterator([1]);

      $gpMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'get_post');
      $gpMock->expects($this->once())->with(1);

      $sqvMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'set_query_var');
      $sqvMock->expects($this->once())->with('loop_entry');

      $rpdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator','wp_reset_postdata');
      $rpdMock->expects($this->once());

      ob_start();
      $iterator->apply('templates/test');
      $content = ob_get_clean();

      $this->assertNotEmpty($content);
      $this->assertEquals('Success', $content);
    }

    public function testCallableWithOneRow()
    {
      $iterator = new Iterator\ArrayIterator([1]);

      $gpMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'get_post');
      $gpMock->expects($this->once())->with(1)->willReturn(1);

      $spdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'setup_postdata');
      $spdMock->expects($this->once());

      $sqvMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'set_query_var');
      $sqvMock->expects($this->once())->with('loop_entry');

      $rpdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator','wp_reset_postdata');
      $rpdMock->expects($this->once());

      ob_start();
      $iterator->each(function($item) {
        echo "Success";
      });
      $content = ob_get_clean();

      $this->assertEquals('Success', $content);

    }

    public function testCallableWithMoreRows()
    {
      $iterator = new Iterator\ArrayIterator([1,2,3,4,5,6,7,8,9,10]);

      $gpMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'get_post');
      $gpMock->expects($this->exactly(10))->willReturn(1);

      $spdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'setup_postdata');
      $spdMock->expects($this->exactly(10));

      $sqvMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'set_query_var');
      $sqvMock->expects($this->exactly(10))->with('loop_entry');

      $rpdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator','wp_reset_postdata');
      $rpdMock->expects($this->once());

      ob_start();
      $iterator->each(function($item) {
        echo "Success";
      });
      $content = ob_get_clean();

      $this->assertEquals(str_repeat('Success',10), $content);

    }

    public function testTemplateWithMoreRows()
    {
      $iterator = new Iterator\ArrayIterator([1,2,3,4,5,6,7,8,9,10]);

      $gpMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'get_post');
      $gpMock->expects($this->exactly(10))->willReturn(1);

      $spdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'setup_postdata');
      $spdMock->expects($this->exactly(10));

      $sqvMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator', 'set_query_var');
      $sqvMock->expects($this->exactly(10))->with('loop_entry');

      $rpdMock = $this->getFunctionMock('\\HarperJones\\Wordpress\\Iterator','wp_reset_postdata');
      $rpdMock->expects($this->once());

      ob_start();
      $iterator->apply('templates/test');
      $content = ob_get_clean();

      $this->assertEquals(str_repeat('Success',10), $content);

    }

  }

}
