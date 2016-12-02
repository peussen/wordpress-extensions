<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Admin;


class Notification
{
  const NOTIFICATION_OPTION_NAME = 'hj_notifications';
  const ALL_ADMIN_USERS = 'all';

  static private $instance = null;

  protected $messages = [];

  protected function __construct()
  {
    add_action('admin_init',[$this,'bootstrapNotifications']);
    add_action('admin_notices',[$this,'displayNotices']);
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

  public function displayNotices()
  {
    foreach( $this->messages as $cap => $msgs ) {
      if ( $cap === static::ALL_ADMIN_USERS || user_can(get_current_user_id(),$cap)) {
        foreach( $msgs as $code => $levelmsgs ) {
          foreach( $levelmsgs as $msg ) {
            $this->render($msg,$code);
          }
        }

        unset($this->messages[$cap]);
      }
    }
  }

  public function send($message,$state,$capability = self::ALL_ADMIN_USERS, $remove = false)
  {
    if ( isset($this->messages[$capability][$state])) {

      if ( $remove ) {
        $this->messages[$capability][$state] = array_diff($this->messages[$capability][$state],[$message]);
      } else if (!in_array($message,$this->messages[$capability][$state])) {
        $this->messages[$capability][$state][] = $message;
      }
    } else {
      $this->messages[$capability][$state][] = $message;
    }
    return $this;
  }

  static public function notice($message,$capability = self::ALL_ADMIN_USERS, $remove = false)
  {
    return static::instance()->send($message,'notice',$capability,$remove);
  }

  static public function error($message,$capability = self::ALL_ADMIN_USERS, $remove = false)
  {
    return static::instance()->send($message,'error',$capability,$remove);
  }

  static public function instance()
  {
    if ( static::$instance === null ) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  static public function wakeup()
  {
    return static::instance();
  }

  protected function render($message, $code = 'notice')
  {
    echo '<div class="updated ' . $code . ' is-dismissible"><p>'. $message . '</p></div>';
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