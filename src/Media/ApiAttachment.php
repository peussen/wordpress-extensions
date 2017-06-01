<?php
/*
 * @author: petereussen
 * @package: darionwm-portal
 */

namespace Woppe\Wordpress\Media;

/**
 * A wrapper to use an external attachment provided by another Wordpress API site
 *
 * @package Woppe\Wordpress\Media
 */
class ApiAttachment extends Attachment
{
  /**
   * List of variants of the image as specified by the API
   * @var array
   */
  protected $alternates = [];

  /**
   * The GUID of the external image
   * @var string
   */
  protected $guid;

  /**
   * Reads the information from the external object and initializes the attachment
   * You can construct the image either by using the API endpoint as "attachmentId"
   * or a json decoded version of the API response.
   *
   * @param array|int $attachmentId
   * @param string $size
   */
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


  /**
   * Initializes the attachment based on the specified object
   * @param $object
   */
  private function setupApiObject($object)
  {
    $this->guid         = isset($object->guid->rendered) ? $object->guid->rendered : false;
    $alternates         = isset($object->media_details->sizes) ? $object->media_details->sizes : [];
    $this->attachmentId = isset($object->id) ? $object->id : false;

    $info = parse_url($this->guid);

    if ( isset($info) ) {
      $baseUrl = $info['scheme'] . '://' . $info['host'] . (isset($info['port']) ? ':' . $info['port'] : '');

      $this->alternates = get_object_vars($alternates);

      // Make sure references to the image are all absolute.
      foreach( $this->alternates as $name => $alternate ) {
        if ( !preg_match("|^http(s)?:|",$alternate->source_url)) {
          $this->alternates[$name]->source_url = $baseUrl . $this->alternates[$name]->source_url;
        }
      }
    }

  }
}