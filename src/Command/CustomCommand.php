<?php
/*
 * @author: petereussen
 * @package: gfhg2015
 */

namespace HarperJones\Wordpress\Command;


/**
 * Handle custom theme functionality
 *
 * @package HarperJones\Wordpress\Command
 */
class CustomCommand extends \WP_CLI_Command
{

    /**
     * Flush custom theme loader caches
     *
     */
    public function flushautoload()
    {
        delete_site_option('hj_autoload-cache-' . wp_get_theme());
        \WP_CLI::success("Ok");
    }
}