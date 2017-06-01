<?php

namespace Woppe\Wordpress\Permalink;

/*
 * @author: petereussen
 * @package: lakes
 */

/**
 * Factory class to create rewrite rules for permalinks
 *
 * @package Woppe\Wordpress\Permalink
 */
class RewriterFactory
{
  const HASH_STRATEGY = 'Hash';

  const REWRITE_PAGE_ONLY = 'page';
  const REWRITE_POST_ONLY = 'post';
  const REWRITE_ALL       = '*';

  static private $rewriteNS = [ __NAMESPACE__ ];

  static public function addNS($ns)
  {
    self::$rewriteNS[] = $ns;
  }

  /**
   * Create a permalink rewrite instance
   *
   * usage:
   *    `RewriterFactory::create();`
   *    `RewriterFactory::create('post');`
   *    `RewriterFactory::create('Filters::menuPagesOnly','hash','primary-navigation');`
   *
   * @param string $what
   * @param string $strategy
   * @param bool $methodArgs
   * @return null|string
   */
  static public function create($what = 'page', $strategy = 'Hash', $methodArgs = false)
  {
    foreach( self::$rewriteNS as $ns ) {
      $class = $ns . '\\' . $strategy . 'Rewrite';

      if ( class_exists($class) ) {
        $strategy = new $class($what,$methodArgs);
        return $strategy;
      }
    }
    return null;
  }
}