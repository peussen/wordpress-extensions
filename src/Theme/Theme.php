<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 05/03/15
 * Time: 14:09
 */

namespace Woppe\Wordpress\Theme;


class Theme
{
	static public function getThemeSettings()
	{
		$settingfile = get_template_directory() . '/paths.json';
		$settings    = new \stdClass();

		if ( file_exists($settingfile) && is_readable($settingfile)) {
			$settings = json_decode($settingfile);
		}

		$settings->base   = get_template_directory();
		$settings->vendor = $settings->base . (isset($settings->vendor) ? $settings->vendor : '/assets/js/plugins/');
		$settings->custom = $settings->base . (isset($settings->custom) ? $settings->custom : '/assets/js/');
		$settings->css    = $settings->base . (isset($settings->css) ? $settings->css : '/assets/css/');
		$settings->less   = $settings->base . (isset($settings->less) ? $settings->less : '/assets/less/components/');
		return $settings;
	}


	static public function makeRelativePath($dir)
	{
		return str_replace('//','/',str_replace(get_template_directory(),'',$dir));
	}
}