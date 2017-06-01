<?php
namespace Woppe\Wordpress\Media;

use Woppe\Wordpress\Arr;

/**
 * Wordpress Media Attachment wrapper
 *
 * @package Woppe\Wordpress\Media
 * @todo: force maximum size of sourceset images
 */
class Attachment
{
    /**
     * The attachment ID or object
     *
     * @var bool|int|array
     */
    protected $attachmentId = false;

    /**
     * The default size of images
     *
     * @var bool|string
     */
    protected $default = false;

    /**
     * Attachment constructor.
     *
     * @param int|array $attachmentId
     * @param string    $size
     */
    public function __construct($attachmentId, $size = 'medium')
    {
        $this->attachmentId = $attachmentId;
        $this->default      = $size;
    }

    public function getPath()
    {
      if ( $this->valid()) {
        return get_attached_file($this->attachmentId);
      }
      return false;
    }

    public function getMimeType()
    {
      return get_post_mime_type($this->attachmentId);
    }

    public function __toString()
    {
        if ( $this->valid()) {

            if ( $this->isImage()) {
                return $this->displayPicture([],'',$this->default,true);
                //return $this->displayImg($this->default,[],true);
            } else {
                return $this->displayMedia([],true);
            }

        }

        if ( WP_DEBUG ) {
            return '<!-- No image -->';
        }
        return '';
    }

    /**
     * Check if the attachment is valid
     *
     * @return bool
     */
    public function valid()
    {
        return
          (is_array($this->attachmentId) && isset($this->attachmentId['ID'])) ||
          (is_numeric($this->attachmentId) && $this->attachmentId > 0);
    }

  /**
   * Display a <picture> structured image set
   *
   * @param array $classes
   * @param string $alt
   * @param int|false $size
   * @param bool $return
   * @return bool|string
   */
    public function displayPicture($classes = [], $alt = '', $size = false, $return = false)
    {
      if ( $this->attachmentId === false || !$this->isImage()) {
        $html = WP_DEBUG ? '<!-- Invalid Attachment -->' : '';
      } else {
        $url    = $this->getUrl($size ?: $this->default);
        $srcset = $this->getSrcSet($size ?: $this->default);
        $parts  = explode(', ',$srcset['srcset']);

        $sources= [];

        foreach( $parts as $part ) {
          $mqPart = trim(substr($part,strrpos($part,' ') + 1));
          $urlPart= trim(substr($part,0,strrpos($part,' ') + 1));

          if ( $urlPart !== $url ) {
            $sources[(int)$mqPart] = $urlPart;
          }
        }

        ksort($sources);

        /*
         * Default filter for all attachment sources
         */
        $sources = apply_filters('attachment/picture/sources',$sources);

        /*
         * Filter for all pictures with a specific size
         */
        $sources = apply_filters('attachment/picture/sources/' . ($size ?: $this->default ),$sources);

        /*
         * Filter for this specific attachment
         */
        $sources = apply_filters('attachment/picture/sources/' . $this->attachmentId,$sources);

        /*
         * Add classes to the set
         */
        $classes = apply_filters('attachment/picture/classes', $classes);

        $html = "<picture>\n";

        foreach( $sources as $mq => $source ) {
          $html .= sprintf(
            '<source srcset="%s" media="(max-width: %spx)" type="%s">' . "\n",
            $source,
            $mq,
            $this->deriveContentType($source)
          );
        }

        $html .= sprintf(
          '<img src="%s" class="%s" alt="%s"/>' . "\n",
          $this->getUrl('full'),
          implode(' ',(array)$classes),
          $alt
        );

        $html .= '</picture>';
      }

      $html = apply_filters('attachment/picture',$html);

      if ( $return ) {
        return $html;
      }
      echo $html;
      return true;
    }

    /**
     * Returns an image with srcset information
     *
     * @param bool|false $size
     * @param array $classes
     * @param bool|false $return
     * @return string
     */
    public function displayImg($size = false, $classes = [],$return = false)
    {
        if ( $this->attachmentId === false || !$this->isImage()) {
            $html = '';
        } else {
            $url    = $this->getUrl($size ?: $this->default);
            $srcset = $this->getSrcSet($size ?: $this->default);

            $html = '<img class="' . implode($classes) .
              '" src="' . $url .
              '" srcset="' . $srcset['srcset'] .
              '" sizes="' . $srcset['sizes'] . '">';

        }

        $html = apply_filters('attachment/image',$html);

        if ( $return ) {
            return $html;
        }
        echo $html;
        return true;
    }

    /**
     * Returns an absolute URL to the attachment
     *
     * @param bool|false $size
     * @return bool|mixed
     */
    public function getUrl($size = false)
    {
        if ( $this->attachmentId === false ) {
            return false;
        }


        if ( $this->isImage() ) {
            return wp_get_attachment_image_url(
              $this->attachmentId,
              $size ?: $this->default
            );
        } else {
            if ( is_array($this->attachmentId)) {
                return Arr::value($this->attachmentId,'url');
            } else {
                return wp_get_attachment_url($this->attachmentId);
            }
        }
    }

    /**
     * Returns true if the object is an image
     *
     * @return bool
     */
    public function isImage()
    {
        return wp_attachment_is_image($this->attachmentId);
    }

    /**
     * Returns sourceset information for images
     *
     * @param bool|false $size
     * @return array|bool
     */
    public function getSrcSet($size = false)
    {
        if ( $this->attachmentId === false ) {
            return false;
        }

        if ( !$this->isImage()) {
            return false;
        }

        return [
          'srcset' => wp_get_attachment_image_srcset($this->attachmentId,$size ?: $this->default),
          'sizes'  => wp_get_attachment_image_sizes($this->attachmentId,$size ?: $this->default)
        ];
    }

    /**
     * Returns a link to a media file
     *
     * @param array $classes
     * @param bool|true $return
     * @return string
     */
    public function displayMedia($classes = [], $return = false)
    {
        if ( !$this->valid() ) {
            $html = '';
        } else {
            $html = wp_get_attachment_link($this->attachmentId);
            $class= 'class="' . implode(' ',$classes) . '"';
            $html = str_replace('title=',$class . ' title=',$html);
        }

        if ( $return ) {
            return $html;
        }
        echo $html;
        return true;
    }

    private function deriveContentType($url)
    {
      $ext = strtolower(substr($url,strrpos($url,'.') + 1));

      switch( $ext ) {
        case 'jpg':
        case 'jpeg':
          return 'image/jpeg';
        case 'svg':
          return 'image/svg+xml';
        case 'png':
          return 'image/png';
        case 'gif':
          return 'image/gif';
        case 'webp':
          return  'image/webp';
        default:
          return 'application/octet-stream';
      }

    }
}