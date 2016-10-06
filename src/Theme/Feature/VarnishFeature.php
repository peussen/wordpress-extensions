<?php
/*
 * @author: petereussen <peter.eussen@harperjones.nl>
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;

/**
 * Adds support for varnish caching
 * When enabled, this feature will  hook into various actions to detect
 * changes to posts, and initiate a flush when it happens, so the cache is never
 * out of date.
 *
 * To enable this, add the following line to your theme init:
 *
 * <code>
 * add_theme_support('harperjones-varnish');
 * </code>
 *
 * Additionally you can add extra configuration options:
 * - server: the server to use to initiate purge commands
 * - port: the port to use to initiate a purge request
 * - host: the domain to use as purge filter
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class VarnishFeature implements FeatureInterface
{
    protected $varnishServerIP = false;
    protected $varnishPort     = false;
    protected $flushHost       = false;
    protected $noticeResponse  = false;
    protected $actions         = array(
        'save_post',
        'deleted_post',
        'trashed_post',
        'edit_post',
        'delete_attachment',
        'switch_theme',
        'publish_post',
        'transition_post_status',
        'enable-media-replace-upload-done'
    );

    public function register($options = [])
    {
        if (isset($options['server'])) {
            $this->setVarnishIp($options['server']);
        }

        if ( isset($options['port'])) {
            $this->setVarnishPort($options['port']);
        }

        if ( isset($options['host'])) {
            $this->setHost($options['host']);
        }

        if ($this->varnishServerIP || $this->detectVarnishSetup()) {
            $this->addHooks();
        }
    }

    /**
     * Changes the varnish IP used for purges to the one given
     *
     * @param string $ip
     */
    public function setVarnishIp( $ip )
    {
        $this->varnishServerIP = apply_filters('hj/varnish/server/ip',$ip);
    }

    /**
     * Changes the port the varnish server listens on to the one specified
     *
     * @param int $port
     */
    public function setVarnishPort( $port )
    {
        $this->varnishPost = apply_filters('hj/varnish/server/port',$port);
    }

    /**
     * Change the default domain to use for flushing site content
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $parts = explode('.',$host);
        $root  = array_pop($parts);
        $dom   = array_pop($parts);

        $this->flushHost = $dom . '.' . $root;
    }

    /**
     * execute a flush whenever a post has changed
     *
     */
    public function executeFlush()
    {
        // Bail out when we have not found a varnish server
        if ( empty($this->varnishServerIP)) {
            return;
        }

        $url          = 'http://' . $this->varnishServerIP . ($this->varnishPort ? ':' . $this->varnishPort : '');
        $purgeRequest = [
            'method'    => 'PURGE',
            'headers'   => [
                'host'              => apply_filters('hj/varnish/purgedomain',$this->flushHost),
                'X-Purge-Method'    => 'regex'
            ]
        ];

        $response = wp_remote_request($url,$purgeRequest);

        if ( is_wp_error($response)) {
          add_option('hj-varnish-error',['code' => 500, 'message' => $response->get_error_message()]);
        }
        else if ( isset($response['response']['code']) && $response['response']['code'] !== 200) {
            add_option('hj-varnish-error',$response['response']);
        } else {
            add_option('hj-varnish-error',['code' => 200, 'message' => $this->flushHost]);
        }

    }

    /**
     * Display admin notice so we can notify the admin's in case flush failed
     *
     * @since 0.3.4: also show succesful messages
     */
    public function displayNotice()
    {
        if ( isset($this->noticeResponse['code']) && $this->noticeResponse['code'] === 200 ) {
            echo '<div class="updated notice"><p>Flushed varnish cache for: ' . $this->noticeResponse['message'] . '</p></div>';
        } else {
            echo '<div class="error notice"><p>Varnish flush failed: ' . $this->noticeResponse['message'] . '</p></div>';
        }
    }

    /**
     * Hook on all specified actions
     */
    private function addHooks()
    {
        foreach ($this->actions as $action) {
            add_action($action,[$this,'executeFlush']);
        }

        $this->noticeResponse = get_option('hj-varnish-error');

        if ( $this->noticeResponse ) {
            add_action('admin_notices',[$this,'displayNotice']);
            delete_option('hj-varnish-error');
        }

        /**
         * Add admin bar flush action
         * @since 0.3.4
         */
        add_action('wp_before_admin_bar_render',[$this,'addAdminBarItem']);
        add_action('wp_ajax_flush_varnish',[$this,'adminFlush']);
    }

    /**
     * Handles Flush Requests from the Admin bar
     *
     * @action wp_ajax_flush_varnish
     * @since 0.3.4
     */
    public function adminFlush()
    {
        if ( is_user_logged_in() && current_user_can('edit_pages') ) {
            $this->executeFlush();
        }

        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : admin_url();
        wp_safe_redirect($url);
        exit();
    }

    /**
     * Add flush option to the admin bar so admins can flush manually
     *
     * @action wp_before_admin_bar_render
     * @since 0.3.4
     */
    public function addAdminBarItem()
    {
        global $wp_admin_bar;

        // We abuse the ajax handler to get back to this feature
        $wp_admin_bar->add_node([
            'id'    => 'varnishflush',
            'title' => 'Flush Cache',
            'href'  => admin_url('admin-ajax.php') . '?action=flush_varnish',
            'parent'=> false,
            //'meta'  => array( 'class' => 'my-toolbar-page' )
        ]);
    }

    /**
     * Try to detect if we have varnish automatically based on the request
     *
     * @return bool
     */
    private function detectVarnishSetup()
    {

        $ip  = getenv('VARNISH_SERVER_IP');

        if ( $ip !== false ) {
            $this->setVarnishIp($ip);
            $this->setVarnishPort(getenv('VARNISH_SERVER_PORT'));
            $this->setHost(parse_url(get_bloginfo('url'),PHP_URL_HOST));
            return true;
        }

        if ( isset($_SERVER['HTTP_X_VARNISH'])) {
            if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && isset($_SERVER['REMOTE_ADDR'])) {
                $this->setVarnishIP($_SERVER['REMOTE_ADDR']);
                $this->setHost(parse_url(get_bloginfo('url'),PHP_URL_HOST));
                return true;
            }
        }
        return false;
    }

}