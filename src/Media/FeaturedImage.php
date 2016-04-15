<?php
namespace HarperJones\Wordpress\Media;

/*
 * @author: petereussen
 * @package: Inspire
 */

class FeaturedImage extends Attachment
{
  public function __construct($postId, $size = 'medium')
  {
    if ( has_post_thumbnail($postId)) {
      parent::__construct(get_post_thumbnail_id($postId), $size);
    }
  }
}