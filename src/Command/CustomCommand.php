<?php
/*
 * @author: petereussen
 * @package: gfhg2015
 */

namespace HarperJones\Wordpress\Command;

use HarperJones\Wordpress\Globalizer;


/**
 * Handle custom theme functionality
 *
 * @package HarperJones\Wordpress\Command
 */
class CustomCommand extends \WP_CLI_Command
{

  /**
   * Flush custom theme loader caches
   *
   */
  public function flushautoload()
  {
    delete_site_option('hj_autoload-cache-' . wp_get_theme());
    \WP_CLI::success("Ok");
  }

  public function clearglobalize()
  {
    Globalizer::clearCache();
  }

  public function scanglobalize()
  {
    global $root_dir;

    if (empty($root_dir)) {
      $root_dir = '.';
    }
    $loader = require($root_dir . '/vendor/autoload.php');

    $psr4 = $loader->getPrefixesPsr4();
    $themes = dirname(get_template_directory());
    $classes = [];


    foreach ($psr4 as $ns => $paths) {

      foreach ($paths as $path) {
        if (strpos($path, $themes) !== false) {
          $classes = array_merge($classes, $this->scanForClasses($path, $ns));
        }
      }
    }

    foreach ($classes as $file => $class) {
      Globalizer::writeCache($class);
    }
  }

  private function scanForClasses($path, $baseNS)
  {
    $directory = new \RecursiveDirectoryIterator($path);
    $iterator = new \RecursiveIteratorIterator($directory);
    $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
    $classes = [];

    foreach ($regex as $file => $match) {
      $classes[$file] = preg_replace('|[\\\/]+|', '\\', str_replace($path, $baseNS, substr($file, 0, strrpos($file, '.'))));
    }

    return $classes;
  }
}