<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress;

/**
 * Adds a Google Analytics counter at the end of the page when one is defined
 * You can set the ID by either passing it as a parameter to the construtcor,
 * or by setting a define called GOOGLE_ANALYTICS_ID (old sage way)
 *
 * @package HarperJones\Wordpress
 */
class GoogleAnalytics
{
    /**
     * The Google Analytics ID
     * @var bool|string
     */
    private $uaCode = false;

    /**
     * Enable privacy mode or not
     *
     * @var bool
     */
    private $privacy= true;

    /**
     * Initializes the analytics, should be called from bootstrap
     *
     * @param string    $ga
     * @param array     $options
     */
    public function __construct( $ga = null, $options = [] )
    {
        if ( $ga ) {
            $this->uaCode = $ga;
        } else if ( defined('GOOGLE_ANALYTICS_ID')) {
            $this->uaCode = GOOGLE_ANALYTICS_ID;
        } else {
            throw new \RuntimeException("Google Analytics not configured properly");
        }

        if ( isset($options['privacy'])) {
            $this->privacy = $options['privacy'];
        }

        if ( defined('WP_ENV') && WP_ENV === 'production') {
            \add_action('wp_footer', [$this,'renderGoogleAnalytics']);
        }
    }

    /**
     *
     * @action wp_footer
     */
    public function renderGoogleAnalytics()
    {
        if ( $this->uaCode === false) {
            return;
        }

        if (!current_user_can('manage_options')):
            ?>
            <script>
                (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
                    function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
                    e=o.createElement(i);r=o.getElementsByTagName(i)[0];
                    e.src='//www.google-analytics.com/analytics.js';
                    r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
                <?php else : ?>
                function ga() {
                    if (window.console) {
                        console.log('Google Analytics: ' + [].slice.call(arguments));
                    }
                }
                ga('create','<?php echo $this->uaCode; ?>','auto');
                <?php if ( $this->privacy ): ?>
                ga('set', 'anonymizeIp', true)
                <?php endif; ?>
                ga('send','pageview');
            </script>
            <?php
        endif;
    }
}