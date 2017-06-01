<?php

namespace Woppe\Wordpress\Command;


use Woppe\Wordpress\Setup;

/**
 * Manage Varnish Proxy Server(s)
 *
 * @package Woppe\Wordpress\Command
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

    if (isset($varnishInfo['client'])) {
      if (!$varnishInfo['client']->executeFlush()) {
        $error = get_option('woppe-varnish-error');
        delete_option('woppe-varnish-error');

        \WP_CLI::error("Failed to flush: {$error['message']} ({$error['code']})", true);
      }
      \WP_CLI::success("Flushed");
    } else {
      \WP_CLI::error('No varnish information found', true);
    }
  }
}