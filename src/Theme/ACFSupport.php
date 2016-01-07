<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme;


class ACFSupport
{
    static private $fieldFilters = [];

    /**
     * Renders a flexible content container and it's content
     * @param $flexid
     * @param null $postref
     *
     * @globalize acf_flex_container
     */
    static public function flexContainer($flexid, $postref = null)
    {
        $flexboxTplDir = apply_filters('acf/flexblock/template_dir','templates/acf-flexblock/');

        if( have_rows($flexid, $postref) ) {

            echo '<div class="' . implode(' ',apply_filters('acf/flexcontainer/classes',['flex__container', 'flex__container--' . $flexid])) . '">';

            while( have_rows($flexid, $postref) ) {

                the_row();

                $layout  = get_row_layout();
                $classes = apply_filters('acf/flexblock/classes', ['flex__block', 'flex__block--' . $layout, $layout]);

                echo '<section class="' . implode(' ',$classes) . '">';
                get_template_part($flexboxTplDir . '/block',$layout);
                echo '</section>';
            }

            echo "</div>\n";
        }
    }

    /**
     * Adds a filter for an acf field
     *
     * @param string    $acffield
     * @param callable  $callable
     * @param int       $priority (default 10)
     * @globalize acf_add_field_filter
     */
    static public function filterValue($acffield,$callable,$priority = 10)
    {
        if ( !isset(static::$fieldFilters[$acffield][$priority])) {
            add_filter('acf/load_value/?name=' . $acffield,function($content) use ($priority, $acffield) {
                if ( isset(static::$fieldFilters[$acffield][$priority])) {
                    foreach( static::$fieldFilters[$acffield][$priority] as $callable ) {
                        $content = $callable($content);
                    }
                }
                return $content;
            },$priority);
        }
        static::$fieldFilters[$acffield][$priority][] = $callable;
    }

}

