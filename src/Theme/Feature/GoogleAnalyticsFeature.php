<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\GoogleAnalytics;

/**
 * Usage:
 *
 * in your code you should have two things:
 *
 * <code>
 * add_theme_support('woppe-googleanalytics','UA-XXXXXX-XX');
 * </code>
 *
 * @package Woppe\Wordpress\Theme\Feature
 */
class GoogleAnalyticsFeature implements FeatureInterface
{
    private $google;

    public function register($options = [])
    {
        if (isset($options['ua'])) {
            $ua = $options['ua'];
        } elseif (isset($options[0])) {
            $ua = array_shift($options);

            $options['domain'] = array_shift($options);
        }


        $this->google = new GoogleAnalytics($ua,$options);
    }
}