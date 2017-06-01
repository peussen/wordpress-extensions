<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


class HidePostTypeFeature implements FeatureInterface
{
    protected $postTypesToHide;

    public function register($options = [])
    {
        $this->postTypesToHide = (array)$options;

        add_action('template_redirect', [$this, 'filterPostTypes']);
    }

    public function filterPostTypes()
    {
        if ( in_array(get_query_var('post_type'),$this->postTypesToHide) ) {
            wp_redirect( apply_filters('woppe/hideposttype/redirect', home_url()), 302);
            exit();
        }
    }
}