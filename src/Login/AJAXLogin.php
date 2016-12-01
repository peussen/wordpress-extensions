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
 * This wrapper allows you to create an AJAX based login/password reset workflow on your site.
 * There are tons of filters you can use to modify the output, but here are some basics:
 *
 * API:
 * hj_ajax_loginform         : function you can use in your template to show the login form
 * hj_ajax_resetpasswordform : function you can use to show the reset password request form
 * hj_ajax_changepasswordform: function you can use to show the change password form after a request has been sent
 *
 * Filters:
 * harperjones/login/form           : Changes the template/method that will render the login form
 * harperjones/reset/form           : Changes the template/method that will render the reset password request form
 * harperjones/changepassword/form  : Changes the template/method that will render the change password form
 *
 * harperjones/login/redirecturl    : Url to redirect the user to after login (defaults to home), second param
 *                                    holds the logged in user if available, otherwise it will be NULL, which
 *                                    should be interpreted as the default login page if none was found.
 * harperjones/login/logouturl      : Url to redirect the user to after logout (defaults to home)
 * lostpassword_url                 : The page that will show the reset password request form.
 *
 * harperjones/reset/mailbody       : Changes the mail body of the reset password request mail
 * harperjones/reset/mailsubject    : Changes the mail subject of the reset password request mail
 *
 * harperjones/login/*              : All filters related to the login form and messages
 * harperjones/reset/*              : All filters related to the reset password request form
 * harperjones/changepassword/*     : All filters related to the change password form
 *
 * Shortcodes:
 * [hj_loginform]
 * [hj_resetpasswordform]
 * [hj_changepasswordform]
 *
 * @package HarperJones\Wordpress\Login
 */
class AJAXLogin implements LoginIterface
{
  const JS_FILENAME = 'ajax-login.js';

  /**
   * @var bool|string
   */
  protected $loginscript = false;

  /**
   * Generic settings
   *
   * @var array
   */
  protected $config = [];

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
   *
   * @globalize hj_ajax_resetpasswordform
   */
  static public function displayPasswordResetForm()
  {
    /**
     * Filter which allows you to change where the login form comes from
     *
     * @filter harperjones/resetpassword/form
     */
    $formHandler = apply_filters('harperjones/resetpassword/form',[Setup::get(__CLASS__),'resetPasswordForm']);

    if ( is_callable($formHandler)) {
      call_user_func($formHandler);
    } else {
      get_template_part($formHandler);
    }
  }

  /**
   *
   * @globalize hj_ajax_changepasswordform
   */
  static public function displayChangePasswordForm()
  {
    /**
     * Filter which allows you to change where the login form comes from
     *
     * @filter harperjones/changepassword/form
     */
    $formHandler = apply_filters('harperjones/changepassword/form',[Setup::get(__CLASS__),'changePasswordForm']);

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
   *
   * The password form has two states: one for a valid input and one for an invalid input. You need to check
   * the token with the verifyResetToken() method, and then check if it succeeded or not to see which state you
   * should show.
   *
   * The form requires the data-changepasswordform on the FORM tag and expects input fields with the following names:
   *  - password1: the main password field
   *  - password2: the retry/validate password field
   *  - token: the sent password reset code
   *  - security: the wp_nonce code
   *
   * @see LoginIterface::changePasswordForm()
   */
  public function changePasswordForm()
  {
    $user = $this->verifyResetToken(Arr::value($_GET,'reset'));

    if ( $user ):
      ?>
    <div class="hj-form__wrap">
      <form class="hj-form hj-form--changepw" data-changepasswordform action="changepassword" method="post">
        <h1 class="hj-form__title"><?php echo apply_filters('harperjones/changepassword/title',__('Change Password')); ?></h1>
        <div data-formstatus class="hj-form__message hj-form__message--status"><?php echo sprintf(apply_filters('harperjones/changepassword/changefor','Changing password for %s'),$this->getSafeEmail($user)); ?></div>
        <div data-formfields>
          <label for="password1" class="hj-field__label"><?php echo apply_filters('harperjones/changepassword/password',__('Password')); ?></label>
          <input type="password" name="password1" class="hj-field hj-field--password">
          <label for="password2" class="hj-field__label"><?php echo apply_filters('harperjones/changepassword/retypepassword',__('Retype Password')); ?></label>
          <input type="password" name="password2" class="hj-field hj-field--password">
          <button class="hj-form__button" type="submit" data-changebutton><?php echo apply_filters('harperjones/reset/submit',__('Save new password')); ?></button>
          <input type="hidden" name="token" value="<?php echo $this->getResetToken($user) ?>" />
          <?php wp_nonce_field( 'ajax-change-nonce', 'security' ); ?>
        </div>
      </form>
   
    </div>
    <?php
    else:
    ?>
    <div class="hj-form__wrap">
      <div class="hj-form hj-changepasswordform" data-changepasswordform>
        <div data-formstatus class="hj-form__message hj-form__message--status""><?php echo apply_filters('harperjones/changepassword/invalidtoken',__('The token supplied is invalid')); ?></div>
      </div>
    </div>
    <?php
    endif;
  }


  /**
   * (inherited doc)
   * @see LoginIterface::resetPasswordForm()
   */
  public function resetPasswordForm()
  {
    ?>
    <div class="hj-form__wrap">
      <form class="hj-form hj-form--resetpw" data-resetform action="reset" method="post">
        <h1 class="hj-form__title"><?php echo apply_filters('harperjones/login/title',__('Forgot Password')); ?></h1>
        <div data-formstatus class="hj-form__message hj-form__message--status"></div>
        <div class="hj-form__group" data-formfields>
          <label for="username" class="hj-field__label"><?php echo apply_filters('harperjones/reset/username',__('Username')); ?></label>
          <input type="text" name="username" class="hj-field hj-field--text">
          <button class="hj-form__button" type="submit" data-resetbutton><?php echo apply_filters('harperjones/reset/submit',__('Request password reset')); ?></button>
          <?php wp_nonce_field( 'ajax-reset-nonce', 'security' ); ?>
        </div>
      </form>
    </div>
    <?php
  }


  /**
   * (inherited doc)
   * @see LoginIterface::form()
   */
  public function form()
  {
    if ( !is_user_logged_in()) {
      ?>
      <div class="hj-form__wrap">
        
        <form class="hj-form hj-form--login" data-loginform action="login" method="post">
          <div class="hj-form__header">
            <h1 class="hj-form__title"><?php echo apply_filters('harperjones/login/title',__('Site Login')); ?></h1>
            <div data-formstatus class="hj-form__message hj-form__message--status"></div>
          </div>
          <div class="hj-form__main">
            <div class="hj-form__group">
              <label for="username" class="hj-field__label"><?php echo apply_filters('harperjones/login/username',__('Username')); ?></label>
              <input type="text" name="username" class="hj-field hj-field--text">
            </div>
            <div class="hj-form__group">
              <label for="password" class="hj-field__label"><?php echo apply_filters('harperjones/login/password',__('Password')); ?></label>
              <input type="password" name="password" class="hj-field hj-field--password">      
            </div>
            <div class="hj-form__group">
              <label for="remember" class="hj-field__label--checkbox">
                <input type="checkbox" name="remember" class="hj-field hj-field--checkbox">
                <?php _e('Remember login'); ?>
              </label>
            </div>
          </div>
          <div class="hj-form__footer">
            <button class="hj-form__button" type="submit" data-loginbutton><?php echo apply_filters('harperjones/login/submit',__('Login')); ?></button>
            <a class="hj-form__link" href="<?php echo wp_lostpassword_url(); ?>"><?php echo apply_filters('harperjones/login/forgot',__('Lost your password?')); ?></a>
            <?php wp_nonce_field( 'ajax-login-nonce', 'security' ); ?>
          </div>
        </form>

      </div>
      <?php
    } else {
      ?>
      <div class="hj-form__wrap">
        <div class="hj-form__message"><?php _e('You are already logged in.'); ?></div>
        <a class="hj-form__link" href="<?php echo wp_logout_url( apply_filters('harperjones/login/logouturl',home_url()) ); ?>"><?php echo apply_filters('harperjones/login/logout',__('Logout')); ?></a>
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

    $redirect = Arr::value(
      $_POST,
      'ref',
      apply_filters('harperjones/login/redirecturl',$this->config['urls']['redirect'],$signedOnUser)
    );

    if ( is_wp_error($signedOnUser) ){
      echo json_encode(
        [
          'loggedin'=> false,
          'message' => apply_filters('harperjones/login/invalidmessage',$this->config['labels']['login']['wronglogin'])
        ]
      );
    } else {
      echo json_encode(
        [
          'loggedin' => true,
          'message'  => apply_filters('harperjones/login/succesmessage',$this->config['labels']['login']['wronglogin']),
          'redirect' => $redirect,
        ]
      );
    }
    exit();
  }

  /**
   * Handles the Request Password reset form
   *
   */
  public function resetHandler()
  {
    check_ajax_referer('ajax-reset-nonce', 'security');

    $account = Arr::value($_POST,'username');
    $getby   = 'login';
    $error   = false;

    if ( empty($account) ) {
      $error = apply_filters('harperjones/reset/error/nousername',__('Enter a username or emailaddress'));
    } else {
      if ( is_email($account)) {
        if ( !email_exists($account)) {
          $error = apply_filters('harperjones/reset/error/invalidusername',__('No user found for your login'));
        } else {
          $getby = 'email';
        }
      } elseif ( validate_username($account)) {
        if ( username_exists($account)) {
          $getby = 'login';
        } else {
          $error = apply_filters('harperjones/reset/error/invalidusername',__('No user found for your login'));
        }
      } else {
        $error = apply_filters('harperjones/reset/error/invalidaccount', 'No account found with that name');
      }
    }

    if ( $error === false ) {

      $user  = get_user_by($getby,$account);
      $token = sha1(wp_salt('auth') . $user->user_email . microtime(true));

      if ( $user && update_user_meta($user->ID,'hj_resettoken', $token )) {
        $from    = apply_filters('harperjones/reset/from',get_bloginfo('admin_email'));
        $to      = $user->user_email;
        $subject = apply_filters('harperjones/reset/mailsubject',__('Your new password'));


        $headers[] = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers[] = "MIME-Version: 1.0\r\n";
        $headers[] = 'From:' . get_bloginfo('name') . '<' . $from . '>';

        $mail      = apply_filters(
          'harperjones/reset/mailbody',
          "Hi [displayname],\n\nYou, or someone else requested a new password for your account on our site.\nPlease use the following link to create a new password\n[link]\n"
        );

        $mail = str_replace("[displayname]",$user->display_name,$mail);
        $mail = str_replace("[link]", apply_filters('harperjones/reset/changepasswordurl',home_url()) . '?reset=' . $token,$mail);

        if ( wp_mail($to,$subject,$mail,$headers)) {
          $reply = [
            'status' => true,
            'message'=> sprintf(
              apply_filters('harperjones/reset/mailsent',__('An e-mail has been sent to %s with instructions on how you can change your password')),
              $this->getSafeEmail($user)
            ),
          ];
        } else {
          $reply = [
            'status'  => false,
            'message' => apply_filters('harperjones/reset/error/updatefailed',__('Your account could not be reset. Contact support')),
          ];
        }
      } else {
        $reply = [
          'status'  => false,
          'message' => apply_filters('harperjones/reset/error/updatefailed',__('Your account could not be reset. Contact support')),
        ];

      }
    } else {
      $reply = [
        'status'  => false,
        'message' => $error
      ];
    }

    echo json_encode($reply);
    exit();
  }

  /**
   * handles the actual change of the password
   */
  public function changeHandler()
  {
    check_ajax_referer('ajax-change-nonce', 'security');

    $password1 = Arr::value($_POST,'password1');
    $password2 = Arr::value($_POST,'password2');
    $token     = Arr::value($_POST,'token');
    $user      = $this->verifyResetToken($token);

    /**
     * Checks if a password is secure enough
     *
     */
    $secure    = apply_filters('harperjones/changepassword/strength',true,$password1);

    if ( $password1 != $password2 ) {
      $reply = [
        'status' => false,
        'message'=> apply_filters('harperjones/changepassword/mismatch',__('Specified passwords do not match, try again'))
      ];
    } elseif ( empty($password1) ) {
      $reply = [
        'status' => false,
        'message'=> apply_filters('harperjones/changepassword/empty',__('Password may not be empty.'))
      ];
    } elseif ( !$secure ) {
      $reply = [
        'status' => false,
        'message'=> apply_filters('harperjones/changepassword/insecure',__('Your password is not secure enough. Please try adding capitals, numbers and special characters'))
      ];
    } elseif ( !$user ) {
      $reply = [
        'status'  => false,
        'message' => apply_filters('harperjones/changepassword/invalidtoken',__('The token supplied is invalid')),
        'token'   => $token
      ];
    } else {
      $result = wp_update_user(['user_pass' => $password1, 'ID' => $user->ID]);

      if ( is_wp_error($result) ) {
        $reply = [
          'status'  => false,
          'message' => apply_filters('harperjones/changepassword/failed',__('Unable to alter your password. Try again later')),
        ];
      } else {
        delete_user_meta($user->ID,'hj_resettoken');
        $reply = [
          'status' => true,
          'message'=> apply_filters('harperjones/changepassword/succes',__('Your new password has been saved.')),
        ];
      }
    }

    echo json_encode($reply);
    exit();
  }

  /**
   * Initializes all things for this module
   *
   * @action init
   */
  public function initAjaxLogin()
  {
    $this->setupDefaults();

    /**
     * Allows you to override the default login javascript with your own version.
     *
     * @filter harperjones/login/ajaxscript
     * @param string  $loginScript  Url of the login handling script
     */
    $loginScript = apply_filters('harperjones/login/ajaxscript',$this->loginscript);

    if ( $loginScript ) {
      wp_register_script('hj-ajax-login-script', $loginScript, array('jquery'), false, true );
      wp_enqueue_script('hj-ajax-login-script');

      wp_localize_script( 'hj-ajax-login-script', 'ajax_login_object',
        [
          'ajaxurl'         => admin_url( 'admin-ajax.php' ),
          'redirecturl'     => apply_filters('harperjones/login/redirecturl',$this->config['urls']['redirect'],null),
          'loadingmessage'  => apply_filters('harperjones/login/submitmessage',$this->config['labels']['login']['checkinglogin']),
          'passwordmismatch'=> apply_filters('harperjones/changepassword/mismatch',$this->config['labels']['changepassword']['passwordsmismatch']),
        ]
      );
    }

    // Enable the user with no privileges to run ajax_login() in AJAX
    add_action( 'wp_ajax_nopriv_hj_ajaxlogin', [$this,'loginHandler'] );

    add_action( 'wp_ajax_nopriv_hj_resetpassword', [$this,'resetHandler']);

    add_action( 'wp_ajax_nopriv_hj_changepassword', [$this,'changeHandler']);
    add_action( 'wp_ajax_priv_hj_changepassword', [$this,'changeHandler']);

    add_shortcode('hj_loginform', __CLASS__ . '::displayLoginForm' );
    add_shortcode('hj_resetpasswordform', __CLASS__ . '::displayPasswordResetForm' );
    add_shortcode('hj_changepasswordform', __CLASS__ . '::displayChangePasswordForm' );
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

    $paths  = [
      get_template_directory() . '/dist/scripts' => get_template_directory_uri() . '/dist/scripts',
      get_template_directory() . '/js' => get_template_directory_uri() . '/js',
      get_theme_root() => get_theme_root_uri(),
      dirname(ABSPATH) => get_home_url(),
      $upload['basedir'] => $upload['baseurl']
    ];

    foreach( $paths as $optionalPath => $optionalUrl ) {
      if ( is_writeable($optionalPath) ) {
        if ( filemtime(__FILE__) > @filemtime($optionalPath) || $debug ) {
          $this->generateLoginScript($optionalPath);
        }

        $this->loginscript = $optionalUrl . '/' . static::JS_FILENAME;
        return true;
      }
    }
    return false;

    if ( isset($upload['basedir']) && isset($upload['baseurl'])) {
      if ( (!$debug && file_exists($upload['basedir'] . '/' . static::JS_FILENAME)) || $this->generateLoginScript($upload['basedir'])) {
        $this->loginscript = $upload['baseurl'] . '/' . static::JS_FILENAME;
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
// Do not touch, generated by the Wordpress-extensions
    
jQuery(document).ready(function ($) {

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
        'security': form$.find('[name="security"]').val(),
        'ref': form$.find('[name="ref"]').val()
      },
      success: function (data) {
        form$.find('[data-formstatus]').text(data.message);
        if (data.loggedin == true) {
          document.location.href = data.redirect ? data.redirect : ajax_login_object.redirecturl;
        }
      }
    });
    e.preventDefault();
  });

  // Perform AJAX for password reset request
  $('form[data-resetform]').on('submit',function(e) {
    var form$ = $(this);
    
    form$.find('[data-formstatus]').show().text(ajax_login_object.loadingmessage);
    $.ajax({
      type: 'POST',
      dataType: 'json',
      url: ajax_login_object.ajaxurl,
      data: {
        'action'  : 'hj_resetpassword',
        'security': form$.find('[name="security"]').val(),
        'username': form$.find('[name="username"]').val()
      },
      success: function(data) {
        form$.find('[data-formstatus]').text(data.message);
        
        if ( data.status === true ) {
          form$.find('[data-formfields]').hide();          
        }
      }
    });
    
    e.preventDefault();
  });
  
  
  // Perform AJAX for password change
  $('form[data-changepasswordform]').on('submit',function(e) {
    var form$    = $(this),
        password1 = $(this).find('[name="password1"]').val(), 
        password2 = $(this).find('[name="password2"]').val(); 

    if ( password1 != password2 || password1 === '' || password2 === '') {
      form$.find('[data-formstatus]').show().text(ajax_login_object.passwordmismatch)
    } else {
      form$.find('[data-formstatus]').show().text(ajax_login_object.loadingmessage);
      
      $.ajax({
        type: 'POST',
        dataType: 'json',
        url: ajax_login_object.ajaxurl,
        data: {
          'action'   : 'hj_changepassword',
          'password1': password1,
          'password2': password2,
          'token'    : form$.find('[name="token"]').val(),
          'security' : form$.find('[name="security"]').val(),
        },
        success: function(data) {
          form$.find('[data-formstatus]').text(data.message);
          
          if ( data.status === true ) {
            form$.find('[data-formfields]').hide();          
          }
        }
      });
    }
    
    e.preventDefault();
  });  
});
__EOF;

    return file_put_contents($directory . '/' . static::JS_FILENAME,$content);
  }

  /**
   * Checks if the given reset token is a valid reset token for one of our users
   *
   * @param $token
   * @return false|\WP_User
   */
  protected function verifyResetToken($token)
  {
    global $wpdb;

    if ( $token ) {
      $sql =         sprintf(
        "SELECT user_id FROM " . $wpdb->usermeta . " WHERE meta_key = 'hj_resettoken' AND meta_value = '%s'",
        $wpdb->esc_like($token)
      );

      $result = $wpdb->get_col(
        $sql
      );

      if ( $result ) {
        return get_user_by('id',array_shift($result));
      }
    }
    return false;
  }

  /**
   * Returns the reset token for a specific user
   *
   * @param $user
   * @return string
   */
  protected function getResetToken($user)
  {
    return get_user_meta($user->ID,'hj_resettoken',true);
  }

  /**
   * Returns a semi protected string containing the email of the user
   *
   * @param \WP_User $user
   * @return string
   */
  protected function getSafeEmail(\WP_User $user)
  {
    $email = $user->user_email;

    if (preg_match('/@[a-z0-9](.*)\.[a-z0-9]+$/i',$email,$matches)) {
      $email = str_replace($matches[1],str_repeat('*',strlen($matches[1])),$email);
    }
    return $email;
  }

  protected function setupDefaults()
  {
    $this->config = [
      'urls' => [
        'redirect' => home_url(),
        'logout'   => home_url(),
      ],
      'labels' => [
        'login' => [
          'title'           => __('Site Login'),
          'username'        => __('Username'),
          'password'        => __('Password'),
          'button'          => __('Login'),
          'forgotpassword'  => __('Lost your password?'),
          'alreadyloggedin' => __('You are already logged in.'),
          'logout'          => __('Logout'),
          'remember'        => __('Remember login'),
          'wronglogin'      => __('Wrong username or password.'),
          'loginok'         => __('Login successful, redirecting...'),
          'checkinglogin'   => __('Sending user info, please wait...'),
        ],
        'resetpassword' => [
          'title'     => __('Forgot Password'),
          'username'  => __('Username'),
          'button'    => __('Request password reset'),
        ],
        'changepassword' => [
          'title'             => __('Change Password'),
          'info'              => __('Changing password for %s'),
          'password'          => __('Password'),
          'retypepassword'    => __('Retype Password'),
          'button'            => __('Save new password'),
          'invalidtoken'      => __('The token supplied is invalid'),
          'passwordsmismatch' => __('Specified passwords do not match, try again'),
        ]
      ]
    ];

    $this->config = apply_filters('harperjones/ajaxlogin/config',$this->config);
  }
}