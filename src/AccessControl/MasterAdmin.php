<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\AccessControl;

/**
 * Master Administration: allow the hiding of objects for normal administrators to avoid messups
 * Sometimes you have a website/theme that requires a couple of pages to always exist and you
 * want to prevent the end administrator from messing it up. This class will give you all the
 * tooling needed to avoid that.
 * It will create a "Super Admin" role, which basically copies the Administrator role, and creates
 * a category with the option to indicate that the current object is a "system" object. If it is:
 * only the super admin will have access to the object.
 *
 * Difference between this and RoleAccess is that this limits access to the ADMIN part of the object
 * whereas the RoleAccess limits access to viewing of the object
 *
 * @package HarperJones\Wordpress\AccessControl
 */
class MasterAdmin
{
  const SUPERADMIN_ID       = 'masteradmin';
  const SUPERADMIN_NAME     = 'Master Administrator';
  const SUPERADMIN_CATEGORY = 'masteradmin_settings';

  const SYSTEM_POST_SLUG  = 'system';
  const SYSTEM_POST_NAME  = 'System item';

  /**
   * Post Types that support the System attribute
   *
   * @var array
   */
  protected $_systemSupport= ['page'];

  /**
   * The actual instance
   *
   * @var MasterAdmin
   */
  private static $instance;

  /**
   * Protected to make sure you can not call it directly. user MasterAdmin::bootstrap()
   *
   * @see MasterAdmin::bootstrap()
   */
  protected function __construct()
  {
    // Ensure the master role is there
    Role::create(self::SUPERADMIN_ID,self::SUPERADMIN_NAME,'administrator');

    add_action('init',[$this,'createSuperAdminCategory']);
    add_action('registered_taxonomy',[$this,'ensureSystemTerm'],10,1);
    add_action('pre_get_posts',[$this,'hideSystemObjects']);

    if ( is_admin() ) {
      add_action('current_screen',[$this,'checkScreenAccess']);
      add_action('admin_bar_menu',[$this,'modifyToolbar'],900);
    }
  }

  /**
   * Initializer
   *
   * @return MasterAdmin|static
   */
  static public function bootstrap()
  {
    if ( self::$instance === null ) {
      self::$instance = new static();
    }
    return self::$instance;
  }

  /**
   * Get the role of the master user
   *
   * @return Role
   */
  public function getRole()
  {
    return new Role(self::SUPERADMIN_ID);
  }

  /**
   * Add one or more post types as post types that support Access limitation
   *
   * @param   array|string $postType
   * @return  void
   */
  public function addSystemPostType($postType)
  {
    if ( is_array($postType)) {
      $this->_systemSupport = array_merge($this->_systemSupport,$postType);
      $this->_systemSupport = array_unique($this->_systemSupport);
    }
    if ( !in_array($postType,$this->_systemSupport)) {
      $this->_systemSupport[] = $postType;
    }
  }

  /**
   * Creates the admin category we use to check if someone has access
   * @return void
   */
  public function createSuperAdminCategory()
  {
    $showInUI = current_user_can('be_' . self::SUPERADMIN_ID);

    $labels = array(
      'name'              => __('Admin Categories'),
      'singular_name'     => __('Admin Category'),
      'search_items'      => __('Search Admin Categories'),
      'all_items'         => __('All Admin Categories'),
      'parent_item'       => __('Parent Admin Category'),
      'parent_item_colon' => __('Parent Admin Category:'),
      'edit_item'         => __('Edit Admin Category'),
      'update_item'       => __('Update Admin Category'),
      'add_new_item'      => __('Add New Admin Category'),
      'new_item_name'     => __('New Admin Category Name'),
      'menu_name'         => __('Admin Categories'),
    );
    $args = array(
      'hierarchical'      => true,
      'labels'            => $labels,
      'show_ui'           => $showInUI,
      'query_var'         => true,
      'rewrite'           => array( 'slug' => self::SUPERADMIN_CATEGORY ),
    );


    register_taxonomy( self::SUPERADMIN_CATEGORY,
      $this->getSupportedPostTypes(),
      $args
    );
  }

  /**
   * Makes sure the term for system posts exists in the taxonomy
   *
   * @param string  $taxonomy
   * @return bool
   */
  public function ensureSystemTerm($taxonomy)
  {
    if ( $taxonomy === self::SUPERADMIN_CATEGORY ) {
      $term = get_term_by('slug',self::SYSTEM_POST_SLUG. self::SUPERADMIN_CATEGORY);

      if ( $term === false ) {
        wp_insert_term(self::SYSTEM_POST_NAME,self::SUPERADMIN_CATEGORY, [
          'description' => self::SYSTEM_POST_NAME,
          'slug'        => self::SYSTEM_POST_SLUG
        ]);
      }
    }
    return true;
  }

  /**
   * Remove the edit option if the page is a system page and the current user does not have access
   *
   * @param  \WP_Admin_Bar $wp_admin
   * @return \WP_Admin_Bar
   */
  public function modifyToolbar($wp_admin)
  {
    $page = get_the_ID();

    if ( !$this->canEdit($page)) {
      $wp_admin->remove_node('edit');
    }
    return $wp_admin;
  }

  /**
   * Check if someone has access to a certain screen based on their role/capabilities
   *
   */
  public function checkScreenAccess()
  {
    global $pagenow;

    if ( in_array($pagenow,['post.php'])) {
      $postId = isset($_GET['post'])   ? $_GET['post']   : false;
      $action = isset($_GET['action']) ? $_GET['action'] : false;

      if ( $postId && $action === 'edit' ) {

        if ( !$this->canEdit($postId)) {
          $pt = get_post_type($postId);

          wp_redirect(admin_url('edit.php?post_type=' . $pt));
          exit();
        }
      }
    }
  }

  /**
   * checks if a user can edit a post (or posttype)
   *
   * @param   int $postId
   * @param   int $user (default null)
   * @return  bool
   */
  public function canEdit($postId,$user = null )
  {
    if ( $user === null ) {
      $user = get_current_user_id();
    }

    $pt = get_post_type($postId);

    if ( !in_array($pt,$this->getSupportedPostTypes())) {
      return true;
    }

    $terms = wp_get_object_terms($postId, self::SUPERADMIN_CATEGORY,['fields' => 'slugs']);

    return !in_array(self::SYSTEM_POST_SLUG,$terms) || user_can($user,'be_' . self::SUPERADMIN_ID);
  }

  /**
   * Remove system objects from list views in the admin
   *
   * @param   \WP_Query $query
   * @return  void
   */
  public function hideSystemObjects($query)
  {
    if ( is_admin() &&
      isset($_GET['post_type']) &&
      in_array($_GET['post_type'],$this->getSupportedPostTypes()) &&
      !current_user_can('be_' . self::SUPERADMIN_ID)
    ) {
      $query->set('tax_query', [
        [
          'taxonomy' => self::SUPERADMIN_CATEGORY,
          'field'    => 'slug',
          'terms'    => [ self::SYSTEM_POST_SLUG ],
          'operator' => 'NOT IN'
        ]
      ]);
    }
  }

  /**
   * Get a list of supported post types
   *
   * @filter harperjones/acl/systemobjects
   * @return array
   */
  protected function getSupportedPostTypes()
  {
    /**
     * Filter the post types that should be filtered using the super admin
     *
     * @since 0.6.5
     *
     * @param   array $supportedPostTypes
     * @return  array
     */
    return apply_filters('harperjones/accesscontrol/systemobjects',$this->_systemSupport);
  }

}