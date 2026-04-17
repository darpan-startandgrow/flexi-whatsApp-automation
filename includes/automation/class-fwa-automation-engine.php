<?php
/**
 * Automation engine – Event → Rule → Template → Message.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Automation_Engine
 *
 * Listens for WordPress/WooCommerce/Flexi Revive Cart events, evaluates
 * automation rules, resolves message templates, and dispatches WhatsApp
 * messages via FWA_Message_Sender.
 *
 * @since 1.0.0
 */
class FWA_Automation_Engine {

	/**
	 * Loaded automation rules.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $rules = array();

	/**
	 * Message sender instance.
	 *
	 * @since 1.0.0
	 * @var FWA_Message_Sender
	 */
	private $message_sender;

	/**
	 * Constructor.
	 *
	 * Registers all automation event hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->message_sender = new FWA_Message_Sender();

		// --- WooCommerce hooks (only when WooCommerce is active) ---
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 1 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 3 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_checkout_complete' ), 10, 1 );
			add_action( 'woocommerce_payment_complete', array( $this, 'on_payment_complete' ), 10, 1 );
		}

		// --- WooCommerce Subscriptions hooks ---
		if ( class_exists( 'WC_Subscriptions' ) || function_exists( 'wcs_get_subscription' ) ) {
			add_action( 'woocommerce_subscription_status_active', array( $this, 'on_subscription_activated' ), 10, 1 );
			add_action( 'woocommerce_subscription_renewed_payment', array( $this, 'on_subscription_renewed' ), 10, 2 );
			add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'on_subscription_cancelled' ), 10, 1 );
			add_action( 'woocommerce_subscription_status_expired', array( $this, 'on_subscription_expired' ), 10, 1 );
			add_action( 'woocommerce_subscription_payment_failed', array( $this, 'on_subscription_payment_failed' ), 10, 2 );
		}

		// --- Core WordPress hooks ---
		add_action( 'user_register', array( $this, 'on_user_register' ), 10, 1 );
		add_action( 'wp_login', array( $this, 'on_user_login' ), 10, 2 );

		// --- Flexi Revive Cart hooks (safe to register; callbacks are no-ops when FRC is absent) ---
		add_action( 'frc_after_cart_tracked', array( $this, 'on_cart_abandoned' ), 10, 2 );
		add_action( 'frc_cart_restore_complete', array( $this, 'on_cart_recovered' ), 10, 2 );
		add_action( 'frc_dispatch_reminder', array( $this, 'on_frc_reminder' ), 10, 2 );

		// --- Generic custom trigger ---
		add_action( 'fwa_trigger_automation', array( $this, 'on_custom_trigger' ), 10, 2 );

		// --- Delayed rule execution ---
		add_action( 'fwa_execute_delayed_rule', array( $this, 'execute_delayed_rule_callback' ), 10, 2 );
	}

	/*--------------------------------------------------------------
	 * WooCommerce event handlers
	 *------------------------------------------------------------*/

	/**
	 * Handle new WooCommerce order.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_new_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$data  = $this->extract_order_data( $order );
		$rules = $this->get_rules( 'new_order' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		/**
		 * Fires after the new_order automation event is triggered.
		 *
		 * @since 1.0.0
		 *
		 * @param string $event    Event name.
		 * @param int    $order_id WooCommerce order ID.
		 */
		do_action( 'fwa_automation_triggered', 'new_order', $order_id );
	}

	/**
	 * Handle WooCommerce order status change.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $order_id  Order ID.
	 * @param string $old_status Previous status (without wc- prefix).
	 * @param string $new_status New status (without wc- prefix).
	 * @return void
	 */
	public function on_order_status_changed( $order_id, $old_status, $new_status ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$data               = $this->extract_order_data( $order );
		$data['old_status'] = $old_status;
		$data['new_status'] = $new_status;

		$event = 'order_status_' . sanitize_key( $new_status );
		$rules = $this->get_rules( $event );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		// WooCommerce per-status notification (Settings → WooCommerce tab).
		$this->send_wc_status_notification( $new_status, $data );

		do_action( 'fwa_automation_triggered', $event, $order_id );
	}

	/**
	 * Handle checkout complete.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_checkout_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$data  = $this->extract_order_data( $order );
		$rules = $this->get_rules( 'checkout_complete' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'checkout_complete', $order_id );
	}

	/**
	 * Handle payment complete.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_payment_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$data  = $this->extract_order_data( $order );
		$rules = $this->get_rules( 'payment_complete' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'payment_complete', $order_id );
	}

	/*--------------------------------------------------------------
	 * WordPress event handlers
	 *------------------------------------------------------------*/

	/**
	 * Handle new user registration.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_register( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$phone = get_user_meta( $user_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			// No phone available — skip and log.
			do_action( 'fwa_automation_triggered', 'user_register', $user_id );
			return;
		}

		$data = array(
			'user_id'       => $user_id,
			'customer_name' => $user->display_name,
			'email'         => $user->user_email,
			'phone'         => $phone,
			'billing_phone' => $phone,
			'date'          => current_time( 'mysql' ),
		);

		$rules = $this->get_rules( 'user_register' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'user_register', $user_id );
	}

	/**
	 * Handle user login.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 * @return void
	 */
	public function on_user_login( $user_login, $user ) {
		$rules = $this->get_rules( 'user_login' );

		if ( empty( $rules ) ) {
			return;
		}

		$phone = get_user_meta( $user->ID, 'billing_phone', true );
		if ( empty( $phone ) ) {
			return;
		}

		$data = array(
			'user_id'       => $user->ID,
			'customer_name' => $user->display_name,
			'email'         => $user->user_email,
			'phone'         => $phone,
			'billing_phone' => $phone,
			'date'          => current_time( 'mysql' ),
		);

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'user_login', $user->ID );
	}

	/*--------------------------------------------------------------
	 * Flexi Revive Cart event handlers
	 *------------------------------------------------------------*/

	/**
	 * Handle abandoned cart event.
	 *
	 * @since 1.0.0
	 *
	 * @param int $cart_id Cart ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_cart_abandoned( $cart_id, $user_id ) {
		global $wpdb;

		$data = array(
			'cart_id' => $cart_id,
			'user_id' => $user_id,
			'date'    => current_time( 'mysql' ),
		);

		// Attempt to load cart data from Flexi Revive Cart table.
		$cart_table = $wpdb->prefix . 'frc_abandoned_carts';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cart = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$cart_table} WHERE id = %d LIMIT 1",
			absint( $cart_id )
		) );

		if ( $cart ) {
			$cart_data = maybe_unserialize( isset( $cart->cart_contents ) ? $cart->cart_contents : '' );
			$data['cart_total']    = isset( $cart->cart_total ) ? $cart->cart_total : '';
			$data['cart_contents'] = $cart_data;
		}

		// Resolve phone.
		if ( $user_id ) {
			$phone = get_user_meta( $user_id, 'billing_phone', true );
			if ( $phone ) {
				$data['phone']         = $phone;
				$data['billing_phone'] = $phone;
			}
		}

		if ( $cart && isset( $cart->billing_phone ) && ! empty( $cart->billing_phone ) ) {
			$data['phone']         = $cart->billing_phone;
			$data['billing_phone'] = $cart->billing_phone;
		}

		$user = $user_id ? get_userdata( $user_id ) : null;
		if ( $user ) {
			$data['customer_name'] = $user->display_name;
			$data['email']         = $user->user_email;
		}

		$rules = $this->get_rules( 'cart_abandoned' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'cart_abandoned', $cart_id );
	}

	/**
	 * Handle recovered cart event.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $cart    Cart data (object or array).
	 * @param string $channel Recovery channel identifier.
	 * @return void
	 */
	public function on_cart_recovered( $cart, $channel ) {
		$data = array(
			'cart'    => $cart,
			'channel' => $channel,
			'date'    => current_time( 'mysql' ),
		);

		if ( is_object( $cart ) ) {
			if ( isset( $cart->billing_phone ) ) {
				$data['phone']         = $cart->billing_phone;
				$data['billing_phone'] = $cart->billing_phone;
			}
			if ( isset( $cart->cart_total ) ) {
				$data['cart_total'] = $cart->cart_total;
			}
		}

		$rules = $this->get_rules( 'cart_recovered' );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', 'cart_recovered', $cart );
	}

	/**
	 * Handle Flexi Revive Cart reminder dispatch.
	 *
	 * Sends a WhatsApp message as an alternative or complement to the
	 * email reminder at the given stage.
	 *
	 * Stage 1 is a free FRC feature. Stages 2+ are PRO-only features
	 * (Urgency and Incentive/Discount templates). If FRC Pro is not
	 * installed and licensed, PRO stages are silently ignored to prevent
	 * unintended feature leakage.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $cart  Cart data (object or array).
	 * @param string $stage Reminder stage identifier.
	 * @return void
	 */
	public function on_frc_reminder( $cart, $stage ) {
		$stage_num = absint( $stage );

		// Stages 2+ require FRC Pro to be installed, active, and licensed.
		if ( $stage_num >= 2 && ! fwa_is_frc_pro_licensed() ) {
			return;
		}

		$data = array(
			'cart'  => $cart,
			'stage' => $stage,
			'date'  => current_time( 'mysql' ),
		);

		if ( is_object( $cart ) ) {
			if ( isset( $cart->billing_phone ) ) {
				$data['phone']         = $cart->billing_phone;
				$data['billing_phone'] = $cart->billing_phone;
			}
			if ( isset( $cart->cart_total ) ) {
				$data['cart_total'] = $cart->cart_total;
			}
			if ( isset( $cart->recovery_link ) ) {
				$data['recovery_link'] = $cart->recovery_link;
			}
		}

		$event = 'frc_reminder_stage_' . sanitize_key( $stage );
		$rules = $this->get_rules( $event );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', $event, $cart );
	}

	/*--------------------------------------------------------------
	 * Custom trigger
	 *------------------------------------------------------------*/

	/**
	 * Generic handler for custom automation triggers.
	 *
	 * Any plugin can fire:
	 *   do_action( 'fwa_trigger_automation', 'my_event', $data );
	 *
	 * @since 1.0.0
	 *
	 * @param string $event_name Custom event name.
	 * @param array  $data       Arbitrary event data.
	 * @return void
	 */
	public function on_custom_trigger( $event_name, $data = array() ) {
		$event_name = sanitize_key( $event_name );

		if ( empty( $event_name ) ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data['date'] = current_time( 'mysql' );

		$rules = $this->get_rules( $event_name );

		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}

		do_action( 'fwa_automation_triggered', $event_name, $data );
	}

	/*--------------------------------------------------------------
	 * Rules management
	 *------------------------------------------------------------*/

	/**
	 * Retrieve automation rules, optionally filtered by event.
	 *
	 * Rules are stored in the 'fwa_automation_rules' option as a
	 * serialised array.
	 *
	 * Each rule: [
	 *   'id'           => string,
	 *   'event'        => string,
	 *   'enabled'      => bool,
	 *   'instance_id'  => int,
	 *   'template'     => string,
	 *   'phone_field'  => string,
	 *   'conditions'   => array,
	 *   'message_type' => string,
	 *   'media_url'    => string,
	 * ]
	 *
	 * @since 1.0.0
	 *
	 * @param string $event Optional event name to filter by.
	 * @return array Array of matching rules.
	 */
	public function get_rules( $event = '' ) {
		if ( empty( $this->rules ) ) {
			$this->rules = get_option( 'fwa_automation_rules', array() );
			if ( ! is_array( $this->rules ) ) {
				$this->rules = array();
			}
		}

		/**
		 * Filter the automation rules.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $rules All automation rules.
		 * @param string $event The event being queried (may be empty).
		 */
		$rules = apply_filters( 'fwa_automation_rules', $this->rules, $event );

		if ( '' === $event ) {
			return $rules;
		}

		// PRO-only events must be gated at execution time as well.
		$pro_gated_events = $this->get_pro_gated_events();

		return array_filter( $rules, function ( $rule ) use ( $event, $pro_gated_events ) {
			if ( ! isset( $rule['event'] ) || $rule['event'] !== $event || empty( $rule['enabled'] ) ) {
				return false;
			}

			// Block execution of PRO-gated events when PRO isn't licensed.
			if ( isset( $pro_gated_events[ $rule['event'] ] ) && ! fwa_is_frc_pro_licensed() ) {
				return false;
			}

			return true;
		} );
	}

	/**
	 * Get events that require FRC Pro to be licensed.
	 *
	 * @since 1.2.0
	 *
	 * @return array Associative array of event_slug => true.
	 */
	private function get_pro_gated_events() {
		$events = array(
			'frc_reminder_stage_2' => true,
			'frc_reminder_stage_3' => true,
		);

		/**
		 * Filter the list of PRO-gated automation events.
		 *
		 * @since 1.2.0
		 *
		 * @param array $events Associative array of event_slug => true.
		 */
		return apply_filters( 'fwa_pro_gated_events', $events );
	}

	/**
	 * Validate and save automation rules.
	 *
	 * @since 1.0.0
	 *
	 * @param array $rules Array of rule arrays.
	 * @return bool True on success.
	 */
	public function save_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$sanitised = array();

		foreach ( $rules as $rule ) {
			$sanitised[] = array(
				'id'                   => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : wp_generate_uuid4(),
				'event'                => isset( $rule['event'] ) ? sanitize_key( $rule['event'] ) : '',
				'enabled'              => ! empty( $rule['enabled'] ),
				'instance_id'          => isset( $rule['instance_id'] ) ? absint( $rule['instance_id'] ) : 0,
				'template'             => isset( $rule['template'] ) ? sanitize_textarea_field( $rule['template'] ) : '',
				'phone_field'          => isset( $rule['phone_field'] ) ? sanitize_text_field( $rule['phone_field'] ) : 'billing_phone',
				'conditions'           => isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array(),
				'message_type'         => isset( $rule['message_type'] ) ? sanitize_text_field( $rule['message_type'] ) : 'text',
				'media_url'            => isset( $rule['media_url'] ) ? esc_url_raw( $rule['media_url'] ) : '',
				'delay'                => isset( $rule['delay'] ) ? absint( $rule['delay'] ) : 0,
				'admin_alert'          => ! empty( $rule['admin_alert'] ),
				'admin_alert_phone'    => isset( $rule['admin_alert_phone'] ) ? sanitize_text_field( $rule['admin_alert_phone'] ) : '',
				'admin_alert_template' => isset( $rule['admin_alert_template'] ) ? sanitize_textarea_field( $rule['admin_alert_template'] ) : '',
			);
		}

		$this->rules = $sanitised;

		return update_option( 'fwa_automation_rules', $sanitised );
	}

	/*--------------------------------------------------------------
	 * Template resolution
	 *------------------------------------------------------------*/

	/**
	 * Resolve placeholders in a message template.
	 *
	 * Supported placeholders: {customer_name}, {order_id}, {order_total},
	 * {order_status}, {store_name}, {site_url}, {date}, {phone}, {email},
	 * {product_list}, {cart_total}, {recovery_link}.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Template string with placeholders.
	 * @param array  $data     Contextual data for replacement.
	 * @return string Resolved message.
	 */
	public function resolve_template( $template, $data ) {
		$product_list = '';
		if ( ! empty( $data['order_items'] ) && is_array( $data['order_items'] ) ) {
			$items = array();
			foreach ( $data['order_items'] as $item ) {
				$name = isset( $item['name'] ) ? $item['name'] : '';
				$qty  = isset( $item['qty'] ) ? $item['qty'] : 1;
				$items[] = "{$name} x{$qty}";
			}
			$product_list = implode( "\n", $items );
		}

		/**
		 * Filter available template variables before resolution.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $variables Key-value pairs of placeholder → value.
		 * @param array  $data      Raw event data.
		 * @param string $template  Original template string.
		 */
		$variables = apply_filters( 'fwa_template_variables', array(
			'{customer_name}' => isset( $data['customer_name'] ) ? $data['customer_name'] : '',
			'{order_id}'      => isset( $data['order_id'] ) ? $data['order_id'] : '',
			'{order_total}'   => isset( $data['order_total'] ) ? $data['order_total'] : '',
			'{order_status}'  => isset( $data['order_status'] ) ? $data['order_status'] : '',
			'{store_name}'    => get_bloginfo( 'name' ),
			'{site_url}'      => home_url(),
			'{date}'          => isset( $data['date'] ) ? $data['date'] : current_time( 'mysql' ),
			'{phone}'         => isset( $data['phone'] ) ? $data['phone'] : '',
			'{email}'         => isset( $data['email'] ) ? $data['email'] : '',
			'{product_list}'  => $product_list,
			'{cart_total}'    => isset( $data['cart_total'] ) ? $data['cart_total'] : '',
			'{recovery_link}' => isset( $data['recovery_link'] ) ? $data['recovery_link'] : '',
		), $data, $template );

		$resolved = str_replace( array_keys( $variables ), array_values( $variables ), $template );

		/**
		 * Filter the fully resolved template.
		 *
		 * @since 1.0.0
		 *
		 * @param string $resolved Resolved message content.
		 * @param string $template Original template.
		 * @param array  $data     Event data.
		 */
		return apply_filters( 'fwa_resolved_template', $resolved, $template, $data );
	}

	/**
	 * Extract phone number from event data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data        Event data.
	 * @param string $phone_field Field path for the phone number.
	 * @return string Sanitised phone number or empty string.
	 */
	public function resolve_phone( $data, $phone_field = 'billing_phone' ) {
		$phone = '';

		if ( isset( $data[ $phone_field ] ) ) {
			$phone = $data[ $phone_field ];
		} elseif ( isset( $data['phone'] ) ) {
			$phone = $data['phone'];
		}

		if ( empty( $phone ) ) {
			return '';
		}

		return FWA_Helpers::sanitize_phone( $phone );
	}

	/**
	 * Evaluate rule conditions against event data.
	 *
	 * Conditions are arrays with 'field', 'operator', and 'value' keys.
	 * Supported operators: ==, !=, >, <, >=, <=, contains.
	 *
	 * @since 1.0.0
	 *
	 * @param array $conditions Array of condition arrays.
	 * @param array $data       Event data.
	 * @return bool True when all conditions pass.
	 */
	public function check_conditions( $conditions, $data ) {
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return true;
		}

		foreach ( $conditions as $condition ) {
			if ( ! isset( $condition['field'], $condition['operator'], $condition['value'] ) ) {
				continue;
			}

			$field    = $condition['field'];
			$operator = $condition['operator'];
			$expected = $condition['value'];
			$actual   = isset( $data[ $field ] ) ? $data[ $field ] : '';

			$pass = false;

			switch ( $operator ) {
				case '==':
					$pass = ( (string) $actual === (string) $expected );
					break;
				case '!=':
					$pass = ( (string) $actual !== (string) $expected );
					break;
				case '>':
					$pass = ( (float) $actual > (float) $expected );
					break;
				case '<':
					$pass = ( (float) $actual < (float) $expected );
					break;
				case '>=':
					$pass = ( (float) $actual >= (float) $expected );
					break;
				case '<=':
					$pass = ( (float) $actual <= (float) $expected );
					break;
				case 'contains':
					$pass = ( false !== strpos( (string) $actual, (string) $expected ) );
					break;
			}

			if ( ! $pass ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get supported automation events with human-readable labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of event_name => label.
	 */
	public function get_supported_events() {
		$events = array(
			'new_order'                   => __( 'New Order', 'flexi-whatsapp-automation' ),
			'order_status_processing'     => __( 'Order Processing', 'flexi-whatsapp-automation' ),
			'order_status_completed'      => __( 'Order Completed', 'flexi-whatsapp-automation' ),
			'order_status_cancelled'      => __( 'Order Cancelled', 'flexi-whatsapp-automation' ),
			'order_status_refunded'       => __( 'Order Refunded', 'flexi-whatsapp-automation' ),
			'order_status_failed'         => __( 'Order Failed', 'flexi-whatsapp-automation' ),
			'order_status_on-hold'        => __( 'Order On Hold', 'flexi-whatsapp-automation' ),
			'checkout_complete'           => __( 'Checkout Complete', 'flexi-whatsapp-automation' ),
			'payment_complete'            => __( 'Payment Complete', 'flexi-whatsapp-automation' ),
			'user_register'               => __( 'User Registration', 'flexi-whatsapp-automation' ),
			'user_login'                  => __( 'User Login', 'flexi-whatsapp-automation' ),
		);

		// FRC Free events — available when Flexi Revive Cart (free) is active.
		if ( fwa_is_flexi_revive_cart_active() ) {
			$events['cart_abandoned'] = __( 'Cart Abandoned', 'flexi-whatsapp-automation' );
			$events['cart_recovered'] = __( 'Cart Recovered', 'flexi-whatsapp-automation' );
		}

		// WooCommerce Subscriptions — available when Subscriptions is active.
		if ( class_exists( 'WC_Subscriptions' ) || function_exists( 'wcs_get_subscription' ) ) {
			$events['subscription_activated']      = __( 'Subscription Activated (WC Subscriptions)', 'flexi-whatsapp-automation' );
			$events['subscription_renewed']        = __( 'Subscription Renewed (WC Subscriptions)', 'flexi-whatsapp-automation' );
			$events['subscription_cancelled']      = __( 'Subscription Cancelled (WC Subscriptions)', 'flexi-whatsapp-automation' );
			$events['subscription_expired']        = __( 'Subscription Expired (WC Subscriptions)', 'flexi-whatsapp-automation' );
			$events['subscription_payment_failed'] = __( 'Subscription Payment Failed (WC Subscriptions)', 'flexi-whatsapp-automation' );
		}

		/**
		 * Filter the list of supported automation events.
		 *
		 * @since 1.0.0
		 *
		 * @param array $events Associative array of event => label.
		 */
		return apply_filters( 'fwa_automation_events', $events );
	}

	/*--------------------------------------------------------------
	 * Internal helpers
	 *------------------------------------------------------------*/

	/**
	 * Execute a single rule against event data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $rule Rule definition.
	 * @param array $data Event data.
	 * @return void
	 */
	private function execute_rule( $rule, $data ) {
		// License guard — block automation execution without a valid license (fail-closed).
		if ( ! FWA_License_Guard::is_licensed() ) {
			return;
		}

		if ( empty( $rule['enabled'] ) ) {
			return;
		}

		// Evaluate conditions.
		$conditions = isset( $rule['conditions'] ) ? $rule['conditions'] : array();
		if ( ! $this->check_conditions( $conditions, $data ) ) {
			return;
		}

		// Per-rule delay (seconds). If > 0, schedule via wp_schedule_single_event.
		$delay = isset( $rule['delay'] ) ? absint( $rule['delay'] ) : 0;
		if ( $delay > 0 ) {
			wp_schedule_single_event( time() + $delay, 'fwa_execute_delayed_rule', array( $rule, $data ) );
			return;
		}

		$this->dispatch_rule( $rule, $data );
	}

	/**
	 * WP cron callback: execute a rule that was delayed.
	 *
	 * @since 1.2.0
	 *
	 * @param array $rule Rule definition.
	 * @param array $data Event data.
	 */
	public function execute_delayed_rule_callback( $rule, $data ) {
		$this->dispatch_rule( $rule, $data );
	}

	/**
	 * Send a message (and optional admin alert) for a rule.
	 *
	 * @since 1.2.0
	 *
	 * @param array $rule Rule definition.
	 * @param array $data Event data.
	 * @return void
	 */
	private function dispatch_rule( $rule, $data ) {
		// Distributed license guard — secondary check at dispatch time (fail-closed).
		if ( ! FWA_License_Guard::is_licensed() ) {
			return;
		}

		// Resolve phone.
		$phone_field = isset( $rule['phone_field'] ) ? $rule['phone_field'] : 'billing_phone';
		$phone       = $this->resolve_phone( $data, $phone_field );

		if ( empty( $phone ) ) {
			return;
		}

		// Resolve template.
		$template = isset( $rule['template'] ) ? $rule['template'] : '';
		$content  = $this->resolve_template( $template, $data );

		// Build send arguments.
		$send_args = array(
			'to'      => $phone,
			'type'    => isset( $rule['message_type'] ) ? $rule['message_type'] : 'text',
			'content' => $content,
		);

		if ( ! empty( $rule['instance_id'] ) ) {
			$send_args['instance_id'] = absint( $rule['instance_id'] );
		}

		if ( ! empty( $rule['media_url'] ) ) {
			$send_args['media_url'] = $rule['media_url'];
		}

		$this->message_sender->send( $send_args );

		// Admin-alert recipient: send a copy to a configured admin phone if the rule specifies it.
		$admin_alert_phone = isset( $rule['admin_alert_phone'] ) ? sanitize_text_field( $rule['admin_alert_phone'] ) : '';
		if ( empty( $admin_alert_phone ) ) {
			// Fallback to global admin alert phone if rule enables it.
			if ( ! empty( $rule['admin_alert'] ) ) {
				$admin_alert_phone = get_option( 'fwa_admin_alert_phone', '' );
			}
		}

		if ( ! empty( $admin_alert_phone ) ) {
			$admin_content = $this->resolve_template(
				isset( $rule['admin_alert_template'] ) && ! empty( $rule['admin_alert_template'] )
					? $rule['admin_alert_template']
					: '[Admin Alert] Event: ' . ( isset( $data['event'] ) ? $data['event'] : '' ) . ' | Customer: {phone} | Order: {order_id}',
				$data
			);

			$admin_send_args = array(
				'to'      => $admin_alert_phone,
				'type'    => 'text',
				'content' => $admin_content,
			);

			if ( ! empty( $rule['instance_id'] ) ) {
				$admin_send_args['instance_id'] = absint( $rule['instance_id'] );
			}

			$this->message_sender->send( $admin_send_args );
		}
	}

	/**
	 * Send a WooCommerce per-status notification if configured in Settings → WooCommerce.
	 *
	 * @since 1.1.0
	 *
	 * @param string $status WooCommerce status (without wc- prefix).
	 * @param array  $data   Normalised order data from extract_order_data().
	 * @return void
	 */
	private function send_wc_status_notification( $status, $data ) {
		$wc_notifications = get_option( 'fwa_wc_notifications', array() );

		if ( ! is_array( $wc_notifications ) || empty( $wc_notifications[ $status ] ) ) {
			return;
		}

		$notif = $wc_notifications[ $status ];

		if ( empty( $notif['enabled'] ) || empty( $notif['message'] ) ) {
			return;
		}

		$phone = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';
		if ( empty( $phone ) ) {
			$phone = isset( $data['phone'] ) ? $data['phone'] : '';
		}

		if ( empty( $phone ) ) {
			return;
		}

		$phone   = FWA_Helpers::sanitize_phone( $phone );
		$content = $this->resolve_template( $notif['message'], $data );

		$this->message_sender->send( array(
			'to'      => $phone,
			'type'    => 'text',
			'content' => $content,
		) );

		// Admin alert when a configured status notification fires.
		if ( 'yes' === get_option( 'fwa_wc_admin_order_alert', 'no' ) ) {
			$admin_phone = get_option( 'fwa_admin_alert_phone', '' );
			if ( ! empty( $admin_phone ) ) {
				$admin_content = $this->resolve_template(
					'[Admin] New order #{order_id} by {customer_name} — {order_total} ({order_status})',
					$data
				);
				$this->message_sender->send( array(
					'to'      => $admin_phone,
					'type'    => 'text',
					'content' => $admin_content,
				) );
			}
		}
	}

	/**
	 * Extract standard data array from a WooCommerce order.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array Normalised data.
	 */
	private function extract_order_data( $order ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name'  => $item->get_name(),
				'qty'   => $item->get_quantity(),
				'total' => $item->get_total(),
			);
		}

		return array(
			'order_id'      => $order->get_id(),
			'order_total'   => $order->get_total(),
			'order_status'  => $order->get_status(),
			'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'email'         => $order->get_billing_email(),
			'phone'         => $order->get_billing_phone(),
			'billing_phone' => $order->get_billing_phone(),
			'order_items'   => $items,
			'date'          => $order->get_date_created()
				? $order->get_date_created()->date( 'Y-m-d H:i:s' )
				: current_time( 'mysql' ),
		);
	}

	/*--------------------------------------------------------------
	 * WooCommerce Subscriptions event handlers
	 *------------------------------------------------------------*/

	/**
	 * Handle WooCommerce Subscription activated.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 */
	public function on_subscription_activated( $subscription ) {
		$data  = $this->extract_subscription_data( $subscription, 'activated' );
		$rules = $this->get_rules( 'subscription_activated' );
		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}
		do_action( 'fwa_automation_triggered', 'subscription_activated', $subscription );
	}

	/**
	 * Handle WooCommerce Subscription renewal payment.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Order        $renewal_order Renewal order object.
	 * @param WC_Subscription $subscription  Subscription object.
	 */
	public function on_subscription_renewed( $renewal_order, $subscription ) {
		$data  = $this->extract_subscription_data( $subscription, 'renewed' );
		if ( is_object( $renewal_order ) && method_exists( $renewal_order, 'get_id' ) ) {
			$data['renewal_order_id'] = $renewal_order->get_id();
			$data['renewal_total']    = $renewal_order->get_total();
		}
		$rules = $this->get_rules( 'subscription_renewed' );
		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}
		do_action( 'fwa_automation_triggered', 'subscription_renewed', $subscription );
	}

	/**
	 * Handle WooCommerce Subscription cancelled.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 */
	public function on_subscription_cancelled( $subscription ) {
		$data  = $this->extract_subscription_data( $subscription, 'cancelled' );
		$rules = $this->get_rules( 'subscription_cancelled' );
		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}
		do_action( 'fwa_automation_triggered', 'subscription_cancelled', $subscription );
	}

	/**
	 * Handle WooCommerce Subscription expired.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 */
	public function on_subscription_expired( $subscription ) {
		$data  = $this->extract_subscription_data( $subscription, 'expired' );
		$rules = $this->get_rules( 'subscription_expired' );
		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}
		do_action( 'fwa_automation_triggered', 'subscription_expired', $subscription );
	}

	/**
	 * Handle WooCommerce Subscription payment failed.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Subscription $subscription  Subscription object.
	 * @param WC_Order        $renewal_order Failed renewal order.
	 */
	public function on_subscription_payment_failed( $subscription, $renewal_order ) {
		$data  = $this->extract_subscription_data( $subscription, 'payment_failed' );
		$rules = $this->get_rules( 'subscription_payment_failed' );
		foreach ( $rules as $rule ) {
			$this->execute_rule( $rule, $data );
		}
		do_action( 'fwa_automation_triggered', 'subscription_payment_failed', $subscription );
	}

	/**
	 * Extract normalised data from a WooCommerce Subscription.
	 *
	 * @since 1.2.0
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 * @param string          $event        Event name.
	 * @return array
	 */
	private function extract_subscription_data( $subscription, $event ) {
		$data = array(
			'event'               => $event,
			'subscription_id'     => $subscription->get_id(),
			'subscription_status' => $subscription->get_status(),
			'customer_name'       => $subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name(),
			'email'               => $subscription->get_billing_email(),
			'phone'               => $subscription->get_billing_phone(),
			'billing_phone'       => $subscription->get_billing_phone(),
			'order_total'         => $subscription->get_total(),
			'date'                => current_time( 'mysql' ),
		);

		// Attach parent order ID if available.
		$parent_order = $subscription->get_parent();
		if ( $parent_order ) {
			$data['order_id'] = $parent_order->get_id();
		}

		return $data;
	}
}
