<?php
/*
 * @author: petereussen
 * @package: hj2016
 */

namespace HarperJones\Wordpress\Theme\Feature;


use HarperJones\Wordpress\Search\ExtendedSearch;
use HarperJones\Wordpress\Search\ExtendedSearchHelpers;
use HarperJones\Wordpress\Setup;

class ExtendedSearchFeature implements FeatureInterface
{
    protected $postTypes;

    public function register($options = [])
    {
        // Means no additional options were passed
        $this->postTypes = $options;

        add_action('init',[$this,'applyFilters'],30);

        Setup::globalizeStaticMethods(ExtendedSearchHelpers::class);
    }

    public function applyFilters()
    {
        $search = ExtendedSearch::register();

        if ( $this->postTypes === true) {
            $this->postTypes = get_post_types(['public' => true],'names');
        }


        foreach( $this->postTypes as $postType ) {
            $search->addPostType($postType);
        }

        $filters = apply_filters('hj/search/filters',[]);

        foreach ($filters as $filter ) {
            $search->addFilter($filter);
        }

        $relations = apply_filters('hj/search/relations',[]);

        foreach( $relations as $field => $postType ) {
            $search->addRelation($field,$postType);
        }
    }

}