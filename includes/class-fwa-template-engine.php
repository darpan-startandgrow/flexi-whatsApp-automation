<?php
/**
 * Message template engine with placeholder resolution.
 *
 * Provides a centralized template system with a cheat-sheet
 * of available placeholders and dynamic variable resolution.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Template_Engine
 *
 * Resolves message templates by replacing placeholders with
 * actual values from event data, user data, and site data.
 *
 * @since 1.1.0
 */
class FWA_Template_Engine {

	/**
	 * Resolve a template string by replacing placeholders.
	 *
	 * Placeholders use the format {placeholder_name}.
	 *
	 * @since 1.1.0
	 *
	 * @param string $template Template string with placeholders.
	 * @param array  $data     Context data for variable resolution.
	 * @return string Resolved message.
	 */
	public static function resolve( $template, $data = array() ) {
		$variables = self::get_variables( $data );

		/**
		 * Filter template variables before resolution.
		 *
		 * @since 1.1.0
		 *
		 * @param array $variables Placeholder => value pairs.
		 * @param array $data      Raw context data.
		 */
		$variables = apply_filters( 'fwa_template_variables', $variables, $data );

		// Replace all {placeholder} patterns.
		$message = preg_replace_callback(
			'/\{([a-zA-Z0-9_]+)\}/',
			function ( $matches ) use ( $variables ) {
				$key = $matches[1];
				return isset( $variables[ $key ] ) ? $variables[ $key ] : $matches[0];
			},
			$template
		);

		/**
		 * Filter the fully resolved template.
		 *
		 * @since 1.1.0
		 *
		 * @param string $message  Resolved message.
		 * @param array  $data     Raw context data.
		 */
		return apply_filters( 'fwa_resolved_template', $message, $data );
	}

	/**
	 * Build the full variable map from context data.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data Context data.
	 * @return array Variable name => value pairs.
	 */
	public static function get_variables( $data = array() ) {
		$vars = array();

		// Site variables (always available).
		$vars['site_name']  = get_bloginfo( 'name' );
		$vars['site_url']   = home_url();
		$vars['store_name'] = get_bloginfo( 'name' );
		$vars['admin_email']= get_option( 'admin_email' );
		$vars['date']       = wp_date( get_option( 'date_format' ) );
		$vars['time']       = wp_date( get_option( 'time_format' ) );
		$vars['year']       = wp_date( 'Y' );

		// Direct data pass-through.
		if ( ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_scalar( $value ) ) {
					$vars[ $key ] = (string) $value;
				}
			}
		}

		// WooCommerce order variables.
		if ( isset( $data['order_id'] ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $data['order_id'] );
			if ( $order ) {
				$vars = array_merge( $vars, self::get_order_variables( $order ) );
			}
		}

		// User variables.
		if ( isset( $data['user_id'] ) ) {
			$user = get_user_by( 'ID', $data['user_id'] );
			if ( $user ) {
				$vars = array_merge( $vars, self::get_user_variables( $user ) );
			}
		}

		return $vars;
	}

	/**
	 * Get WooCommerce order placeholder variables.
	 *
	 * @since 1.1.0
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return array Variables.
	 */
	private static function get_order_variables( $order ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = $item->get_name() . ' x' . $item->get_quantity();
		}

		return array(
			'order_id'        => $order->get_id(),
			'order_number'    => $order->get_order_number(),
			'order_total'     => $order->get_formatted_order_total(),
			'order_status'    => wc_get_order_status_name( $order->get_status() ),
			'order_date'      => $order->get_date_created() ? $order->get_date_created()->date( get_option( 'date_format' ) ) : '',
			'order_items'     => implode( ', ', $items ),
			'order_item_count'=> $order->get_item_count(),
			'payment_method'  => $order->get_payment_method_title(),
			'shipping_method' => $order->get_shipping_method(),
			'order_url'       => $order->get_view_order_url(),
			'customer_name'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_first'  => $order->get_billing_first_name(),
			'customer_last'   => $order->get_billing_last_name(),
			'customer_email'  => $order->get_billing_email(),
			'customer_phone'  => $order->get_billing_phone(),
			'billing_address' => $order->get_formatted_billing_address(),
			'shipping_address'=> $order->get_formatted_shipping_address(),
			'billing_city'    => $order->get_billing_city(),
			'billing_state'   => $order->get_billing_state(),
			'billing_country' => $order->get_billing_country(),
			'currency'        => $order->get_currency(),
			'subtotal'        => wc_price( $order->get_subtotal() ),
			'discount'        => wc_price( $order->get_discount_total() ),
			'shipping_total'  => wc_price( $order->get_shipping_total() ),
			'tax_total'       => wc_price( $order->get_total_tax() ),
		);
	}

	/**
	 * Get WordPress user placeholder variables.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_User $user User object.
	 * @return array Variables.
	 */
	private static function get_user_variables( $user ) {
		return array(
			'user_id'         => $user->ID,
			'username'        => $user->user_login,
			'user_email'      => $user->user_email,
			'user_first_name' => $user->first_name,
			'user_last_name'  => $user->last_name,
			'display_name'    => $user->display_name,
			'user_role'       => implode( ', ', $user->roles ),
		);
	}

	/**
	 * Get the complete placeholder cheat-sheet.
	 *
	 * Returns a structured array of all available placeholders,
	 * organized by category for display in admin UI.
	 *
	 * @since 1.1.0
	 *
	 * @return array Categories with placeholder descriptions.
	 */
	public static function get_cheat_sheet() {
		$sheet = array(
			'site' => array(
				'label'        => __( 'Site', 'flexi-whatsapp-automation' ),
				'placeholders' => array(
					'{site_name}'   => __( 'Site name', 'flexi-whatsapp-automation' ),
					'{site_url}'    => __( 'Site URL', 'flexi-whatsapp-automation' ),
					'{store_name}'  => __( 'Store name (same as site name)', 'flexi-whatsapp-automation' ),
					'{admin_email}' => __( 'Admin email address', 'flexi-whatsapp-automation' ),
					'{date}'        => __( 'Current date', 'flexi-whatsapp-automation' ),
					'{time}'        => __( 'Current time', 'flexi-whatsapp-automation' ),
					'{year}'        => __( 'Current year', 'flexi-whatsapp-automation' ),
				),
			),
			'user' => array(
				'label'        => __( 'User', 'flexi-whatsapp-automation' ),
				'placeholders' => array(
					'{user_id}'         => __( 'User ID', 'flexi-whatsapp-automation' ),
					'{username}'        => __( 'Username', 'flexi-whatsapp-automation' ),
					'{user_email}'      => __( 'User email', 'flexi-whatsapp-automation' ),
					'{user_first_name}' => __( 'First name', 'flexi-whatsapp-automation' ),
					'{user_last_name}'  => __( 'Last name', 'flexi-whatsapp-automation' ),
					'{display_name}'    => __( 'Display name', 'flexi-whatsapp-automation' ),
					'{user_role}'       => __( 'User role', 'flexi-whatsapp-automation' ),
				),
			),
			'order' => array(
				'label'        => __( 'WooCommerce Order', 'flexi-whatsapp-automation' ),
				'placeholders' => array(
					'{order_id}'         => __( 'Order ID', 'flexi-whatsapp-automation' ),
					'{order_number}'     => __( 'Order number', 'flexi-whatsapp-automation' ),
					'{order_total}'      => __( 'Order total (formatted)', 'flexi-whatsapp-automation' ),
					'{order_status}'     => __( 'Order status', 'flexi-whatsapp-automation' ),
					'{order_date}'       => __( 'Order date', 'flexi-whatsapp-automation' ),
					'{order_items}'      => __( 'List of items', 'flexi-whatsapp-automation' ),
					'{order_item_count}' => __( 'Number of items', 'flexi-whatsapp-automation' ),
					'{payment_method}'   => __( 'Payment method', 'flexi-whatsapp-automation' ),
					'{shipping_method}'  => __( 'Shipping method', 'flexi-whatsapp-automation' ),
					'{order_url}'        => __( 'Order view URL', 'flexi-whatsapp-automation' ),
					'{customer_name}'    => __( 'Customer full name', 'flexi-whatsapp-automation' ),
					'{customer_first}'   => __( 'Customer first name', 'flexi-whatsapp-automation' ),
					'{customer_last}'    => __( 'Customer last name', 'flexi-whatsapp-automation' ),
					'{customer_email}'   => __( 'Customer email', 'flexi-whatsapp-automation' ),
					'{customer_phone}'   => __( 'Customer phone', 'flexi-whatsapp-automation' ),
					'{billing_address}'  => __( 'Billing address', 'flexi-whatsapp-automation' ),
					'{shipping_address}' => __( 'Shipping address', 'flexi-whatsapp-automation' ),
					'{billing_city}'     => __( 'Billing city', 'flexi-whatsapp-automation' ),
					'{billing_country}'  => __( 'Billing country', 'flexi-whatsapp-automation' ),
					'{currency}'         => __( 'Order currency', 'flexi-whatsapp-automation' ),
					'{subtotal}'         => __( 'Order subtotal', 'flexi-whatsapp-automation' ),
					'{discount}'         => __( 'Discount total', 'flexi-whatsapp-automation' ),
					'{shipping_total}'   => __( 'Shipping total', 'flexi-whatsapp-automation' ),
					'{tax_total}'        => __( 'Tax total', 'flexi-whatsapp-automation' ),
				),
			),
		);

		/**
		 * Filter the placeholder cheat-sheet.
		 *
		 * @since 1.1.0
		 *
		 * @param array $sheet Cheat-sheet data.
		 */
		return apply_filters( 'fwa_template_cheat_sheet', $sheet );
	}
}
