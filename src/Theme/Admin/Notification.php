<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Admin;


class Notification
{
  const NOTIFICATION_OPTION_NAME = 'hj_notifications';

  static private $instance = null;

  protected $messages = [];

  protected function __construct()
  {
    add_action('admin_init',[$this,'bootstrapNotifications']);
  }

  public function __destruct()
  {
    $this->storeNotifications();
  }

  public function bootstrapNotifications()
  {
    if ( is_admin() ) {
      $this->loadNotifications();
    }
  }

  static public function instance()
  {
    if ( static::$instance === null ) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  protected function loadNotifications()
  {
    $this->messages = get_option(self::NOTIFICATION_OPTION_NAME);

    if ( !is_array($this->messages) ) {
      $this->messages = [];
    }
    return true;
  }

  protected function storeNotifications()
  {
    update_option(self::NOTIFICATION_OPTION_NAME,$this->messages);
  }
}