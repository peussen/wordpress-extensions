<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;

use HarperJones\Wordpress\Arr;

/**
 * Will redirect a user when he tries to access a page he has no access to
 *
 * This feature accepts one parameter: a page Id of a page to show
 * when the user tried to reach a restricted page. This page will also get
 * a 'ref' url parameter so you can use it to redirect the user back after
 * login for example.
 *
 * The page ID can be passed as parameter, or as 'page' entry of an
 * associative array of options, or by defining a theme option called
 * 'harperjones_redirect_page'. (this should be an option on an options page
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class EnableRestrictedPageFeature implements FeatureInterface
{
  protected $redirectPageId;

  public function register($options = [])
  {
    if (isset($options['page'])) {
      $this->redirectPageId = $options['page'];
    } else if ( $options ) {
      $this->redirectPageId = array_shift($options);
    } elseif ( function_exists('get_field')) {
      $this->redirectPageId = get_field('harperjones_redirect_page','option');
    }

    add_action('template_redirect',[$this,'handle404Redirection'],10);
  }

  /**
   * Does the actual redirect handling
   *
   * @action template_redirect
   */
  public function handle404Redirection()
  {
    // Only try to work it out if a redirection page was specified
    if ( is_404() && $this->redirectPageId ) {
      $reqUrl = Arr::value($_SERVER,'REQUEST_URI');
      $postId = url_to_postid($reqUrl);

      if ( $postId && !current_user_can('read', $postId) && $postId != $this->redirectPageId ) {
        $permalink = get_permalink($this->redirectPageId);
        $permalink = add_query_arg('ref',$reqUrl,$permalink);
        wp_redirect($permalink);
        exit();
      }
    }
  }
}