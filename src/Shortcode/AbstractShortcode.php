<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 13/02/15
 * Time: 14:20
 */

namespace HarperJones\Wordpress\Shortcode;


use HarperJones\Wordpress\Theme\View;
use HarperJones\Wordpress\WordpressException;
use League\Url\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base setup for a cleaner way to create Wordpress Shortcodes
 *
 * This class is intended to work as a sort router/controller setup for a wordpress
 * shortcode. By default the router will route all traffic to an action_index
 * method.
 * You can however have multiple handling functions for GET or POST. so if you have a
 * form you can also create a get_index, and post_index. They will be called in the
 * following order: post_index, get_index, action_index
 * You can break the sequence by returning output (a View) in one of the methods.
 *
 * @package HarperJones\Wordpress\Shortcode
 */
abstract class AbstractShortcode
{
	/**
	 * A list of supported shortcode attributes (key => default value)
	 * @var array
	 */
	protected $supportedAttributes = array();

	private $shortcode;

	/**
	 * @var Symfony\Component\HttpFoundation\Request;
	 */
	protected $request;

	protected function register($shortcode)
	{
		if ( !defined('ABSPATH')) {
			throw new WordpressException('No Wordpress instance found');
		}
		$this->shortcode = $shortcode;
		add_shortcode($this->shortcode, array($this,'router'));
	}

	/**
	 * Returns the shortcode tag for this shortcode
	 *
	 * @return string
	 */
	public function getTag()
	{
		return $this->shortcode;
	}

	/**
	 * The router is called when the shortcode is invoked, and handles the output generation
	 * It will delegate the actual processing to action methods. This method just finds
	 * out which action methods to check.
	 *
	 * @param array $atts
	 * @param array $content
	 *
	 * @return string
	 */
	public function router($atts, $content )
	{
		// Refspec: Shortcodes, should never produce output of any kind, but should return it
		$atts = shortcode_atts(
			$this->supportedAttributes,
			$atts,
			$this->shortcode
		);

		$request = Request::createFromGlobals();
		$options = array(
			'get_' . $request->get('_action','index'),
			'action_' . $request->get('_action','index'),
			'action_index',
		);

		if ( $request->isMethod('POST')) {
			array_unshift($options,'post_' . $request->get('_action','index'));
		}

		if ( !$request->query->has('_action')) {
			$request->query->add(array('_action' => 'index'));
		}

		$this->request = $request;
		$args          = array($request, $atts, $content);

		return $this->cascadeCallControllerMethod($options,$args);
	}

	/**
	 * Obtains a view object with some variables preset
	 *
	 * @param       $template
	 * @param array $attributes
	 *
	 * @return View
	 */
	public function getView($template,$attributes = array())
	{
		if (!isset($attributes['_shortcode'])) {
			$attributes['_shortcode'] = $this;
		}

		if ( !isset($attributes['_request'])) {
			$attributes['_request'] = $this->request;
		}

		return new View('shortcode/' . $this->shortcode . '/' . $template,$attributes);
	}

	/**
	 * Creates a new URL based on the current request URL, and adds/replaced some parameters
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function getUrlWithParams($params)
	{
		$uri   = $this->request->getURI();
		$url   = Url::createFromUrl($uri);
		$query = $url->getQuery();

		foreach( $params as $name => $value ) {
			$query[$name] = $value;
		}
		return $url;
	}

	/**
	 * Resolves & calls methods until it finds one that answers
	 *
	 * @param array $options
	 * @param array $args
	 *
	 * @return string
	 */
	private function cascadeCallControllerMethod($options, $args = array())
	{
		foreach( $options as $method ) {
			if ( method_exists($this,$method)) {
				$response = call_user_func_array(array($this,$method),$args);

				if ( $response instanceof View ) {
					return $response->render();
				} if ( is_string($response) && !empty($response)) {
					return $response;
				}
			}
		}
		return '';
	}
}