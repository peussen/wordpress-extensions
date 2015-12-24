<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\Setup;

class AcfSupportFeature implements FeatureInterface
{
    public function register($options = [])
    {
        Setup::globalizeStaticMethods(ACFSupport::class);
    }
}