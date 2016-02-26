<?php
namespace HarperJones\Wordpress\Theme;

use HarperJones\Wordpress\WordpressException;

/**
 * Wrapper for the wordpress template files
 *
 * @package HarperJones\Wordpress\Theme
 */
final class View
{
	static private $viewStack = [];
	static private $shouldInherit = false;

	/**
	 * The name of the template that needs to be rendered
	 * @var string
	 */
	private $template;

	/**
	 * Where we can find the actual file
	 * @var
	 */
	private $filePath;

	/**
	 * Optional attributes for the template
	 *
	 * @var array
	 */
	private $attributes;

	/**
	 * @param       $template
	 * @param array $attributes
	 */
	public function __construct($template,$attributes = array(),$inherit = null)
	{
		if ( !defined('ABSPATH')) {
			throw new WordpressException("No Wordpress installation found");
		}

		$this->attributes = (array)$attributes;
		$this->template   = 'templates/' . $template . '.php';
		$this->filePath   = locate_template($this->template);

		if (empty($this->filePath)) {
			throw new InvalidTemplateException($template);
		}

		$inherit = (self::$shouldInherit && $inherit === null) || ($inherit === true);
		if ( $inherit && self::$viewStack) {
			$this->attributes = array_merge(self::$viewStack[count(self::$viewStack) - 1]->attributes(),$this->attributes);
		}
		self::$viewStack[] = $this;
	}

	public function __destruct()
	{
		array_pop(self::$viewStack);
	}

	public function attributes()
	{
		return $this->attributes;
	}

	public function get($attribute,$default = null)
	{
		if ( isset($this->attributes[$attribute])) {
			return $this->attributes[$attribute];
		}
		return $default;
	}

	public function set($attribute,$val)
	{
		if ( $attribute && $attribute[0] != '_') {
			$this->attributes[$attribute] = $val;
			return true;
		}
		return false;
	}

	public function __toString()
	{
		try {
			return $this->render();
		} catch (\Exception $e) {
			return $e->getMessage() . "\n" . $e->getTraceAsString();
		}
	}

	public function apply($template, $extra = array())
	{

		try {
			$view = new self($template);
		} catch (InvalidTemplateException $e) {
			$dir  = dirname($this->template);
			$dir  = rtrim($dir,'/');

			if ( strpos($dir,'/') !== false) {
				$dir  = substr($dir,strpos($dir,'/')+1);
			}
			$view = new self( $dir . '/' . $template);
		}
		echo $view->render(array_merge($this->attributes,$extra));
	}

	/**
	 * Calls the template and captures all output
	 *
	 * @param bool $attributes
	 * @param bool $output
	 *
	 * @return string
	 */
	public function render($attributes = false,$output = false)
	{
		global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

		// Ensure view is set
		$_view = $this;

		ob_start();

		if ( $attributes !== false && $attributes !== null) {
			$this->attributes = array_merge($this->attributes,$attributes);
		}

		extract( $this->attributes, EXTR_SKIP );

		if ( $this->filePath ) {
			self::$shouldInherit = true;
			require($this->filePath);
			self::$shouldInherit = false;
		} else {
			throw new InvalidTemplateException($this->template);
		}

		if ( $output === false ) {
			return ob_get_clean();
		} else {
			echo ob_get_clean();
		}
	}
}