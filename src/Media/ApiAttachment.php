<?php
/*
 * @author: petereussen
 * @package: darionwm-portal
 */

namespace HarperJones\Wordpress\Media;

class ApiAttachment extends Attachment
{
  protected $alternates = [];
  protected $guid;

  public function __construct($attachmentId, $size = 'full')
  {
    $this->default = $size;
    $this->id      = $attachmentId;

    $object        = false;

    if ( is_object($attachmentId) ) {
      $object = $attachmentId;
    } else {
      $json = @file_get_contents($attachmentId);

      if ( $json ) {
        $object = json_decode($json);
      }
    }

    if ( $object !== false ) {
      $this->setupApiObject($object);
    }

  }

  public function getUrl($size = false)
  {
    if ( $size === false ) {
      $size = 'full';
    }

    if ( isset($this->alternates[$size])) {
      return $this->alternates[$size]->source_url;
    }
  }

  public function getSrcSet($size = false)
  {
    $sizes = [];
    $srcset= [];


    foreach( $this->alternates as $alternate ) {
      $srcset[] = $alternate->source_url . ' ' . $alternate->width . 'w';
      $sizes[]  = '(max-width: ' . $alternate->width . 'px)';
    }

    return [
      'sizes' => implode(', ',$sizes),
      'srcset'=> implode(', ',$srcset)
    ];
  }

  public function isImage()
  {
    return !empty($this->guid);
  }


  private function setupApiObject($object)
  {
    $this->guid         = isset($object->guid->rendered) ? $object->guid->rendered : false;
    $alternates         = isset($object->media_details->sizes) ? $object->media_details->sizes : [];
    $this->attachmentId = isset($object->id) ? $object->id : false;

    $info = parse_url($this->guid);

    if ( isset($info) ) {
      $baseUrl = $info['scheme'] . '://' . $info['host'] . (isset($info['port']) ? ':' . $info['port'] : '');

      $this->alternates = get_object_vars($alternates);
      foreach( $this->alternates as $name => $alternate ) {
        $this->alternates[$name]->source_url = $baseUrl . $this->alternates[$name]->source_url;
      }
    }

//    var_dump($this);
  }
}