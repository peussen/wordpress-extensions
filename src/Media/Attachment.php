<?php
namespace HarperJones\Wordpress\Media;

use HarperJones\Wordpress\Arr;

/**
 * Wordpress Media Attachment wrapper
 *
 * @package HarperJones\Wordpress\Media
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

    public function __toString()
    {
        if ( $this->valid()) {

            if ( $this->isImage()) {
                return $this->displayImg($this->default,[],true);
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
}