<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 04/03/15
 * Time: 12:04
 */

namespace HarperJones\Wordpress\Theme\Addon;


use HarperJones\Wordpress\Theme\Addon;

/**
 * Class DirectoryLoader
 * @package HarperJones\Wordpress\Theme\Addon
 * @deprecated
 */
final class DirectoryLoader implements LoaderInterface
{
	private $addonsDir;

	public function __construct($addonsDir)
	{
		$this->addonsDir = $addonsDir;
	}

	/**
	 * Should load all files and return a list of addons that was loaded
	 * @return array
	 */
	public function autoload()
	{
		if ( defined('WP_ENV') && WP_ENV != 'development') {
			if (file_exists($this->addonsDir . '_autoload.php')) {
				$addons = require($this->addonsDir . '_autoload.php');
				return $addons;
			}
		}

		$directory_addons = glob($this->addonsDir . '/*',GLOB_ONLYDIR|GLOB_NOSORT);
		$addons           = array();

		foreach( $directory_addons as $loadable ) {
			$addon = addon::readaddonDefinition($loadable);

			if ( $addon ) {
				$name = (isset($addon->namespace) ? rtrim($addon->namespace,"\\") . "\\" : '') . $addon->name;
				$addons[$name] = isset($addon->file) ? $loadable . '/' . $addon->file : $loadable;
			}
		}

		if ( defined('WP_ENV') && WP_ENV === 'development') {
			if ( file_exists($this->addonsDir . '_autoload.php')) {
				$oldSetup = require($this->addonsDir . '_autoload.php');
			} else {
				$oldSetup = array();
			}

			if ( array_diff_assoc($addons,$oldSetup)) {
				file_put_contents($this->addonsDir . '/_autoload.php','<?php return ' . var_export($addons,true) . ';');
			}
		}

		return $addons;
	}

}