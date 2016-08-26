<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Permalink;


abstract class Filters
{
  static private $menuPages = [];

  static public function menuPagesOnly($post,$menu)
  {
    if ( is_array($menu) ) {
      $ids = [];

      foreach( $menu as $menuId ) {
        $ids = array_merge($ids,self::buildPageList($menuId));
      }
    }

    $ids = self::buildPageList($menu);

    if ( $ids ) {
      $ids = array_unique($ids);

      if ( $post instanceof \WP_Post ) {
        $postId = $post->ID;
      } else {
        $postId = $post;
      }

      return in_array($postId,$ids);
    }
    return false;
  }

  static protected function buildPageList($menu)
  {
    if ( isset(self::$menuPages[$menu]) ) {
      return self::$menuPages[$menu];
    }

    $menuItems   = wp_get_nav_menu_items(self::getMenuId($menu));
    $pages       = [];
    $itemsToPage = [];

    foreach( $menuItems as $mi ) {
      $itemsToPage[$mi->ID] = (int)get_post_meta( $mi->ID, '_menu_item_object_id', true );
    }

    foreach( $menuItems as $mi ) {
      $pages[] = (int)$itemsToPage[$mi->ID];
    }

    self::$menuPages[$menu] = $pages;

    return $pages;
  }

  static private function getMenuId($name)
  {
    $locations = get_nav_menu_locations();

    if ( isset($locations[$name]) ) {
      $menu  = wp_get_nav_menu_object( $locations[ $name ] );
      return $menu->term_id;
    }
    return false;
  }

}