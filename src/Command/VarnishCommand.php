<?php

namespace HarperJones\Wordpress\Command;


use HarperJones\Wordpress\Setup;

/**
 * Manage Varnish Proxy Server(s)
 *
 * @package HarperJones\Wordpress\Command
 */
class VarnishCommand extends \WP_CLI_Command
{
    /**
     * Flushes the current domain from the varnish server
     *
     */
    public function flush()
    {
        $varnishInfo = Setup::get('varnish');

        if ( isset($varnishInfo['client'])) {
            if (!$varnishInfo['client']->executeFlush()) {
                $error = get_option('hj-varnish-error');
                delete_option('hj-varnish-error');

                \WP_CLI::error("Failed to flush: {$error['message']} ({$error['code']})",true);
            }
            \WP_CLI::success("Flushed");
        } else {
            \WP_CLI::error('No varnish information found',true);
        }
    }
}