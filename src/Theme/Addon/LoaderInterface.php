<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 04/03/15
 * Time: 12:05
 */

namespace HarperJones\Wordpress\Theme\Addon;


interface LoaderInterface
{
	/**
	 * Should load all files and return a list of plugins that was loaded
	 * @return array
	 */
	public function autoload();
}