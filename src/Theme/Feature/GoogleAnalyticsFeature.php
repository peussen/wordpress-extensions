<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\GoogleAnalytics;

/**
 * Usage:
 *
 * in your code you should have two things:
 *
 * <code>
 * add_theme_support('harperjones-googleanalytics','UA-XXXXXX-XX');
 * </code>
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class GoogleAnalyticsFeature implements FeatureInterface
{
    private $google;

    public function register($options = [])
    {
        if (isset($options['ua'])) {
            $ua = $options['ua'];
        } else {
            $ua = (isset($options[0]) ? $options[0] : false);
        }

        $this->google = new GoogleAnalytics($ua,$options);
    }
}