<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Login;


interface LoginIterface
{
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
}