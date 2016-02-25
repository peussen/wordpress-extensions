<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 13/02/15
 * Time: 13:50
 */

namespace HarperJones\Wordpress;

use HarperJones\Wordpress\Theme\ACFSupport;
use HarperJones\Wordpress\Theme\Feature\FeatureInterface;

class Setup
{
	const FEATURE_PREFIX = 'harperjones-';

	/**
	 * DI container
	 *
	 * @var \Pimple\Container
	 */
	static private $container = null;

	/**
	 * List of hooked actions
	 *
	 * @var array
	 */
	static private $actions   = [];

	/**
	 * List of loaded features
	 *
	 * @var array
	 */
	static private $features  = [];

	static public function bootstrap()
	{
		if ( did_action('after_setup_theme') ) {
			static::postThemeSetup();
		} else {
			add_action('after_setup_theme', __CLASS__ . '::postThemeSetup',9999);
		}
	}

	/**
	 * Loads theme_support features.
	 *
	 * @action after_setup_theme
	 * @return void
	 */
	static public function postThemeSetup()
	{
		global $_wp_theme_features;

		if ( $_wp_theme_features === null ) {
			return;
		}

		$harperjonesFeatures = array_filter(array_keys($_wp_theme_features),function($key) {
			return substr($key,0,strlen(static::FEATURE_PREFIX)) == static::FEATURE_PREFIX;
		});

		foreach( $harperjonesFeatures as $feature ) {
			static::addFeature($feature,$_wp_theme_features[$feature]);
		}

		if ( defined("WP_CLI") && WP_CLI) {
			\WP_CLI::add_command('harperjones','\\HarperJones\\Wordpress\\Command\\CustomCommand');
		}
	}

	/**
	 * @param $feature
	 * @return HarperJones\Wordpress\Theme\Feature\FeatureInterface
	 */
	static public function addFeature($feature, $options = [])
	{
		$feature = substr($feature,strlen(static::FEATURE_PREFIX));

		if ( !isset(self::$features[$feature])) {
			$class   = __NAMESPACE__ . '\\Theme\\Feature\\' . static::dashedToClass($feature) . 'Feature';

			if ( class_exists($class)) {
				self::$features[$feature] = new $class();
				self::$features[$feature]->register($options);
			}
		}

		return self::$features[$feature];
	}

	/**
	 * On an action do something
	 *
	 * @param $action
	 * @param $callable
	 */
	static public function on($action,$callable)
	{

		if ( !is_callable($callable) && class_exists($callable)) {
			$callable = function() {
				Setup::set(Setup::deNamespace($callable),$callable);
			};
		}

		if ( !is_callable($callable)) {
			throw new \RuntimeException("callable attribute is not actually callable.");
		}

		self::$actions[$action][] = $callable;
		add_action($action,$callable);
	}

	/**
	 * Adds a command to the WP_CLI (when in CLI mode)
	 *
	 * @param $command
	 * @param $callable
	 */
	static public function cli($command, $callable)
	{
		if ( defined('WP_CLI')) {
			\WP_CLI::add_command($command, $callable);
		}
	}

	/**
	 * Directly set a value in the container
	 * When the setting is already there, you will be given an error unless
	 * the $force parameter is set to TRUE
	 *
	 * @param      $attribute
	 * @param      $val
	 * @param bool $force
	 */
	static public function set($attribute,$val,$force = false)
	{
		$container = self::getContainer();

		if ( isset($container[$attribute]) && !$force) {
			throw new \InvalidArgumentException("Attribute $attribute already set in container");
		}

		$container[$attribute] = $val;
	}

	/**
	 * Obtains a value from the container, or returns the default value if it is not
	 *
	 * @param      $attribute
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	static public function get($attribute, $default = null)
	{
		$container = self::getContainer();

		if ( isset($container[$attribute])) {
			return $container[$attribute];
		}
		return $default;
	}

	static public function getContainer()
	{
		if ( self::$container === null ) {
			// Foutief gebruik van een DI container, maar het is gewoon een storage container nu
			self::$container = new \Pimple\Container();
		}
		return self::$container;
	}

	/**
	 * Strips the namespace part of the class
	 *
	 * @param $objectOrClass
	 *
	 * @return string
	 */
	static public function deNamespace($objectOrClass)
	{
		if ( is_object($objectOrClass) ) {
			$class = get_class($objectOrClass);
		} else {
			$class = $objectOrClass;
		}

		$class = trim($class, '\\');
		if ($last_separator = strrpos($class, '\\'))
		{
			$class = substr($class, $last_separator + 1);
		}
		return $class;
	}

	/**
	 * Obtains the namespace part of a class name
	 *
	 * @param $classOrObject
	 *
	 * @return string
	 */
	static public function getNamespace($classOrObject)
	{
		if ( is_object($classOrObject)) {
			$class = get_class($classOrObject);
		} else {
			$class = $classOrObject;
		}

		$class = trim($class, '\\');
		if ($last_separator = strrpos($class, '\\'))
		{
			return substr($class, 0, $last_separator + 1);
		}
		return '';
	}

	/**
	 * Converts static method from a class into global methods (based on the @globalize doc)
	 * Reflects a class and obtains all static public methods, and checks them for the
	 * @globalize tag in the docblock. If it is there, it will create a global function based
	 * on the parameter of the @globalize tag.
	 *
	 * If no parameters was passed with the @globalize tag, it will create one from the
	 * full class name (including namespaces).
	 *
	 * After execution this method will return FALSE if reflection failed and an array with the
	 * created mappings when it succeeded.
	 *
	 * @param $class
	 *
	 * @return array|bool
	 */
	static public function globalizeStaticMethods($class)
	{
		Globalizer::globalizeStaticMethods($class);
	}


	static public function dashedToClass($featureorfilter)
	{
		$entries = explode('-',$featureorfilter);
		foreach( $entries as $i => $val ) {
			$entries[$i] = ucfirst($val);
		}
		return implode('',$entries);
	}
}