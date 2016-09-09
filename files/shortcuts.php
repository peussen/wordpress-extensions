<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

/**
 * Loop over an Array of WP_Query instance, and either apply a template or a function to each instance.
 * This function is created to simplify creating "main loops" or "loops" in wordpress, taking away
 * the pains of setting up the postdata and resetting it at the end. It will work for arrays as well
 * as WP_Query instances.
 *
 * You can even iterate over normal arrays if you want with this construct. The outcome will always be
 * a TemplateIteratorInterface compliant instance. So with that you can either apply() a template, or
 * call a function using the each() method. (Examples shown here)
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