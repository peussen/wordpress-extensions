<?php
/*
 * @author: petereussen
 * @package: woppe2016
 */

namespace Woppe\Wordpress\Search;


class ExtendedSearchHelpers
{
    /**
     * Searches in a specific posttype
     * @param $postType
     * @return \WP_Query
     * @globalize woppe_search_posttype
     */
    static public function searchInPostType($postType)
    {
        return ExtendedSearch::instance()->getResults($postType);
    }

    /**
     * Custom search with other search keywords than entered in the site searchbox
     *
     * @param string $search
     * @param string|array $postType
     * @return \WP_Query
     * @globalize woppe_search
     */
    static public function search($search, $postType)
    {
        return ExtendedSearch::instance()->getCustomResults($search,$postType);
    }

}