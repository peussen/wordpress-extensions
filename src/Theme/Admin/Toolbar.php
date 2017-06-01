<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Admin;


use Woppe\Wordpress\Arr;
use Woppe\Wordpress\Setup;

class Toolbar
{
  static protected $instance = null;

  protected $items = [];

  protected function __construct()
  {
    Notification::wakeup();

    add_action('wp_before_admin_bar_render', [$this,'renderMenu']);
    add_action('wp_ajax_woppe_wpex',[$this,'executeCommand']);
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

  public function renderMenu()
  {
    global $wp_admin_bar;

    $wp_admin_bar->add_menu([
      'id'    => 'woppe_wpex',
      'title' => __('Advanced Controls'),
    ]);

    foreach( $this->items as $item ) {
      $wp_admin_bar->add_node([
        'id'    => $item['id'],
        'title' => $item['title'],
        'href'  => admin_url('admin-ajax.php') . '?action=woppe_wpex&sub=' . $item['id'],
        'parent'=> 'woppe_wpex',
      ]);

    }
  }

  public function executeCommand()
  {
    if ( is_user_logged_in() ) {
      $sub = Arr::value($_GET,'sub');

      if ( $sub && isset($this->items[$sub]) && isset($this->items[$sub]['callback'])) {
        try {
          $this->items[$sub]['callback']();
        } catch (\Exception $e) {
          Notification::error($e->getMessage() . ' (' . Setup::deNamespace($e) . ')');
        }
      }
    }

    $url = Arr::value($_SERVER,'HTTP_REFERER',admin_url());
    wp_safe_redirect($url);
    exit();
  }

}