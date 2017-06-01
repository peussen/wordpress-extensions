<?php
/*
 * @author: petereussen
 * @package: woppe2016
 */

namespace Woppe\Wordpress\Theme\Feature;


use Woppe\Wordpress\Search\ExtendedSearch;
use Woppe\Wordpress\Search\ExtendedSearchHelpers;
use Woppe\Wordpress\Setup;

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

        $filters = apply_filters('woppe/search/filters',[]);

        foreach ($filters as $filter ) {
            $search->addFilter($filter);
        }

        $relations = apply_filters('woppe/search/relations',[]);

        foreach( $relations as $field => $postType ) {
            $search->addRelation($field,$postType);
        }
    }

}