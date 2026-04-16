<?php
/**
 * Admin settings handler.
 *
 * Registers, sanitizes, and renders tabbed plugin settings.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Admin_Settings
 *
 * @since 1.0.0
 */
class FWA_Admin_Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'fwa_settings';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register all plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// --- General section ---------------------------------------------------
		add_settings_section(
			'fwa_section_general',
			__( 'General Settings', 'flexi-whatsapp-automation' ),
			'__return_null',
			self::OPTION_GROUP
		);

		register_setting( self::OPTION_GROUP, 'fwa_default_country_code', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'fwa_default_country_code',
			__( 'Default Country Code', 'flexi-whatsapp-automation' ),
			array( $this, 'render_text_field' ),
			self::OPTION_GROUP,
			'fwa_section_general',
			array(
				'label_for'   => 'fwa_default_country_code',
				'description' => __( 'Default country code for phone numbers (e.g. +1, +91).', 'flexi-whatsapp-automation' ),
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_enable_automation', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'yes',
		) );

		register_setting( self::OPTION_GROUP, 'fwa_enable_logging', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'yes',
		) );

		register_setting( self::OPTION_GROUP, 'fwa_enable_campaigns', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'yes',
		) );

		// --- API section -------------------------------------------------------
		add_settings_section(
			'fwa_section_api',
			__( 'API Configuration', 'flexi-whatsapp-automation' ),
			'__return_null',
			self::OPTION_GROUP
		);

		register_setting( self::OPTION_GROUP, 'fwa_api_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		) );

		add_settings_field(
			'fwa_api_base_url',
			__( 'API Base URL', 'flexi-whatsapp-automation' ),
			array( $this, 'render_text_field' ),
			self::OPTION_GROUP,
			'fwa_section_api',
			array(
				'label_for'   => 'fwa_api_base_url',
				'description' => __( 'WhatsApp API base URL.', 'flexi-whatsapp-automation' ),
				'type'        => 'url',
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_api_global_token', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'fwa_api_global_token',
			__( 'Global API Token', 'flexi-whatsapp-automation' ),
			array( $this, 'render_password_field' ),
			self::OPTION_GROUP,
			'fwa_section_api',
			array(
				'label_for'   => 'fwa_api_global_token',
				'description' => __( 'Global API access token.', 'flexi-whatsapp-automation' ),
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_webhook_secret', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'fwa_webhook_secret',
			__( 'Webhook Secret', 'flexi-whatsapp-automation' ),
			array( $this, 'render_text_field' ),
			self::OPTION_GROUP,
			'fwa_section_api',
			array(
				'label_for'   => 'fwa_webhook_secret',
				'description' => __( 'Secret token for webhook verification.', 'flexi-whatsapp-automation' ),
			)
		);

		// --- Messaging section -------------------------------------------------
		add_settings_section(
			'fwa_section_messaging',
			__( 'Messaging Settings', 'flexi-whatsapp-automation' ),
			'__return_null',
			self::OPTION_GROUP
		);

		register_setting( self::OPTION_GROUP, 'fwa_rate_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 20,
		) );

		add_settings_field(
			'fwa_rate_limit',
			__( 'Rate Limit', 'flexi-whatsapp-automation' ),
			array( $this, 'render_number_field' ),
			self::OPTION_GROUP,
			'fwa_section_messaging',
			array(
				'label_for'   => 'fwa_rate_limit',
				'description' => __( 'Maximum messages per interval.', 'flexi-whatsapp-automation' ),
				'min'         => 1,
				'default'     => 20,
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_rate_limit_interval', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 60,
		) );

		add_settings_field(
			'fwa_rate_limit_interval',
			__( 'Rate Limit Interval', 'flexi-whatsapp-automation' ),
			array( $this, 'render_number_field' ),
			self::OPTION_GROUP,
			'fwa_section_messaging',
			array(
				'label_for'   => 'fwa_rate_limit_interval',
				'description' => __( 'Interval in seconds.', 'flexi-whatsapp-automation' ),
				'min'         => 1,
				'default'     => 60,
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_campaign_message_delay', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3,
		) );

		add_settings_field(
			'fwa_campaign_message_delay',
			__( 'Campaign Message Delay', 'flexi-whatsapp-automation' ),
			array( $this, 'render_number_field' ),
			self::OPTION_GROUP,
			'fwa_section_messaging',
			array(
				'label_for'   => 'fwa_campaign_message_delay',
				'description' => __( 'Seconds between campaign messages.', 'flexi-whatsapp-automation' ),
				'min'         => 0,
				'default'     => 3,
			)
		);

		// --- Automation section ------------------------------------------------
		add_settings_section(
			'fwa_section_automation',
			__( 'Automation Settings', 'flexi-whatsapp-automation' ),
			'__return_null',
			self::OPTION_GROUP
		);

		register_setting( self::OPTION_GROUP, 'fwa_auto_reconnect', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'yes',
		) );

		add_settings_field(
			'fwa_auto_reconnect',
			__( 'Auto-Reconnect', 'flexi-whatsapp-automation' ),
			array( $this, 'render_checkbox_field' ),
			self::OPTION_GROUP,
			'fwa_section_automation',
			array(
				'label_for'   => 'fwa_auto_reconnect',
				'description' => __( 'Automatically reconnect disconnected instances.', 'flexi-whatsapp-automation' ),
				'default'     => 'yes',
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_health_check_interval', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		add_settings_field(
			'fwa_health_check_interval',
			__( 'Health Check Interval', 'flexi-whatsapp-automation' ),
			array( $this, 'render_number_field' ),
			self::OPTION_GROUP,
			'fwa_section_automation',
			array(
				'label_for'   => 'fwa_health_check_interval',
				'description' => __( 'Minutes between instance health checks.', 'flexi-whatsapp-automation' ),
				'min'         => 1,
				'default'     => 5,
			)
		);

		// --- Advanced section --------------------------------------------------
		add_settings_section(
			'fwa_section_advanced',
			__( 'Advanced / Logging', 'flexi-whatsapp-automation' ),
			'__return_null',
			self::OPTION_GROUP
		);

		register_setting( self::OPTION_GROUP, 'fwa_debug_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'no',
		) );

		add_settings_field(
			'fwa_debug_mode',
			__( 'Debug Mode', 'flexi-whatsapp-automation' ),
			array( $this, 'render_checkbox_field' ),
			self::OPTION_GROUP,
			'fwa_section_advanced',
			array(
				'label_for'   => 'fwa_debug_mode',
				'description' => __( 'Enable debug logging.', 'flexi-whatsapp-automation' ),
				'default'     => 'no',
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_file_logging', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'no',
		) );

		add_settings_field(
			'fwa_file_logging',
			__( 'File Logging', 'flexi-whatsapp-automation' ),
			array( $this, 'render_checkbox_field' ),
			self::OPTION_GROUP,
			'fwa_section_advanced',
			array(
				'label_for'   => 'fwa_file_logging',
				'description' => __( 'Enable logging to file.', 'flexi-whatsapp-automation' ),
				'default'     => 'no',
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_log_level', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_log_level' ),
			'default'           => 'info',
		) );

		add_settings_field(
			'fwa_log_level',
			__( 'Log Level', 'flexi-whatsapp-automation' ),
			array( $this, 'render_select_field' ),
			self::OPTION_GROUP,
			'fwa_section_advanced',
			array(
				'label_for'   => 'fwa_log_level',
				'description' => __( 'Minimum log level to record.', 'flexi-whatsapp-automation' ),
				'options'     => array(
					'debug'    => __( 'Debug', 'flexi-whatsapp-automation' ),
					'info'     => __( 'Info', 'flexi-whatsapp-automation' ),
					'warning'  => __( 'Warning', 'flexi-whatsapp-automation' ),
					'error'    => __( 'Error', 'flexi-whatsapp-automation' ),
					'critical' => __( 'Critical', 'flexi-whatsapp-automation' ),
				),
				'default'     => 'info',
			)
		);

		register_setting( self::OPTION_GROUP, 'fwa_log_retention_days', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 30,
		) );

		add_settings_field(
			'fwa_log_retention_days',
			__( 'Log Retention', 'flexi-whatsapp-automation' ),
			array( $this, 'render_number_field' ),
			self::OPTION_GROUP,
			'fwa_section_advanced',
			array(
				'label_for'   => 'fwa_log_retention_days',
				'description' => __( 'Number of days to keep log entries.', 'flexi-whatsapp-automation' ),
				'min'         => 1,
				'default'     => 30,
			)
		);

		/**
		 * Fires after all FWA settings are registered.
		 *
		 * Extensions can register additional settings here.
		 *
		 * @since 1.0.0
		 */
		do_action( 'fwa_register_settings' );
	}

	/**
	 * Sanitize a combined settings array.
	 *
	 * Used when settings are submitted as a single grouped option.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Raw form data.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$clean = array();

		if ( isset( $input['fwa_api_base_url'] ) ) {
			$clean['fwa_api_base_url'] = esc_url_raw( $input['fwa_api_base_url'] );
		}

		if ( isset( $input['fwa_api_global_token'] ) ) {
			$clean['fwa_api_global_token'] = sanitize_text_field( $input['fwa_api_global_token'] );
		}

		if ( isset( $input['fwa_webhook_secret'] ) ) {
			$clean['fwa_webhook_secret'] = sanitize_text_field( $input['fwa_webhook_secret'] );
		}

		$int_fields = array(
			'fwa_rate_limit',
			'fwa_rate_limit_interval',
			'fwa_log_retention_days',
			'fwa_campaign_message_delay',
			'fwa_health_check_interval',
		);

		foreach ( $int_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$clean[ $field ] = absint( $input[ $field ] );
			}
		}

		$checkbox_fields = array(
			'fwa_debug_mode',
			'fwa_file_logging',
			'fwa_auto_reconnect',
			'fwa_enable_automation',
			'fwa_enable_logging',
			'fwa_enable_campaigns',
		);

		foreach ( $checkbox_fields as $field ) {
			$clean[ $field ] = ! empty( $input[ $field ] ) ? 'yes' : 'no';
		}

		if ( isset( $input['fwa_log_level'] ) ) {
			$clean['fwa_log_level'] = $this->sanitize_log_level( $input['fwa_log_level'] );
		}

		if ( isset( $input['fwa_default_country_code'] ) ) {
			$clean['fwa_default_country_code'] = sanitize_text_field( $input['fwa_default_country_code'] );
		}

		return $clean;
	}

	/**
	 * Get the available settings tabs.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of tab_slug => label.
	 */
	public function get_tabs() {
		$tabs = array(
			'general'    => __( 'General', 'flexi-whatsapp-automation' ),
			'api'        => __( 'API', 'flexi-whatsapp-automation' ),
			'messaging'  => __( 'Messaging', 'flexi-whatsapp-automation' ),
			'automation' => __( 'Automation', 'flexi-whatsapp-automation' ),
			'advanced'   => __( 'Advanced', 'flexi-whatsapp-automation' ),
		);

		/**
		 * Filters the settings page tabs.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tabs Tab slug => label pairs.
		 */
		return apply_filters( 'fwa_settings_tabs', $tabs );
	}

	/**
	 * Render the tabbed settings page.
	 *
	 * Called from the settings view file.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		$tabs        = $this->get_tabs();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $tabs[ $current_tab ] ) ) {
			$current_tab = 'general';
		}

		$tab_section_map = array(
			'general'    => 'fwa_section_general',
			'api'        => 'fwa_section_api',
			'messaging'  => 'fwa_section_messaging',
			'automation' => 'fwa_section_automation',
			'advanced'   => 'fwa_section_advanced',
		);

		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			$url   = add_query_arg( array( 'page' => 'fwa-settings', 'tab' => $slug ), admin_url( 'admin.php' ) );
			$class = ( $slug === $current_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $label ) );
		}
		echo '</nav>';

		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );

		echo '<table class="form-table" role="presentation">';

		$section_id = isset( $tab_section_map[ $current_tab ] ) ? $tab_section_map[ $current_tab ] : '';
		if ( $section_id ) {
			do_settings_fields( self::OPTION_GROUP, $section_id );
		}

		echo '</table>';

		submit_button( __( 'Save Settings', 'flexi-whatsapp-automation' ) );
		wp_nonce_field( 'fwa_settings_nonce', 'fwa_settings_nonce_field' );
		echo '</form>';
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Render a text input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( $args ) {
		$id    = $args['label_for'];
		$type  = isset( $args['type'] ) ? $args['type'] : 'text';
		$value = get_option( $id, '' );

		printf(
			'<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $value )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a password input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_password_field( $args ) {
		$id    = $args['label_for'];
		$value = get_option( $id, '' );

		printf(
			'<input type="password" id="%s" name="%s" value="%s" class="regular-text" autocomplete="off" />',
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $value )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a number input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_number_field( $args ) {
		$id      = $args['label_for'];
		$min     = isset( $args['min'] ) ? $args['min'] : 0;
		$default = isset( $args['default'] ) ? $args['default'] : 0;
		$value   = get_option( $id, $default );

		printf(
			'<input type="number" id="%s" name="%s" value="%s" min="%s" class="small-text" />',
			esc_attr( $id ),
			esc_attr( $id ),
			esc_attr( $value ),
			esc_attr( $min )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( $args ) {
		$id      = $args['label_for'];
		$default = isset( $args['default'] ) ? $args['default'] : 'no';
		$value   = get_option( $id, $default );

		printf(
			'<input type="checkbox" id="%s" name="%s" value="yes" %s />',
			esc_attr( $id ),
			esc_attr( $id ),
			checked( 'yes', $value, false )
		);

		if ( ! empty( $args['description'] ) ) {
			printf( '<span class="description">%s</span>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a select field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Field arguments.
	 */
	public function render_select_field( $args ) {
		$id      = $args['label_for'];
		$options = isset( $args['options'] ) ? $args['options'] : array();
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $id, $default );

		printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $id ) );
		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	// -------------------------------------------------------------------------
	// Sanitizers
	// -------------------------------------------------------------------------

	/**
	 * Sanitize a checkbox value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted value.
	 * @return string 'yes' or 'no'.
	 */
	public function sanitize_checkbox( $value ) {
		return ( 'yes' === $value ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize the log level value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Submitted log level.
	 * @return string Valid log level.
	 */
	public function sanitize_log_level( $value ) {
		$valid = array( 'debug', 'info', 'warning', 'error', 'critical' );
		return in_array( $value, $valid, true ) ? $value : 'info';
	}
}
