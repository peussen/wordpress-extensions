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
 * @param \Iterator|\WP_Query|array $queryOrArray
 * @return \HarperJones\Wordpress\Iterator\TemplateIteratorInterface
 */
function loop($queryOrArray)
{
  if ( is_array($queryOrArray)) {
    $iterator = new \HarperJones\Wordpress\Iterator\ArrayIterator($queryOrArray);
  } elseif ( $queryOrArray instanceof \WP_Query ) {
    $iterator = new \HarperJones\Wordpress\Iterator\WPQueryIterator($queryOrArray);
  } elseif (is_object($queryOrArray) && $queryOrArray instanceof \Iterator ) {
    $iterator = new \HarperJones\Wordpress\Iterator\IteratorIterator($queryOrArray);
  } else {
    $iterator = new \HarperJones\Wordpress\Iterator\DummyIterator($queryOrArray);
  }

  return $iterator;
}

/**
 * Safely return a value from an array
 *
 * @see \HarperJones\Wordpress\Arr::value()
 * @param array       $array
 * @param string|int  $key
 * @param mixed       $default (default null)
 * @return mixed
 */
function array_value($array,$key,$default = null)
{
  return \HarperJones\Wordpress\Arr::value($array,$key,$default);
}


/**
 * Adds an element as first element in an associative array
 *
 * @see \HarperJones\Wordpress\Arr::prependAssoc()
 * @param array   $array
 * @param string  $key
 * @param mixed   $value
 * @return array
 */
function array_unshift_assoc(array $array, $key, $value)
{
  return \HarperJones\Wordpress\Arr::prependAssoc($array,$key,$value);
}


if ( version_compare(PHP_VERSION,'7.0.0')) {
  /**
   * Return the first non-null entry
   *
   * @see \HarperJones\Wordpress\Helper::strictCoalesce()
   * @param array ...$options
   */
  function coalesce(...$options)
  {
    return call_user_func_array('\\HarperJones\\Wordpress\\Helper::strictCoalesce',$options);
  }
}

/**
 * Truncate a string to a specified number of words.
 *
 * @see \HarperJones\Wordpress\String::trunc()
 * @param string $string
 * @param int    $limit
 * @param string $more
 * @return string
 */
function str_trunc($string,$limit = 50,$more = '...')
{
  return \HarperJones\Wordpress\String::trunc($string,$limit,$more);
}


/**
 * Set one or more capabilities for a certain role.
 *
 * @param   string $roleName
 * @param   string|array $capabilities
 * @return  bool
 */
function set_role_capability($roleName,$capabilities)
{
  $role = get_role( $roleName );

  if ( $role === null ) {
    return false;
  }

  foreach ((array)$capabilities as $capability ) {
    $role->add_cap( $capability );
  }

  return true;
}