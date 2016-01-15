<?php
/*
 * @author: petereussen
 * @package: gfhg2015
 */

namespace HarperJones\Wordpress\Search;


class ExtendedSearch
{
    static private $instance = null;

    /**
     * List of supported posttypes
     * @var array
     */
    private $indexedPostTypes = [];

    /**
     * Functions that should be applied to the stored data
     * @var array
     */
    private $filterFunctions  = [];

    /**
     * List of relations between posttypes
     * @var array
     */
    private $relations        = [];

    /**
     * Sanity check to ensure we do not register ourselves more than once
     *
     * @var bool
     */
    private $registered       = false;

    /**
     * List of results of the last global search query
     *
     * @var array
     */
    private $lastResult       = [];

    /**
     * Search mode (BOOLEAN or NATURAL LANGUAGE)
     *
     * @var bool|string
     */
    private $booleanmode      = false;

    /**
     * Name of the search table
     *
     * @var string
     */
    private $searchtable;

    protected function __construct()
    {
        global $wpdb;

        $this->booleanmode = getenv('COMPLEXSEARCH_BOOLEAN_MODE');
        $this->searchtable = $wpdb->prefix . '_index';
    }

    public function hook()
    {
        if ( !$this->registered ) {
            add_action('save_post', [$this, 'updatePostIndex'],10,3);
            add_action('delete_post', [$this, 'removeFromIndex']);
            add_filter('pre_get_posts', [$this,'pre_get_posts'],50);
            add_filter('posts_where', [$this, 'alterWhereQuery']);
            add_filter('posts_orderby', [$this,'alterOrderQuery']);

            if ( defined('WP_CLI') && WP_CLI ) {
                \WP_CLI::add_command('complexsearch', __NAMESPACE__ . '\\ExtendedSearchCommand');
            }

            $this->registered = true;
        }
        return $this;
    }

    public function alterWhereQuery($where)
    {
        global $wp_query;

        if ( $wp_query->is_main_query() && $wp_query->is_search ) {

            if ( $wp_query->query_vars['post__in'] ) {
                $where = sprintf(" AND (wp_posts.ID IN (%s) OR
                              wp_posts.post_title LIKE '%s' OR
                              wp_posts.post_content LIKE '%s'
                            )
                            AND wp_posts.post_type IN ('%s')
                            AND wp_posts.post_status IN ('%s')",
                    implode(',',$wp_query->query_vars['post__in']),
                    '%' . esc_sql($wp_query->query_vars['s']) . '%',
                    '%' . esc_sql($wp_query->query_vars['s']) . '%',
                    implode("','", $wp_query->query_vars['post_type']),
                    implode("','", $wp_query->query_vars['post_status'])
                );

            }
        }
        return $where;
    }

    public function alterOrderQuery($orderBy)
    {
        global $wp_query;

        if ( $wp_query->is_main_query() && $wp_query->is_search ) {
                return ' wp_posts.post_date DESC ';
        }
    }

    /**
     * Adjust search so it will only return posttypes in our index
     *
     * @param \WP_Query $query
     * @return \WP_Query
     */
    public function pre_get_posts(\WP_Query $query)
    {
        if (!is_admin() && $query->is_main_query()) {
            if ($query->is_search) {
                $related = $this->search($query->query_vars['s']);

                $query->set('post__in',$related);
                $query->set('post_type', $this->getPostTypes());
                $query->set('post_status', ['publish']);

            }
        }
        return $query;
    }

    /**
     * Return all results matching for a specific post type
     *
     * @param   string|array $postType
     * @return  \WP_Query
     */
    public function getResults($postType)
    {
        global $wp_query;

        $items = $this->search($wp_query->query_vars['s'],$postType);

        return new \WP_Query(['post__in' => $items, 'post_type' => $postType]);
    }

    /**
     * Search for a custom search string
     *
     * @param $search
     * @param string|array $postType
     * @return \WP_Query
     */
    public function getCustomResults($search,$postType = null)
    {
        $items = $this->search($search,$postType);

        return new \WP_Query(['post__in' => $items]);
    }

    /**
     *
     * @param string $keyword
     * @param null $postType
     * @return array|mixed
     */
    public function search($keyword = '', $postType = null)
    {
        global $wpdb;

        if ( $keyword === '') {
            return $this->lastResult;
        }

        $storeResult = true;
        $cacheKey    = 'extendedsearch_kw_' . ($postType === null ? '' : $postType . '_') . $keyword;
        $related     = get_transient($cacheKey);

        if ( $related ) {
            $this->lastResult = $related;
            return $related;
        }

        $mode   = ($this->booleanmode ? "BOOLEAN" : "NATURAL LANGUAGE" );
        $filter = '';

        if ( $postType !== null ) {
            $in = [];
            foreach( (array)$postType as $pt ) {
                $in[] = esc_sql($pt);
            }

            $filter      = " AND post_type IN ('" . implode("','",$in) . "')";
            $storeResult = false;
        }


        $sql = $wpdb->prepare('SELECT post_id, MATCH(content) AGAINST(%s IN ' . $mode . ' MODE) AS relevance
              FROM ' . $this->searchtable . '
             WHERE MATCH (content) AGAINST (%s IN ' . $mode . ' MODE) ' . $filter . '
             ORDER BY relevance DESC;',
            $keyword,
            $keyword
        );

        $res = $wpdb->get_col($sql,0);

        set_transient($cacheKey,$res,300);

        if ( $storeResult ) {
            $this->lastResult = $res;
        }
        return $res;
    }

    public function addRelation($field,$postType)
    {
        $this->relations[$field] = $postType;
    }

    public function addPostType($postType)
    {
        $this->indexedPostTypes[] = $postType;
    }

    public function getPostTypes()
    {
        return $this->indexedPostTypes;
    }

    public function addFilter($callable)
    {
        if ( is_callable($callable)) {
            $this->filterFunctions[] = $callable;
            return $this;
        }
        return false;
    }

    public function deletePost($post_id)
    {
        global $wpdb;

        $this->createIndexTable();
        $wpdb->delete($this->searchtable,['post_id' => $post_id]);
    }

    public function updatePostIndex($post_id,$post, $update = true)
    {
        global $wpdb;

        $this->deletePost($post_id);

        if ( in_array($post->post_type,$this->indexedPostTypes) && $post->post_status === 'publish') {
            if ( function_exists('get_fields')) {
                $fields = get_fields();
            } else {
                $fields = [];
            }

            $fields['post_title']   = $post->post_title;
            $fields['post_content'] = strip_tags($post->post_content);
            $fields['post_excerpt'] = strip_tags($post->post_excerpt);

            foreach( $this->relations as $field => $value ) {
                if ( isset($fields[$field])) {
                    if ( $value instanceof \WP_Post ) {
                        $post = $value;
                    } else {
                        $post = get_post($value);
                    }

                    if ( $post ) {
                        $fields[$field] = get_fields($post->ID);
                        $fields[$field]['title']    = $post->post_title;
                        $fields[$field]['content']  = $post->post_content;
                        $fields[$field]['excerpt']  = $post->post_excerpt;
                    } else {
                        $fields[$field] = '';
                    }
                }
            }

            foreach( $this->filterFunctions as $filterCallable ) {
                $fields = $filterCallable($fields,$post);
            }

            $content = apply_filters($this->searchtable_post,$fields,$post);

            $wpdb->insert($this->searchtable,
                [
                    'post_id'   => $post_id,
                    'post_type' => get_post_type($post_id),
                    'content'   => $this->flatten($content),
                ]
            );
        }
    }

    private function flatten(array $content)
    {
        $text = '';

        foreach( $content as $values ) {
            if (is_array($values)) {
                $text .= "\n" . $this->flatten($values);
            } elseif ($values instanceof \WP_Post) {
                $text .= "\n" . $values->post_title . "\n" .
                    $values->post_content . "\n" .
                    $values->post_excerpt;
            } elseif (is_object($values)) {
                $objectVars = get_object_vars($values);

                foreach( $objectVars as $value ) {
                    $text .= "\n" . $this->flatten($value);
                }
            } else {
                $text .= "\n" . $values;
            }
        }

        return trim(strip_tags($text));
    }

    private function createIndexTable()
    {
        global $wpdb;

        $wpdb->query('
            CREATE TABLE IF NOT EXISTS ' . $this->searchtable . ' (
              post_id INT NOT NULL,
              post_type VARCHAR(20),
              content TEXT NOT NULL,
              updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (post_id),
              FULLTEXT ft_content (content ASC)) ENGINE MyISAM;
        ');
    }

    /**
     * Gets the instance of deepsearch
     *
     * @return ExtendedSearch
     */
    static public function instance()
    {
        if ( self::$instance === null ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    static public function register()
    {
        return static::instance()->hook();
    }

}

