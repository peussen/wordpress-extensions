<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\AccessControl;

/**
 * Allow access restriction on a per instance and role way
 * With this class you will create the ability to specify on a per Post kind of way which roles can view
 * which objects. This may come in handy when you have for example two groups of users which may not see
 * eachothers information.
 *
 * Difference between this and MasterAdmin is that this will limit viewing of an object, but not the
 * maintenance of an object, whereas MasterAdmin will limit administration/maintenance of an object.
 *
 * @package   HarperJones\Wordpress\AccessControl
 * @requires  advancedcustomfields
 */
class RoleAccess
{
  /**
   * Has the access restriction been initialized or not
   *
   * @var bool
   */
  static private $initialized = false;

  /**
   * A list of post types which should be restricted
   *
   * @var array
   */
  static protected $posttypes = [];


  /**
   * INitializes the RoleAccess object.
   * On the first call you can/should pass a list of post types that should be access
   * restricted (for example ['page','post'], after that you can simply construct it
   * to get access to the checkAccess() method for example
   *
   * @param array $posttypes
   */
  public function __construct($posttypes = [])
  {
    // Call construction only once
    if ( !self::$initialized ) {
      add_action('template_redirect',[$this,'checkAccess']);

      if (is_admin()) {
        add_action('admin_init',[$this,'addACFFieldBox'],20);
        add_action('acf/load_field/name=hj_access_roles',[$this,'addAvailableRoles']);
      }
      self::$initialized = true;
      self::$posttypes   = $posttypes;
    }
  }

  /**
   * Adds a specific post type to the list of supported post types
   * This method only makes sense before the 'init' action has been called.
   *
   * @param $pt
   * @return RoleAccess
   */
  public function addPostType($pt)
  {
    if ( did_action('admin_init')) {
      _doing_it_wrong(__METHOD__,'Adding posttypes should be done before the "init" action fired');
    }
    self::$posttypes[] = $pt;
    return $this;
  }

  /**
   * Checks if someone has access to a page based the role rights for the specific post type
   * The check logic is as follows:
   * 1) If no roles are required: all can watch
   * 2) If a role is required:
   *    - The user needs to be logged in
   *    - The user should have the specific role, or a "Super" role
   *
   * @filter harperjones/roleaccess/fallbackurl   The URL to redirect if access not allowed
   * @filter harperjones/roleaccess/redirectcode  The Redirect status code (default 302)
   * @filter harperjones/roleaccess/superroles    Roles that can always view the object
   */
  public function checkAccess()
  {
    if ( in_array(get_post_type(),static::$posttypes) && function_exists('get_field')) {
      $roles = get_field('hj_access_roles',get_the_ID());
      $allow = true;

      if ( $roles && !is_user_logged_in()) {
        $allow = false;
      } elseif ( $roles && is_user_logged_in() ) {
        /**
         * Filter the roles that should be considered "Super roles"
         *
         * @since 0.6.5
         *
         * @param   array $allowedRoles   array of roles that should be super. Defaults to ['administrator']
         * @return  array
         */
        $allowedRoles = apply_filters('harperjones/roleaccess/superroles',['administrator']);
        $user         = wp_get_current_user();
        $allow        = (count($roles) == 0) || array_intersect($allowedRoles,$user->roles);

        if ( $roles ) {
          foreach( $roles as $allowedRole ) {
            if ( in_array($allowedRole,(array)$user->roles)) {
              $allow = true;
            }
          }
        }
      }

      if ( !$allow ) {
        wp_redirect(
          /**
           * Filter the URL the not-allowed users should be forwarded to
           *
           * @since 0.6.5
           *
           * @param   string  $url    Defaults to home_url()
           * @return  string
           */
          apply_filters('harperjones/roleaccess/fallbackurl',home_url()),

          /**
           * Filter the redirect status
           *
           * @since 0.6.5
           *
           * @param   int $httpStatus   Defaults to 302
           * @return  int
           */
          apply_filters('harperjones/roleaccess/redirectcode',302)
        );
        exit();
      }
    }
  }

  /**
   * Popuplates the ACF access option box
   *
   * @param array $field
   * @return array
   */
  public function addAvailableRoles($field)
  {
    global $wp_roles;

    $field['choices'] = [];

    foreach( $wp_roles->roles as $key => $roleObject ) {
      if ( $key !== 'administrator') {
        $field['choices'][$key] = $roleObject['name'];
      }
    }
    return $field;
  }

  /**
   * Creates the ACF box that in the admin area
   *
   * @requires advancedcustomfields
   * @filter   harperjones/roleaccess/boxlabels   The labels used in the box
   * @filter   harperjones/roleaccess/boxlocation The ACF Rules that determine where the box should be displayed
   */
  public function addACFFieldBox()
  {
    if( function_exists('acf_add_local_field_group') ) {


      $locations = [];

      /**
       * Filter the labels used in the ACF fieldgroup setup
       *
       * @param array $labels
       * @return array
       */
      $labels    = apply_filters('harperjones/roleaccess/boxlabels',
        [
          'title' => __('Access'),
          'field' => __('Roles'),
        ]
      );

      foreach( self::$posttypes as $pt ) {
        $locations[] = [
          [
            'param'     => 'post_type',
            'operator'  => '==',
            'value'     => $pt
          ]
        ];
      }

      /**
       * Filter to change the locations where the box should be displayed
       *
       * @since 0.6.5
       *
       * @param array $locations
       * @return array
       */
      $locations = apply_filters('harperjones/roleaccess/boxlocation',$locations);

      $setup     = [
        'key' => 'group_585bc087e240c',
        'title' => $labels['title'],
        'fields' => [
          [
            'layout' => 'vertical',
            'choices' => [],
            'default_value' => [],
            'allow_custom' => 0,
            'save_custom' => 0,
            'toggle' => 0,
            'return_format' => 'value',
            'key' => 'field_585bc0a6b8119',
            'label' => $labels['field'],
            'name' => 'hj_access_roles',
            'type' => 'checkbox',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
              'width' => '',
              'class' => '',
              'id' => '',
            ],
          ],
        ],
        'location' => $locations,
        'menu_order' => 0,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => 1,
        'description' => '',
      ];

      /**
       * Filter the whole ACF setup array
       *
       * @since 0.6.5
       *
       * @param   array $setup
       * @return  array
       */
      $setup = apply_filters('harperjones/roleaccess/acfsetup',$setup);
      acf_add_local_field_group($setup);
    }
  }
}

