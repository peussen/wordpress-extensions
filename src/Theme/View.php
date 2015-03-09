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

	public function __construct($template,$attributes = array())
	{
		if ( !defined('ABSPATH')) {
			throw new WordpressException("No Wordpress installation found");
		}
		$this->attributes = (array)$attributes;
		$this->template   = 'templates/' . $template . '.php';
		$this->filePath   = locate_template($this->template);

		if (empty($this->filePath)) {
			throw new InvalidTemplate($template);
		}
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
		} catch (InvalidTemplate $e) {
			$dir  = dirname($this->template);
			$dir  = substr($dir,strpos($dir,'/')+1);
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

		ob_start();

		if ( $attributes !== false && $attributes !== null) {
			$this->attributes = $attributes;
		}

		if ( !isset($attributes['_view'])) {
			$attributes['_view'] = $this;
		}

		extract( $this->attributes, EXTR_SKIP );

		if ( $this->filePath ) {
			require($this->filePath);
		} else {
			throw new InvalidTemplate($this->template);
		}

		if ( $output === false ) {
			return ob_get_clean();
		} else {
			echo ob_get_clean();
		}
	}
}