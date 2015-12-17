<?php
/*
 * @author: petereussen
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
    protected $actions         = array(
        'save_post',
        'deleted_post',
        'trashed_post',
        'edit_post',
        'delete_attachment',
        'switch_theme',
        'publish_post',
        'transition_post_status',
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
        $this->varnishServerIP = apply_filters('hj_varnish_server_ip',$ip);
    }

    /**
     * Changes the port the varnish server listens on to the one specified
     *
     * @param int $port
     */
    public function setVarnishPort( $port )
    {
        $this->varnishPost = apply_filters('hj_varnish_server_port',$port);
    }

    /**
     * Change the default domain to use for flushing site content
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->flushHost = $host;
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
                'host'              => apply_filters('hj_varnish_purge_domain',$this->flushHost),
                'X-Purge-Method'    => 'regex'
            ]
        ];

        wp_remote_request($url,$purgeRequest);
    }

    /**
     * Hook on all specified actions
     */
    private function addHooks()
    {
        foreach ($this->actions as $action) {
            add_action($action,[$this,'executeFlush']);
        }
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