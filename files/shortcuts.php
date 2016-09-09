<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

/**
 * Loop over an Array of WP_Query instance, and either apply a template or a function to each instance
 *
 * Usage: `<?php loop(new WP_Query([...]))->apply('template'); ?>`
 *        `<?php loop(get_posts([...]))->each('my_magic_function',['optional','arguments']); ?>
 *
 * @param $queryOrArray
 * @return \HarperJones\Wordpress\Iterator\TemplateIteratorInterface
 */
function loop($queryOrArray)
{
  if ( is_array($queryOrArray)) {
    $iterator = new \HarperJones\Wordpress\Iterator\ArrayIterator($queryOrArray);
  } elseif ( $queryOrArray instanceof \WP_Query ) {
    $iterator = new \HarperJones\Wordpress\Iterator\WPQueryIterator($queryOrArray);
  }

  return $iterator;
}