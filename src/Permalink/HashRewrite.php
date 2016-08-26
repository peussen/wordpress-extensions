<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Permalink;


class HashRewrite extends AbstractRewriter
{
  function rewritePermalink($permalink, $post, $leavename)
  {
    $relativeLink = ltrim(wp_make_link_relative($permalink),'/');

    if ( $this->needsRewrite($post)) {
      return $this->hashRewrite($relativeLink);
    }
    return $permalink;
  }

  function rewriteTemplateRedirect()
  {
    $post = get_post();

    if ( $this->needsRewrite($post) ) {
      wp_redirect($this->hashRewrite($post->post_name));
      exit();
    }
  }

  function rewriteContentUrls($content)
  {
    $urls = $this->extractHrefUrls($content);

    if ( $urls ) {
      foreach( $urls as $urlMatch ) {
        $new     = $this->hashRewrite($urlMatch['post']->post_name);
        $replaced= str_replace($urlMatch['href'],$new,$urlMatch['match']);
        $content = str_replace($urlMatch['match'],$replaced,$content);
      }
    }
    return $content;
  }

  protected function needsRewrite($post)
  {
    $frontpage = get_option('page_on_front');
    $id        = $post instanceof \WP_Post ? $post->ID : $post;

    if ( $id == $frontpage ) {
      return false;
    }

    return parent::needsRewrite($post);
  }

  protected function hashRewrite($slug)
  {
    return home_url('/') . '#' . $slug;
  }
}