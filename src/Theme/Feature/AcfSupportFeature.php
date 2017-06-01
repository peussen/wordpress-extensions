<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\Setup;

class AcfSupportFeature implements FeatureInterface
{
    public function register($options = [])
    {
        Setup::globalizeStaticMethods(ACFSupport::class);
    }
}