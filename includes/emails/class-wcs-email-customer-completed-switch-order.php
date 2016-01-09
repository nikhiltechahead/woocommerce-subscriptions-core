<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Customer Completed Switch Order Email
 *
 * Order switch email sent to customer when a subscription is switched successfully.
 *
 * @class 		WCS_Email_Completed_Switch_Order
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class WCS_Email_Completed_Switch_Order extends WC_Email_Customer_Completed_Order {

	/**
	 * Constructor
	 */
	function __construct() {

		// Call override values
		$this->id             = 'customer_completed_switch_order';
		$this->title          = __( 'Subscription Switch Complete', 'woocommerce-subscriptions' );
		$this->description    = __( 'Subscription switch complete emails are sent to the customer when a subscription is switched successfully.', 'woocommerce-subscriptions' );

		$this->heading        = __( 'Your subscription change is complete', 'woocommerce-subscriptions' );
		$this->subject        = __( 'Your {blogname} subscription change from {order_date} is complete', 'woocommerce-subscriptions' );

		$this->template_html  = 'emails/customer-completed-switch-order.php';
		$this->template_plain = 'emails/plain/customer-completed-switch-order.php';
		$this->template_base  = plugin_dir_path( WC_Subscriptions::$plugin_file ) . 'templates/';

		// Other settings
		$this->heading_downloadable = $this->get_option( 'heading_downloadable', __( 'Your subscription change is complete - download your files', 'woocommerce-subscriptions' ) );
		$this->subject_downloadable = $this->get_option( 'subject_downloadable', __( 'Your {blogname} subscription change from {order_date} is complete - download your files', 'woocommerce-subscriptions' ) );

		// Triggers for this email
		add_action( 'woocommerce_order_status_completed_switch_notification', array( $this, 'trigger' ) );

		// We want most of the parent's methods, with none of its properties, so call its parent's constructor
		WC_Email::__construct();
	}

	/**
	 * trigger function.
	 *
	 * We need to override WC_Email_Customer_Completed_Order's trigger method because it expects to be run only once
	 * per request (but multiple subscription switch orders can be generated per request).
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object    = new WC_Order( $order_id );
			$this->recipient = $this->object->billing_email;

			$order_date_index = array_search( '{order_date}', $this->find );
			if ( false === $order_date_index ) {
				$this->find[] = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			} else {
				$this->replace[ $order_date_index ] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			}

			$order_number_index = array_search( '{order_number}', $this->find );
			if ( false === $order_number_index ) {
				$this->find[] = '{order_number}';
				$this->replace[] = $this->object->get_order_number();
			} else {
				$this->replace[ $order_number_index ] = $this->object->get_order_number();
			}

			$this->subscriptions = wcs_get_subscriptions_for_switch_order( $this->object );
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_subject function.
	 *
	 * @access public
	 * @return string
	 */
	function get_subject() {
		return apply_filters( 'woocommerce_subscriptions_email_subject_customer_completed_switch_order', parent::get_subject(), $this->object );
	}

	/**
	 * get_heading function.
	 *
	 * @access public
	 * @return string
	 */
	function get_heading() {
		return apply_filters( 'woocommerce_email_heading_customer_switch_order', parent::get_heading(), $this->object );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'order'         => $this->object,
				'subscriptions' => $this->subscriptions,
				'email_heading' => $this->get_heading(),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'subscriptions' => $this->subscriptions,
				'email_heading' => $this->get_heading(),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}
}
