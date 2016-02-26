<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 04/03/15
 * Time: 11:59
 */

namespace HarperJones\Wordpress\Theme\Addon;
use HarperJones\Wordpress\Setup;
use HarperJones\Wordpress\Theme\Addon;
use HarperJones\Wordpress\Theme\Addon\AssetBundler;
use HarperJones\Wordpress\Theme\Theme;

/**
 * Management class for loading all Theme addons
 *
 * @package HarperJones\Wordpress\Theme\Addon
 * @deprecated
 */
class Manager
{
	protected $themeDir;
	protected $addonDir;
	protected $addons;

	/**
	 * @var LoaderInterface
	 */
	protected $loader;

	/**
	 * List of all registered themes
	 *
	 * @var array
	 */
	protected $registry = array();

	/**
	 *
	 * @var HarperJones/Wordpress/Theme/Addon/AssetBundler
	 */
	protected $assets;

	public function __construct($addonDir = 'addons/', $autoload = true)
	{
		$this->themeDir  = rtrim(get_template_directory(),'/') . '/';
		$this->addonDir  = rtrim($addonDir,'/') . '/';
		$this->assets    = new AssetBundler();

		$this->registerAutoload(new DirectoryLoader($this->getaddonDirectory()));

		if ( $autoload ) {
			add_action('after_setup_theme',array($this,'loadaddons'));
		}
	}

	public function getaddonDirectory()
	{
		return $this->themeDir . $this->addonDir;
	}

	/**
	 * Override/Set the autoloader class
	 *
	 * @param LoaderInterface $method
	 */
	public function registerAutoload(LoaderInterface $method)
	{
		$this->loader = $method;
	}

	public function loadaddons()
	{
		$loader     = require(dirname(dirname(ABSPATH)) . '/vendor/autoload.php');
		$addons    = $this->loader->autoload();

		foreach( $addons as $addon => $directory ) {

			if ( is_dir($directory)) {
				$ns = Setup::getNamespace($addon);

				$loader->addPsr4($ns, $directory . '/src/');
				$class = $addon;
				$theme = new $class($addon,$directory);
				$this->registry[$addon] = $theme;

				// To show some grace to front-end developers who are used to wordpress way of
				// thinking we create "global" functions for static methods;
				Setup::globalizeStaticMethods($class);

				// Handle assets
				$this->installAssets($directory);
			} else {
				// If the addon points to a file, we just include the file. The addon has to
				// handle all registrations itself
				$this->registry[$addon] = require_once($directory);
			}
		}

		do_action('theme_addons_loaded');
	}

	protected function installAssets($directory)
	{
		$definition = addon::readaddonDefinition($directory);

		$directory = rtrim($directory,'/') . '/';

		if ( isset($definition->install)) {
			foreach( $definition->install as $package) {
				if (strpos('http',$package->src) === false ) {
					$package->src = Theme::makeRelativePath($directory . $package->src);

					$this->assets->add($package);
				}
			}
		}
	}

}