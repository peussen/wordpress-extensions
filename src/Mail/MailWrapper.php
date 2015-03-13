<?php
/**
 * Created by PhpStorm.
 * User: petereussen
 * Date: 12/03/15
 * Time: 11:06
 */
namespace HarperJones\Wordpress\Mail;

use \HarperJones\Wordpress\Theme\View;

class MailWrapper
{
	private $type;

	public function __construct()
	{
		add_filter('wp_mail',array($this,'wrap'));
		add_filter('wp_mail_content_type',array($this,'contentType'));
	}

	/**
	 * Ensures the right content-type is set
	 *
	 * @return string
	 */
	public function contentType()
	{
		return 'text/html';
	}

	/**
	 * Creates a new message body based on the old information
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function wrap($settings)
	{
		if ( isset($settings['message'])) {

			$subject         = htmlentities(preg_replace("/\\[.*\\]/i",'',$settings['subject']));

			/**
			 *
			 * @filter formatted_mail_attributes
			 * @since  1.0
			 */
			$personalisation = apply_filters('formatted_mail_attributes',
				array(
					'to'        => $settings['to'],
					'subject'   => $subject,
				)
			);

			if ( $settings['message'] instanceof View) {
				$message = $settings['message']->render($personalisation);
			} else {
				$message = wpautop($settings['message']); // Fix message already
			}

			$styling = apply_filters('formatted_mail_content_styling','');

			if ( $styling ) {
				$styling = esc_attr($styling);
				$message = str_replace(
					array('<td>','<p>'),
					array('<td style="' . $styling . '">', '<p style="' . $styling . '">'),
					$message
				);
			}
			/**
			 *
			 * @filter formatted_mail_template
			 * @since  1.0
			 */
			$view = new View(apply_filters('formatted_mail_template','mail/template'),
				array('content' => $message)
			);
			$settings['message'] = $view->render($personalisation);
		}
		return $settings;
	}
}