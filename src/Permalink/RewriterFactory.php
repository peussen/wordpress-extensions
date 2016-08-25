<?php

namespace HarperJones\Wordpress\Permalink;

/*
 * @author: petereussen
 * @package: lakes
 */

class RewriterFactory
{
  const HASH_STRATEGY = 'Hash';

  const REWRITE_PAGE_ONLY = 'page';
  const REWRITE_POST_ONLY = 'post';
  const REWRITE_ALL       = '*';


  static public function create($what = 'page', $strategy = 'Hash')
  {
    $class = __NAMESPACE__ . '\\' . $strategy . 'Rewrite';

    if ( class_exists($class) ) {
      $strategy = new $class($what);
    }
  }
}