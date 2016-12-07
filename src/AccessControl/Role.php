<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\AccessControl;


class Role
{
  /**
   * @var \WP_Role
   */
  protected $role;

  /**
   * Role constructor.
   *
   * @param string|\WP_Role $role
   */
  public function __construct($role)
  {
    if ( $role instanceof \WP_Role ) {
      $this->role = $role;
    } else {
      $this->role = get_role($role);
    }
  }

  /**
   * Checks if the role has a certain capability
   */
  public function can($capability)
  {
    if ( $this->role ) {
      return $this->role->has_cap($capability);
    }
    return false;
  }

  /**
   * Add a capability to this role
   *
   * @param $capability
   * @return $this
   */
  public function add($capability)
  {
    if ( $this->role ) {
      $this->role->add_cap($capability,true);
    }
    return $this;
  }

  /**
   * Remove a capability from this role
   *
   * @param $capability
   * @return $this
   */
  public function remove($capability)
  {
    if ( $this->role ) {
      $this->role->remove_cap($capability);
    }
    return $this;
  }

  /**
   * Assign a current user to this role
   *
   * @param   string $username
   * @return  bool
   */
  public function assign($username)
  {
    $user = get_user_by('slug',$username);

    if ( $user ) {
      $userdata = get_userdata($user->ID);

      if ( !in_array($this->role->name,$userdata->roles)) {
        $user = new \WP_User($user->ID);
        $user->set_role($this->role->name);
        return true;
      }
    }
    return false;
  }

  /**
   * Create a role based on an existing other role
   *
   * @param  string       $roleId
   * @param  string       $roleName
   * @param  bool|string  $basedOn
   * @return Role
   */
  static public function create($roleId,$roleName,$basedOn = false)
  {
    $check          = get_role($roleId);

    if ( $check ) {
      return new static($check);
    }

    $capability                = 'be_' . strtolower($roleId);
    $template                  = get_role($basedOn);
    $template_cap              = [];

    if ( $template ) {
      $template_cap              = $template->capabilities;
      $template_cap[$capability] = true;
    }

    $role = add_role($roleId,$roleName,$template_cap);

    return new static($role);
  }
}