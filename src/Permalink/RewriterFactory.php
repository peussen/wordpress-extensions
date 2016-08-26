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

  static private $rewriteNS = [ __NAMESPACE__ ];

  static public function addNS($ns)
  {
    self::$rewriteNS[] = $ns;
  }

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