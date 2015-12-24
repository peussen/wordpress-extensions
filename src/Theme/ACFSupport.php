<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme;


class ACFSupport
{
    /**
     * @param $flexid
     * @param null $postref
     *
     * @globalize flex_container
     */
    static public function flex_container($flexid, $postref = null)
    {
        $flexboxTplDir = apply_filters('acf-flex-block-template-dir','templates/acf-flexblock/');

        if( have_rows($flexid, $postref) ) {

            echo '<div class="' . implode(' ',apply_filters('acf-flex-container-classes',['flex__container', 'flex__container--' . $flexid])) . '">';

            while( have_rows($flexid, $postref) ) {

                the_row();

                $layout  = get_row_layout();
                $classes = apply_filters('acf-flexbox-classes', ['flex__block', 'flex__block--' . $layout, $layout]);

                echo '<section class="' . implode(' ',$classes) . '">';
                get_template_part($flexboxTplDir . '/block',$layout);
                echo '</section>';
            }

            echo "</div>\n";
        }
    }


}

