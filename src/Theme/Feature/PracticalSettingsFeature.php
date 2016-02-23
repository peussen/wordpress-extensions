<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\Setup;

class PracticalSettingsFeature implements FeatureInterface
{

  public function register($options = [])
  {
    // Disable and remove de web API
    add_filter('rest_enabled', '_return_false');
    add_filter('rest_jsonp_enabled', '_return_false');

    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
    remove_action( 'template_redirect', 'rest_output_link_header', 11, 0 );

    // Disable comments
    Setup::addFeature('harperjones-disabled-comments');

    // Enable varnish
    Setup::addFeature('harperjones-varnish');

    // Enable file autoload
    Setup::addFeature('harperjones-autoload-files');
  }
}