<?php
/*
 * @author: petereussen
 * @package: pressmarket
 */

namespace Woppe\Wordpress\Shortcode;


use Exception;
use Woppe\Wordpress\Theme\View;

/**
 * Exception which will be handled by the router to display user friendly errors
 *
 * @package Woppe\Wordpress\Shortcode
 */
class RouterException extends \RuntimeException
{
	public function getView($args = array())
	{
		$args['exception'] = $this;

		return new View($this->getCode(),$args);
	}
}