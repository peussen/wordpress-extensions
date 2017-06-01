<?php
/*
 * @author: petereussen
 * @package: pressmarket
 */

namespace Woppe\Wordpress\Http;


use Symfony\Component\HttpFoundation\Request;

/**
 * Request which also handles wordpress url type requests (page/7) etc
 *
 * @package Woppe\Wordpress\Http
 * @since 0.1.2
 */
class WordpressRequest extends Request
{
	public function initialize( array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null )
	{
		parent::initialize( $query, $request, $attributes, $cookies, $files, $server, $content );
		$this->getUrlParameters();
	}

	private function getUrlParameters()
	{
		$uri    = $this->getRequestUri();
		$perma  = wp_make_link_relative(get_permalink());
		$params = rtrim(str_replace($perma,'',$uri),'/');
		$sets   = explode("/",$params);

		while ( $sets ) {
			$key = array_shift($sets);
			$val = array_shift($sets);

			if ( $key && $val ) {
				$this->query->set($key,$val);
			}
		}
	}
}