<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Admin;


use HarperJones\Wordpress\Arr;
use HarperJones\Wordpress\Setup;

class Toolbar
{
  static protected $instance = null;

  protected $items = [];

  protected function __construct()
  {
    add_action('wp_before_admin_bar_render', [$this,'renderMenu']);
    add_action('wp_ajax_hj_wpex',[$this,'executeCommand']);
    add_action('admin_notices',[$this,'displayNotice']);
  }

  static public function addItem($id,$title,$callback)
  {
    $instance = static::instance();
    $instance->addMenuItem($id,$title,$callback);
    return $instance;
  }

  static protected function instance()
  {
    if ( static::$instance === null ) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  protected function addMenuItem($id,$title,$callback)
  {
    $this->items[$id] = [
      'id'        => $id,
      'title'     => $title,
      'callback'  => $callback
    ];
  }

  public function displayNotice()
  {
    $messages = get_option('hj_wpex_messages');

    if ( $messages ) {
      foreach( $messages as $code => $submessages ) {
        foreach ($submessages as $message) {
          echo '<div class="updated ' . $code . ' is-dismissible"><p>'. $message . '</p></div>';
        }
      }

      delete_option('hj_wpex_messages');
    }

  }

  public function renderMenu()
  {
    global $wp_admin_bar;

    $wp_admin_bar->add_menu([
      'id'    => 'hj_wpex',
      'title' => __('Advanced Controls'),
    ]);

    foreach( $this->items as $item ) {
      $wp_admin_bar->add_node([
        'id'    => $item['id'],
        'title' => $item['title'],
        'href'  => admin_url('admin-ajax.php') . '?action=hj_wpex&sub=' . $item['id'],
        'parent'=> 'hj_wpex',
      ]);

    }
  }

  public function executeCommand()
  {
    if ( is_user_logged_in() ) {
      $sub = Arr::value($_GET,'sub');

      if ( $sub && isset($this->items[$sub]) && isset($this->items[$sub]['callback'])) {
        try {
          $this->items[$sub]['callback']($this);
        } catch (\Exception $e) {
          $this->addMessage($e->getMessage() . ' (' . Setup::deNamespace($e) . ')');
        }
      }
    }

    $url = Arr::value($_SERVER,'HTTP_REFERER',admin_url());
    wp_safe_redirect($url);
    exit();
  }

  public function addMessage($message,$code = 'error')
  {
    $messages = get_option('hj_wpex_messages');

    $messages[$code][] = $message;

    update_option('hj_wpex_messages',$messages);
  }
}