<?php
/*
 * @author: petereussen
 * @package: pressmarket
 */

namespace HarperJones\Wordpress\Shortcode;


use Exception;
use HarperJones\Wordpress\Theme\View;

class RouterException extends \RuntimeException
{
	public function getView()
	{
		$args = array(
			'exception' => $this
		);
		return new View($this->getCode(),$args);
	}
}