<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\Setup;

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
    Setup::addFeature('woppe-disabled-comments');

    // Enable varnish
    Setup::addFeature('woppe-varnish');

    // Enable file autoload
    Setup::addFeature('woppe-autoload-files');
  }
}