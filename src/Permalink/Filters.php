<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Permalink;


use HarperJones\Wordpress\Arr;

abstract class Filters
{
  static private $menuPages    = [];
  static private $menuSubPages = [];

  static public function menuPagesOnly($post,$menu)
  {
    if ( is_array($menu) ) {
      $ids = [];

      foreach( $menu as $menuId ) {
        $ids = array_merge($ids,self::buildPageList($menuId));
      }
    } else {
      $ids = self::buildPageList($menu);
    }

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

  static public function subPagesOnly($post,$menu)
  {
    if ( is_array($menu) ) {
      $ids = [];

      foreach( $menu as $menuId ) {
        $pages = self::buildSubPageList($menuId);

        foreach( $pages as $parent => $subpages ) {
          if ( isset($ids[$parent]) ) {
            $ids[$parent] = array_unique(array_merge($ids[$parent],$subpages));
          } else {
            $ids[$parent] = $subpages;
          }
        }
      }
    } else {
      $ids = self::buildSubPageList($menu);
    }

    $subpages = Arr::flatten($ids);

    if ( $post instanceof \WP_Post ) {
      $postId = $post->ID;
    } else {
      $postId = $post;
    }

    return in_array($postId,$subpages);
  }

  static protected function buildSubPageList($menu)
  {
    self::buildPageList($menu);

    return self::$menuSubPages;
  }

  static protected function buildPageList($menu)
  {
    if ( isset(self::$menuPages[$menu]) ) {
      return self::$menuPages[$menu];
    }

    $menuItems   = wp_get_nav_menu_items(self::getMenuId($menu));
    $pages       = [];
    $subpages    = [];
    $itemsToPage = [];

    foreach( $menuItems as $mi ) {
      $itemsToPage[$mi->ID] = (int)get_post_meta( $mi->ID, '_menu_item_object_id', true );
    }

    foreach( $menuItems as $mi ) {
      $pages[] = (int)$itemsToPage[$mi->ID];

      if ( $mi->menu_item_parent != 0 ) {
        $subpages[$itemsToPage[$mi->menu_item_parent]] = (int)$itemsToPage[$mi->ID];
      }
    }

    self::$menuPages[$menu]    = $pages;
    self::$menuSubPages[$menu] = $subpages;

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