<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Theme\Feature;


class BugherdFeature implements FeatureInterface
{
  public function register($options = [])
  {
    if ( isset($options['key'])) {
      $apiKey = $options['key'];
    } else if ( isset($options[0])) {
      $apiKey = $options[0];
    } else if ( empty($options)) {
      $apiKey = $options;
    }

    add_action('wp_head',function() use ($apiKey) {
      ?>
      <script type='text/javascript'>
        (function (d, t) {
          var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
          bh.type = 'text/javascript';
          bh.src = 'https://www.bugherd.com/sidebarv2.js?apikey=<?php echo addslashes($apiKey); ?>';
          s.parentNode.insertBefore(bh, s);
        })(document, 'script');
      </script>
      <?php
    });
  }


}