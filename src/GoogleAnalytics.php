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
     * What to use as cookie domain
     *
     * @var string
     */
    public $cookieDomain = false;

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

        if ( isset($options['domain'])) {
            $this->cookieDomain = $options['domain'];
        }

        if ( defined('WP_ENV') && WP_ENV === 'production') {
            add_action('wp_head', [$this,'renderGoogleAnalytics']);
        }

        if ( defined("WP_DEBUG") && WP_DEBUG ) {
            add_action('wp_head', [$this, 'showDebug']);
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
                ga('create','<?php echo $this->uaCode; ?>','<?php echo ($this->cookieDomain ? $this->cookieDomain : 'auto') ?>');
                <?php if ( $this->privacy ): ?>
                ga('set', 'anonymizeIp', true);
                <?php endif; ?>
                ga('send','pageview');
            </script>
            <?php
        endif;
    }

    public function showDebug()
    {
        echo '<!-- GA: ' . $this->uaCode .
             ' privacy: ' . ($this->privacy ? 'true' : 'false') .
             ' domain: ' . $this->cookieDomain . " -->\n";
    }
}