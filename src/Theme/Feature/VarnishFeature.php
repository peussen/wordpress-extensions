<?php
/*
 * @author: petereussen <peter.eussen@harperjones.nl>
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;
use HarperJones\Wordpress\Theme\AccessDeniedException;
use HarperJones\Wordpress\Theme\Admin\Notification;
use HarperJones\Wordpress\Theme\Admin\Toolbar;
use Mockery\Matcher\Not;


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

        $url  = 'http://' . $this->varnishServerIP . ($this->varnishPort ? ':' . $this->varnishPort : '');
        $opts = [
          'http'=>[
            'method'        => 'PURGE',
            'timeout'       => 20,
            'mex_redirects' => 1,
            'header'        => [
              'Host: ' . $this->flushHost,
              'X-Purge-Strategy: ' . apply_filters('hj/varnish/purgestrategy','host'),
              'X-Purge-Regex:' . apply_filters('hj/varnish/purgedomain',$this->flushHost),
            ]
          ]
        ];

        // Why not use wp_request you ask? well, because it does things I did not ask
        // it to do, which causes a weird response from varnish instead of the expected
        // PURGE
        $context  = stream_context_create($opts);
        $response = @file_get_contents($url,false,$context);

        if ( !$response ) {
          Notification::error('Varnish failed to respond');
        } else {
          if ( preg_match('|<h1>(.*?)([0-9]+)(.*)</h1>|ims',$response,$matches)) {
            $status = (int)$matches[2];
            $message= trim($matches[3]);
          } else {
            $status  = 500;
            $message = 'Unknown response from varnish';
          }

          if ( $status == 200 ) {
            Notification::notice('Varnish cache has been flushed');
          } else {
            Notification::error($message);
          }
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

        /**
         * Add admin bar flush action
         * @since 0.3.4
         */
        Toolbar::addItem('varnishflush','Flush Cache',[$this,'adminFlush']);
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
        } else {
          throw new AccessDeniedException(__('You do not have sufficient rights to execute a flush'));
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