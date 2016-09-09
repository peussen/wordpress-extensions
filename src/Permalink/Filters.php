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
  static private $frontSubPages= null;

  /**
   * Rewrite only pages that are placed as child pages of the front-page
   * Watch out: this will cause hashes like #home/aboutus instead of #aboutus
   * @param \WP_Post|int $post
   * @return bool
   */
  static public function subPagesOfFrontpageOnly($post)
  {
    if ( self::$frontSubPages === null ) {
      $frontpage = get_option('page_on_front');
      self::$frontSubPages = static::getSubPagesOf($frontpage);
    }

    if ( $post instanceof \WP_Post ) {
      $postId = $post->ID;
    } else {
      $postId = $post;
    }

    return in_array($postId,self::$frontSubPages);
  }

  /**
   * Rewrite only pages that are in a menu (does not have to be a visible menu)
   *
   * @param \WP_Post|int $post
   * @param string $menu
   * @return bool
   */
  static public function menuPagesOnly($post,$menu)
  {
    if ( is_array($menu) ) {
      $ids = [];

      foreach( $menu as $menuId ) {
        $ids = array_merge($ids,self::buildMenuPageList($menuId));
      }
    } else {
      $ids = self::buildMenuPageList($menu);
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

  /**
   * Only rewrite pages that are a subpage of the main menu items
   *
   * @param \WP_Post|int $post
   * @param string $menu
   * @return bool
   */
  static public function subMenuPagesOnly($post,$menu)
  {
    if ( is_array($menu) ) {
      $ids = [];

      foreach( $menu as $menuId ) {
        $pages = self::buildSubMenuPageList($menuId);

        foreach( $pages as $parent => $subpages ) {
          if ( isset($ids[$parent]) ) {
            $ids[$parent] = array_unique(array_merge($ids[$parent],$subpages));
          } else {
            $ids[$parent] = $subpages;
          }
        }
      }
    } else {
      $ids = self::buildSubPMenuageList($menu);
    }

    $subpages = Arr::flatten($ids);

    if ( $post instanceof \WP_Post ) {
      $postId = $post->ID;
    } else {
      $postId = $post;
    }

    return in_array($postId,$subpages);
  }

  static protected function buildSubMenuPageList($menu)
  {
    self::buildMenuPageList($menu);

    return self::$menuSubPages;
  }

  static protected function buildMenuPageList($menu)
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

  static private function getSubPagesOf($pageId)
  {
    var_dump($pageId);
    $pages = get_pages([
      'parent'    => $pageId,
    ]);

    array_walk($pages,function(&$element,$key) {
      $element = $element->ID;
    });
    return $pages;
  }

}