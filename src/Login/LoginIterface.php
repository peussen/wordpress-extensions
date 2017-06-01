<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace Woppe\Wordpress\Login;


interface LoginIterface
{
  /**
   * Wrapper function that displays the login form
   *
   * @return void
   */
  static public function displayLoginForm();

  /**
   * Wrapper function that displays the password reset request form
   *
   * @return void
   */
  static public function displayPasswordResetForm();

  /**
   * Wrapper function that displays the change password form.
   *
   * @return void
   */
  static public function displayChangePasswordForm();

  /**
   * Initializes the login functionality
   * This method should define all actions and filters requireed to make the
   * code work.
   *
   * @return mixed
   */
  public function init();

  /**
   * Displays the HTML form
   *
   * @return mixed
   */
  public function form();

  /**
   * @return void
   */
  public function resetPasswordForm();

  /**
   * @return void
   */
  public function changePasswordForm();
}