<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;


/**
 * Disables comment support for all post types (including built in ones)
 *
 * in your code you should add:
 *
 * <code>
 * add_theme_support('harperjones-disabled-comments');
 * </code>
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class DisabledCommentsFeature implements FeatureInterface
{
    public function register($options = [])
    {
        add_action('init',[$this,'removeCommentSupport'],99);
    }

    public function removeCommentSupport()
    {
        $pt = get_post_types(['_builtin' => true],'names');

        foreach( $pt as $postType ) {
            remove_post_type_support($postType,'comments');
        }
    }

}