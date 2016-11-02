<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Login;

use HarperJones\Wordpress\Arr;
use HarperJones\Wordpress\Setup;

/**
 * AJAX version of a Wordpress login
 *
 * @package HarperJones\Wordpress\Login
 */
class AJAXLogin implements LoginIterface
{
  protected $loginscript = false;

  /**
   * Method that can be called from templates to obtain the loginform
   *
   * @globalize hj_ajax_loginform
   */
  static public function displayLoginForm()
  {
    /**
     * Filter which allows you to change where the login form comes from
     *
     * @filter harperjones/login/form
     */
    $formHandler = apply_filters('harperjones/login/form',[Setup::get(__CLASS__),'form']);

    if ( is_callable($formHandler)) {
      call_user_func($formHandler);
    } else {
      get_template_part($formHandler);
    }
  }

  /**
   * (inherited doc)
   * @see LoginIterface::init()
   */
  public function init()
  {
    Setup::globalizeStaticMethods(__CLASS__);
    Setup::set(__CLASS__,$this);

    $this->setupJavascript();

    // Postpone all setup until the init action is triggered
    add_action('init',[$this,'initAjaxLogin']);
  }

  /**
   * (inherited doc)
   * @see LoginIterface::form()
   */
  public function form()
  {
    if ( !is_user_logged_in()) {
      ?>
      <form class="hj-form hj-loginform" data-loginform action="login" method="post">
        <h1 class="form__title"><?php _e('Site Login'); ?></h1>
        <div data-formstatus class="form__status"></div>
        <label for="username" class="field__label"><?php _e('Username'); ?></label>
        <input type="text" name="username" class="field--text">
        <label for="password" class="field__label"><?php _e('Password'); ?></label>
        <input type="password" name="password" class="field__password">

        <label for="remember" class="field__label--checkbox">
          <input type="checkbox" name="remember">
          <?php _e('Remember login'); ?>
        </label>
        <a class="form__anchor" href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Lost your password?'); ?></a>
        <button type="submit" data-loginbutton><?php _e('Login'); ?></button>
        <?php wp_nonce_field( 'ajax-login-nonce', 'security' ); ?>
      </form>
      <?php
    } else {
      ?>
      <div class="hj-form hj-loginform">
        <div class="form_status"><?php _e('You are already logged in.'); ?></div>
        <a class="form__anchor" href="<?php echo wp_logout_url( apply_filters('harperjones/login/redirecturl',home_url()) ); ?>"><?php _e('Logout'); ?></a>
      </div>
      <?php
    }
  }

  /**
   * AJAX request handler. Checks for validity and returns a JSON string giving the status of the
   * request.
   */
  public function loginHandler()
  {
    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-login-nonce', 'security' );

    // Nonce is checked, get the POST data and sign user on
    $info = [
      'user_login'    => Arr::value($_POST,'username'),
      'user_password' => Arr::value($_POST,'password'),
      'remember'      => Arr::value($_POST,'remember',false),
    ];

    $signedOnUser = wp_signon( $info, false );

    if ( is_wp_error($signedOnUser) ){
      echo json_encode(
        [
          'loggedin'=> false,
          'message' => apply_filters('harperjones/login/invalidmessage',__('Wrong username or password.'))
        ]
      );
    } else {
      echo json_encode(
        [
          'loggedin' => true,
          'message'  => apply_filters('harperjones/login/succesmessage',__('Login successful, redirecting...'))
        ]
      );
    }
    exit();
  }

  /**
   * Initializes all things for this module
   *
   * @action init
   */
  public function initAjaxLogin()
  {
    /**
     *
     * @filter harperjones/login/ajaxscript
     */
    $loginScript = apply_filters('harperjones/login/ajaxscript',$this->loginscript);

    wp_register_script('hj-ajax-login-script', $loginScript, array('jquery'), false, true );
    wp_enqueue_script('hj-ajax-login-script');

    wp_localize_script( 'hj-ajax-login-script', 'ajax_login_object',
      [
        'ajaxurl'        => admin_url( 'admin-ajax.php' ),
        'redirecturl'    => apply_filters('harperjones/login/redirecturl',home_url()),
        'loadingmessage' => apply_filters('harperjones/login/submitmessage',__('Sending user info, please wait...')),
      ]
    );

    // Enable the user with no privileges to run ajax_login() in AJAX
    add_action( 'wp_ajax_nopriv_hj_ajaxlogin', [$this,'loginHandler'] );
  }

  /**
   * Ensure the login javascript code is available on a public queryable path
   *
   * @return bool
   */
  protected function setupJavascript()
  {
    $upload = wp_upload_dir();
    $debug  = defined("WP_DEBUG") && WP_DEBUG;

    if ( isset($upload['basedir']) && isset($upload['baseurl'])) {
      if ( (!$debug && file_exists($upload['basedir'] . '/ajax-login-script.js')) || $this->generateLoginScript($upload['basedir'])) {
        $this->loginscript = $upload['baseurl'] . '/ajax-login-script.js';
        return true;
      }
    }
    return false;
  }

  /**
   * Creates the public javascript code that will make this module work
   *
   * @param $directory
   * @return int
   */
  protected function generateLoginScript($directory)
  {
    $content = <<< __EOF
jQuery(document).ready(function ($) {

  console.log($('form[data-loginform]'));

  // Perform AJAX login on form submit
  $('form[data-loginform]').on('submit', function (e) {

    var form$ = $(this)
    form$.find('[data-formstatus]').show().text(ajax_login_object.loadingmessage);

    $.ajax({
      type: 'POST',
      dataType: 'json',
      url: ajax_login_object.ajaxurl,
      data: {
        'action': 'hj_ajaxlogin', //calls wp_ajax_nopriv_ajaxlogin
        'username': form$.find('[name="username"]').val(),
        'password': form$.find('[name="password"]').val(),
        'security': form$.find('[name="security"]').val()
      },
      success: function (data) {
        form$.find('[data-formstatus]').text(data.message);
        if (data.loggedin == true) {
          document.location.href = ajax_login_object.redirecturl;
        }
      }
    });
    e.preventDefault();
  });

});
__EOF;

    return file_put_contents($directory . '/ajax-login-script.js',$content);
  }
}