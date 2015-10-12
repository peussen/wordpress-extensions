<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\GoogleAnalytics;

class GoogleAnalyticsFeature implements FeatureInterface
{
    private $google;

    public function register($options = [])
    {
        if (isset($options['ua'])) {
            $ua = $options['ua'];
        } else {
            $ua = null;
        }

        $this->google = new GoogleAnalytics($ua,$options);
    }
}