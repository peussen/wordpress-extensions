<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Iterator;

/**
 * Template iterator for Iterator implementations
 *
 * @package HarperJones\Wordpress\Iterator
 */
class IteratorIterator extends AbstractTemplateIterator
{
  protected $iterator;

  public function __construct(\Iterator $iterator)
  {
    $this->iterator = $iterator;
  }

  public function apply($template, $variation = '')
  {
    $this->prepareLoop();

    foreach( $this->iterator as $key => $item ) {
      $this->prepareEntry($item,$key);

      $this->eachApply('get_template_part',[$template,$variation]);
    }

    $this->endLoop();
  }

  public function each($callable, $args = [])
  {
    $this->prepareLoop();

    foreach( $this->iterator as $key => $item ) {
      $this->prepareEntry($item, $key);

      $loopArg = $args;
      array_unshift($loopArg,$item);

      $this->eachApply($callable,$loopArg);
    }

    $this->endLoop();
  }

  protected function prepareLoop()
  {
    $this->iterator->rewind();
    parent::prepareLoop();
  }

}