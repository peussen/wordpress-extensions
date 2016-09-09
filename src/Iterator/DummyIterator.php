<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Iterator;


class DummyIterator implements TemplateIteratorInterface
{
  private $data;

  public function __construct($data)
  {
    $this->data = $data;
  }

  public function apply($template, $variation = '')
  {
    $this->debug();
    return;
  }

  public function each($callable, $args = [])
  {
    $this->debug();
    return;
  }

  public function debug()
  {
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
      throw new \RuntimeException('loop() error: cannot iterator over ' . gettype($this->data));
    }

    if ( defined("WP_DEBUG_LOG") && WP_DEBUG_LOG ) {
      error_log('loop() error: cannot iterator over ' . gettype($this->data));
    }
  }
}