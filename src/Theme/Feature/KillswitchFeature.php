<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\Arr;
use Woppe\Wordpress\Theme\Admin\Notification;
use Woppe\Wordpress\Theme\Admin\Toolbar;

/**
 * Closes a site for external visitors (Admin will remain open)
 * You can now disable the site from the admin toolbar (Advanced Controls pulldown). You can (un)lock
 *
 * You can create a page with the slug 'locked' or 'gesloten'. Which will be used as lock page (if present)
 * otherwise, it will try to locate a locked.html file. If it can't find both, it will just fail with a
 * simple message.
 *
 * @package Woppe\Wordpress\Theme\Feature
 */
class KillswitchFeature implements FeatureInterface
{
  const LOCK_LABEL = 'enabled';

  protected $status = false;
  protected $url    = false;

  public function register($options = [])
  {
    $this->status = get_option(__CLASS__);
    $this->url    = $this->getLockPage();
    $title        = (( $this->status === self::LOCK_LABEL ? 'Unl' : 'L') . 'ock Site') . $this->getLockIcon($this->status);
    $userdata     = get_userdata(get_current_user_id());

    if ( is_admin() && is_user_logged_in() && array_intersect($userdata->roles,apply_filters("woppe/killswitch/allowed_roles",['administrator']))) {
      Toolbar::addItem('killswitch',$title,[$this,'enableKillSwitch']);
    } else {
      add_action("init",[$this,'checkForLock']);
    }

    add_action('admin_init',[$this,'checkForAdminWarning']);
  }

  public function enableKillSwitch()
  {
    if ( $this->status === self::LOCK_LABEL ) {
      $this->status = false;
      Notification::error('Your site is locked',Notification::ALL_ADMIN_USERS,true);
      Notification::notice(__('Your site has been unlocked'));
    } else {
      $this->status = self::LOCK_LABEL;
      Notification::notice(__('Your site has been locked for external visitors'));
    }
    update_option(__CLASS__,$this->status);
  }

  public function checkForLock()
  {
    global $wp;

    if ( is_admin() ) {
      return;
    }

    if ( $this->status === self::LOCK_LABEL ) {
      header('HTTP/1.1 503 Service Unavailable');



      if ( $this->url && wp_make_link_relative($this->url) !== Arr::value($_SERVER,'REQUEST_URI')) {
        wp_redirect($this->url);
        exit();
      } elseif ( !$this->url ) {
        die("The site is currently unavailable. Please try again later");
      }
    }
  }

  public function checkForAdminWarning()
  {
    if ( $this->status === self::LOCK_LABEL && is_admin()) {
      Notification::error("Your site is locked");
    }
  }

  protected function getLockIcon($status)
  {
    if ( $status === self::LOCK_LABEL ) {
      return '<span class="ab-icon dashicons dashicons-unlock"></span>';
    }
    return '<span class="ab-icon dashicons dashicons-lock"></span>';
  }

  protected function getLockPage()
  {
    $page = get_page_by_path('locked');

    if ( !$page ) {
      $page = get_page_by_path('gesloten');
    }

    if ( !$page ) {
      if ( file_exists(get_template_directory() . '/locked.html') ) {
        return get_template_directory_uri() . '/locked.html';
      } elseif ( file_exists(dirname(ABSPATH) . '/locked.html')) {
        return home_url() . '/locked.html';
      }
    }

    return get_permalink($page);
  }

}