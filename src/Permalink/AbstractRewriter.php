<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Permalink;


abstract class AbstractRewriter
{
  protected $filter;
  protected $filterArg;

  public function __construct($what)
  {
    $this->setFilter($what);

    add_action('init',[$this,'init']);
  }

  abstract function rewritePermalink($permalink,$post,$leavename);
  abstract function rewriteContentUrls($content);
  abstract function rewriteTemplateRedirect();

  public function init()
  {
    add_filter('post_link',[$this,'rewritePermalink'],999,3);
    add_filter('the_content', [$this,'rewriteContent'],100);
    add_action('template_redirect',[$this,'rewriteTemplateRedirect'],90);
  }

  public function setFilter($what)
  {
    if ( is_callable($what) ) {
      $this->filter = $what;
      $this->filter = [];
    } else {
      $this->filter    = [ $this, 'genericPTFilter' ];
      $this->filterArg = (array)$what;
    }
  }

  protected function needsRewrite($post)
  {
    $args = $this->filterArg;

    array_unshift($args,$post);

    $value = call_user_func_array($this->filter,$args);

    return ($value !== null);
  }

  protected function genericPTFilter($post, $matchSet )
  {
    $postType = get_post_type($post);

    foreach( $matchSet as $possibleMatch ) {

      if ( is_callable($possibleMatch) ) {
        if ( $possibleMatch($post) ) {
          return $post;
        } elseif ( $possibleMatch === RewriterFactory::REWRITE_ALL ) {
          return $post;
        } elseif ( $postType === $possibleMatch ) {
          return $post;
        }
      }
    }
    return null;
  }

  protected function extractHrefUrls($content)
  {
    $home = rtrim(home_url('/'),'/');

    if ( preg_match_all('/href="([^"]+?)"/ism',$content,$matches)) {

      foreach( $matches[1] as $idx => $matchingUrl ) {
        // Extend URL to be a full permalink url if they were relative
        if ( substr($matchingUrl,0,1) === '/' ) {
          $matches[1][$idx] = $home . $matchingUrl;
        }
      }
      return $matches;
    }
    return false;
  }

}