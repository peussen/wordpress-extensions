<?php
/*
 * @author: petereussen
 * @package: darionwm
 */

namespace HarperJones\Wordpress\Iterator;

/**
 * Template Iterator for arrays or arrays of posts
 *
 * @see loop()
 * @package HarperJones\Wordpress\Iterator
 */
class ArrayIterator extends AbstractTemplateIterator
{
  protected $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }


  public function apply($template, $variation = '')
  {
    if (empty($this->data)) {
      return;
    }

    $this->prepareLoop();

    foreach( $this->data as $postEntry ) {
      $this->prepareEntry($postEntry);

      $this->eachApply('get_template_part',[$template,$variation]);
    }

    wp_reset_postdata();
  }

  public function each($callable, $args = [])
  {
    if (empty($this->data)) {
      return;
    }

    $this->prepareLoop();

    foreach( $this->data as $postEntry ) {
      $this->prepareEntry($postEntry);

      $loopArgs = $args;
      array_unshift($loopArgs,$postEntry);
      $this->eachApply($callable,$loopArgs);
    }

    $this->endLoop();
  }

  protected function prepareLoop()
  {
    reset($this->data);
    parent::prepareLoop();
  }

}