<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\AccessControl\MasterAdmin;

/**
 * Implement a Master Admin role which has access to all, and allow limitation of posts access based on a System Category
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class MasterAdminFeature implements FeatureInterface
{
  public function register($options = [])
  {
    $admin = MasterAdmin::bootstrap();
    $pt    = false;

    if ( isset($options['post_types']) ) {
      $pt = $options['post_types'];
    } else if ( count($options) ) {
      $pt = $options;
    }

    if ( $pt ) {
      $admin->addSystemPostType($pt);
    }

    $role = $admin->getRole();
    $role->assign(apply_filters('harperjones/accesscontrol/masteruser','support'));
  }

}