<?php
namespace Woppe\Wordpress\Theme;

/**
 * A lightweight controller to handle all separate entry points in a uniform way
 *
 * @package Woppe\Wordpress\Theme
 */
class ViewController
{
	static public function render($file,$attributes = array())
	{
		$basefile = basename($file,'.php');


		$view = new View($basefile,$attributes);
		echo $view->render();
	}
}