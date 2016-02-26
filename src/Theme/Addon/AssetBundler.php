<?php

namespace HarperJones\Wordpress\Theme\Addon;


use HarperJones\Wordpress\Theme\Theme;
use HarperJones\Wordpress\Theme\ThemeAssetException;

/**
 * A global package to resolve addons that use the same libraries
 *
 * @package HarperJones\Wordpress\Theme\Addon
 * @author Peter Eussen <peter.eussen@harperjones.nl>
 * @deprecated
 */
class AssetBundler
{
	/**
	 * A list of locations to global theme paths
	 *
	 * @var mixed|\stdClass
	 */
	protected $themeSettings;

	/**
	 * A list of packages that are required by the addons
	 *
	 * @var mixed|\stdClass
	 */
	protected $packages;

	/**
	 * Wether a plugin has changed
	 *
	 * @var int
	 */
	protected $dirty = 0;

	public function __construct()
	{
		$this->themeSettings       = Theme::getThemeSettings();
		$this->packages            = $this->loadInstalledPackages();

		add_filter('themeaddon-get-relative-uri', array($this,'rewriteInstalledUri'));
	}

	/**
	 * Writes a new assets list when it has changed
	 *
	 */
	public function __destruct()
	{
		if ( $this->dirty ) {
			file_put_contents($this->themeSettings->base . '/assets.json',json_encode($this->packages));
		}
	}

	/**
	 * Converts a local addon URI to a theme URI when the file was copied
	 *
	 * @param $uri
	 *
	 * @return mixed
	 * @filter themeaddon-get-relative-uri
	 */
	public function rewriteInstalledUri($uri)
	{
		if ( !isset($this->packages->lookup->{$uri})) {
			return $uri;
		}

		return $this->packages->lookup->{$uri};
	}

	/**
	 * Adds a package from the addon.json file to the global asset list
	 *
	 * @param $package
	 *
	 * @return bool
	 */
	public function add($package)
	{
		if (!isset($this->packages->lookup->{$package->src})) {
			if ( isset($package->package)) {
				if ( !isset($this->themeSettings->{$package->type})) {
					throw new ThemeAssetException("Unsupported package type: {$package->type}");
				}

				$tplDir     = get_template_directory();
				$target     = $this->themeSettings->{$package->type} . basename($package->src);

				if ( isset($this->package->packages[$package->package]->{$package->type})) {
					if ( !in_array($target,$this->package->packages[$package->package]->{$package->type})) {
						return true;
					}
				}

				if (file_exists($tplDir . $package->src) && !file_exists($target)) {
					copy($tplDir . $package->src,$target);
				} else {
					if ( !file_exists($tplDir . $package->src)) {
						throw new ThemeAssetException("Addon requests install of {$package->src}, but file is not available");
					}
				}

				$rel = Theme::makeRelativePath($package->src);
				$this->packages->lookup->{$rel} = Theme::makeRelativePath($target);
				$this->packages->package->{$package->package}->{$package->type}[] = $target;
				$this->dirty = 1;
			}
		}
		return true;
	}

	/**
	 * Loads a list of installed packages
	 *
	 * @return mixed|\stdClass
	 */
	protected function loadInstalledPackages()
	{
		if ( file_exists($this->themeSettings->base . '/assets.json')) {
			return json_decode(file_get_contents($this->themeSettings->base . '/assets.json'));
		} else {
			$mapping           = new \stdClass();
			$mapping->packages = array();
			$mapping->lookup   = new \stdClass();
			return $mapping;
		}
	}
}