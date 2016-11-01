<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\Login\AJAXLogin;

class AjaxLoginFormFeature implements FeatureInterface
{
  public function register($options = [])
  {
    $ajaxLogin = new AJAXLogin();
    $ajaxLogin->init();

    if ( isset($options['redirecturl'])) {
      add_filter(
        'harperjones/login/redirecturl',
        function ($val) use ($options) {
          return $options['redirecturl'];
        }
      );
    }

    if ( isset($options['succes'])) {
      add_filter(
        'harperjones/login/succesmessage',
        function ($val) use ($options) {
          return $options['succes'];
        }
      );
    }

    if ( isset($options['invalid'])) {
      add_filter(
        'harperjones/login/invalidmessage',
        function ($val) use ($options) {
          return $options['invalid'];
        }
      );
    }

    if ( isset($options['loading'])) {
      add_filter(
        'harperjones/login/submitmessage',
        function ($val) use ($options) {
          return $options['loading'];
        }
      );
    }
  }

}