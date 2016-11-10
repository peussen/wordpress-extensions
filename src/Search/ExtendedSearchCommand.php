<?php
/*
 * @author: petereussen
 * @package: gfhg2015
 */

namespace HarperJones\Wordpress\Search;

/**
 * Advanced search that takes custom fields and relations into account
 *
 * @package HarperJones\Wordpress\Search
 */
class ExtendedSearchCommand extends \WP_CLI_Command
{
  /**
   * Create a new index based from scratch
   *
   */
  public function index()
  {
    global $post;

    $search = ExtendedSearch::instance();
    $res    = new \WP_Query([
        'post_type'     => $search->getPostTypes(),
        'post_status'   => 'publish',
        'nopaging'      => true,
        'posts_per_page'=> -1
    ]);

    while ($res->have_posts()) {
      $res->the_post();

      $search->updatePostIndex(get_the_ID(),$post);
    }
  }
}
