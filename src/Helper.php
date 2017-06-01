<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress;


class Helper
{

    /**
     * Returns the first NON-NULL argument
     *
     * @param ...$options
     * @globalize strict_coalesce
     */
    static public function strictCoalesce( ...$options )
    {
        foreach( $options as $option ) {
            if ( $option !== null ) {
                return $option;
            }
        }
        return $option;
    }

    /**
     * Returns the first non-empty argument
     *
     * @param ...$options
     * @return mixed
     * @globalize coalesce
     */
    static public function coalesce( ...$options )
    {
        foreach( $options as $option ) {
            if ( !empty($option)) {
                return $option;
            }
        }
        return $option;
    }

}