<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 04/03/15
 * Time: 11:59
 */

namespace HarperJones\Wordpress\Theme;


/**
 * Skeleton for Theme Addon
 *
 * @package HarperJones\Wordpress\Theme
 * @deprecated
 */
class Addon
{
	/**
	 * The name as defined in the plugin.json file
	 *
	 * @var string
	 */
	private $addonName;

	/**
	 * The directory of the plugin
	 * @var string
	 */
	private $directory;

	/**
	 * a "safe" (lowercased) string that is used when creating actions/filters
	 *
	 * @var string
	 */
	private $filterid;

	/**
	 * A list of settings that apply to this plugin
	 *
	 * @var array
	 */
	protected $config;

	static private $instance;
	static private $definitions = array();

	/**
	 * List of actions one wants to hook on to
	 * This array should have the action as key and a method or callable
	 * function as value.
	 *
	 * @var array
	 */
	protected $on = array();

	/**
	 * A list of filters you want to hook
	 * This array should contain a list of filters you want to use and
	 * should point to a method, or a callable method.
	 *
	 * @var array
	 */
	protected $filter = array();

	public function __construct($name,$directory)
	{
		$this->addonName  = $name;
		$this->filterid   = strtolower($name);
		$this->directory  = $directory;

		$this->config     = $this->applyFilter('config',array());
		$this->on         = $this->applyFilter('actions',$this->on);
		$this->filter     = $this->applyFilter('actions',$this->on);

		foreach( $this->on as $action => $method) {
			if ( !is_callable($method)) {
				$method = array($this,$method);
			}
			add_action($action,$method);
		}

		foreach( $this->filter as $filter => $method) {
			if ( !is_callable($method)) {
				$method = array($this,$method);
			}
			add_filter($filter,$method);
		}
		self::$instance = $this;
	}

	/**
	 * Returns the addonName
	 *
	 * @return string
	 */
	public function getaddonName()
	{
		return $this->addonName;
	}

	/**
	 * Returns a full URI to a plugin specific file
	 *
	 * @param  string $addonFile
	 * @return string
	 */
	public function getUri($addonFile)
	{
		$fullpath = str_replace('//','/',$this->directory . '/' . $addonFile);
		$relative = apply_filters('themeaddon/uri',Theme::makeRelativePath($fullpath));

		return get_template_directory_uri() . $relative;
	}

	/**
	 * Calculates a unique filter name and calls it using do_action_ref_array()
	 * The filter name will be a string starting with the string 'themeaddon_' followed
	 * by the filterid and the $action variable.
	 *
	 * @param string $action
	 * @param array $params
	 * @since 1.0
	 */
	protected function doAction($action,$params = array())
	{
		$action = 'themeaddon_' . $this->filterid . '_' . $action;

		if ( !empty($params)) {
			do_action_ref_array($action,$params);
		} else {
			do_action($action);
		}
	}

	/**
	 * Calculates a unique filter name and calls it using apply_filters_ref_array()
	 * The filter name will be a string starting with the string 'themeaddon_' followed
	 * by the filterid and the $filter variable.
	 *
	 * @see apply_filters_ref_array()
	 * @param string  $filter
	 * @param mixed   $value
	 * @param array   $optional
	 *
	 * @return mixed
	 * @since 1.0
	 */
	protected function applyFilter($filter,$value,$optional = array())
	{
		array_unshift($optional,$value);
		$action = 'themeaddon/' . $this->filterid . '/' . $filter;

		return apply_filters_ref_array($action,$optional);
	}

	/**
	 * Reads the addon.json package if it is available
	 * The file can contain two entries:
	 * - name
	 * - file (optional)
	 *
	 *
	 * @param $addonDir
	 *
	 * @return bool|object
	 * @since 1.0
	 */
	static public function readAddonDefinition($addonDir)
	{
		if ( isset(self::$definitions[$addonDir])) {
			return self::$definitions[$addonDir];
		}

		$package = $addonDir . '/addon.json';

		if ( file_exists($package) && is_readable($package) ) {
			self::$definitions[$addonDir] = json_decode(file_get_contents($package));
			return self::$definitions[$addonDir];
		}
		return false;
	}

	static public function uri($addonFile = '')
	{
		return self::instance()->getUri($addonFile);
	}

	/**
	 * Returns an instance to the plugin
	 *
	 * @return Addon
	 */
	static protected function instance()
	{
		return self::$instance;
	}

}