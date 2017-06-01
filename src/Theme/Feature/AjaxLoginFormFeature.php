<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\Login\AJAXLogin;

/**
 * Out of the box support for wordpress user login on the website using an AJAX login form
 *
 * This feature allows you to quickly add a login form to your website. The form will
 * use an AJAX request to validate your login and give you feedback about whether it
 * worked or not.
 *
 * <code>
 * add_theme_support('woppe-ajax-loginform');
 * </code>
 *
 * @package Woppe\Wordpress\Theme\Feature
 */
class AjaxLoginFormFeature implements FeatureInterface
{
  public function register($options = [])
  {
    $ajaxLogin = new AJAXLogin();
    $ajaxLogin->init();

    if ( isset($options['redirecturl'])) {
      add_filter(
        'woppe/login/redirecturl',
        function ($val) use ($options) {
          return $options['redirecturl'];
        }
      );
    }

    if ( isset($options['succes'])) {
      add_filter(
        'woppe/login/succesmessage',
        function ($val) use ($options) {
          return $options['succes'];
        }
      );
    }

    if ( isset($options['invalid'])) {
      add_filter(
        'woppe/login/invalidmessage',
        function ($val) use ($options) {
          return $options['invalid'];
        }
      );
    }

    if ( isset($options['loading'])) {
      add_filter(
        'woppe/login/submitm`essage',
        function ($val) use ($options) {
          return $options['loading'];
        }
      );
    }
  }

}