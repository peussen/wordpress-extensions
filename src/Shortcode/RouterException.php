<?php
/*
 * @author: petereussen
 * @package: pressmarket
 */

namespace HarperJones\Wordpress\Shortcode;


use Exception;
use HarperJones\Wordpress\Theme\View;

/**
 * Exception which will be handled by the router to display user friendly errors
 *
 * @package HarperJones\Wordpress\Shortcode
 */
class RouterException extends \RuntimeException
{
	public function getView($args = array())
	{
		$args['exception'] = $this;

		return new View($this->getCode(),$args);
	}
}