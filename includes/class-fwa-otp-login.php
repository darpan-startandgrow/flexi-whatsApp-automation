<?php
/**
 * Public-facing OTP login and verification shortcodes.
 *
 * Provides [fwa_otp_login], [fwa_otp_verify], and [fwa_phone_verify_bar]
 * shortcodes for frontend WhatsApp-based authentication.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_OTP_Login
 *
 * Handles frontend OTP login, signup verification, and WooCommerce
 * checkout phone verification via WhatsApp OTP.
 *
 * @since 1.1.0
 */
class FWA_OTP_Login {

	/**
	 * OTP Manager instance.
	 *
	 * @since 1.1.0
	 * @var FWA_OTP_Manager
	 */
	private $otp_manager;

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->otp_manager = new FWA_OTP_Manager();

		// Shortcodes.
		add_shortcode( 'fwa_otp_login', array( $this, 'shortcode_otp_login' ) );
		add_shortcode( 'fwa_otp_verify', array( $this, 'shortcode_otp_verify' ) );
		add_shortcode( 'fwa_phone_verify_bar', array( $this, 'shortcode_phone_verify_bar' ) );

		// Public AJAX (both logged-in and not).
		add_action( 'wp_ajax_fwa_public_send_otp', array( $this, 'ajax_send_otp' ) );
		add_action( 'wp_ajax_nopriv_fwa_public_send_otp', array( $this, 'ajax_send_otp' ) );
		add_action( 'wp_ajax_fwa_public_verify_otp', array( $this, 'ajax_verify_otp' ) );
		add_action( 'wp_ajax_nopriv_fwa_public_verify_otp', array( $this, 'ajax_verify_otp' ) );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend OTP assets.
	 *
	 * @since 1.1.0
	 */
	public function enqueue_assets() {
		// Only load if shortcode is used on this page (WordPress handles this).
		wp_register_style(
			'fwa-otp-login',
			FWA_PLUGIN_URL . 'public/css/fwa-otp-login.css',
			array(),
			FWA_VERSION
		);

		wp_register_script(
			'fwa-otp-login',
			FWA_PLUGIN_URL . 'public/js/fwa-otp-login.js',
			array( 'jquery' ),
			FWA_VERSION,
			true
		);

		wp_localize_script(
			'fwa-otp-login',
			'fwa_otp',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'fwa_public_otp_nonce' ),
				'strings'  => array(
					'sending'      => __( 'Sending code…', 'flexi-whatsapp-automation' ),
					'verifying'    => __( 'Verifying…', 'flexi-whatsapp-automation' ),
					'code_sent'    => __( 'Verification code sent to your WhatsApp.', 'flexi-whatsapp-automation' ),
					'enter_phone'  => __( 'Please enter your phone number.', 'flexi-whatsapp-automation' ),
					'enter_code'   => __( 'Please enter the 6-digit code.', 'flexi-whatsapp-automation' ),
					'verified'     => __( 'Phone number verified successfully!', 'flexi-whatsapp-automation' ),
					'network_error'=> __( 'Network error. Please try again.', 'flexi-whatsapp-automation' ),
				),
			)
		);
	}

	/**
	 * Shortcode: [fwa_otp_login]
	 *
	 * Renders a complete OTP-based login/signup form.
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_otp_login( $atts ) {
		$atts = shortcode_atts(
			array(
				'redirect'     => '',
				'title'        => __( 'Login with WhatsApp', 'flexi-whatsapp-automation' ),
				'button_text'  => __( 'Send Code', 'flexi-whatsapp-automation' ),
				'verify_text'  => __( 'Verify & Login', 'flexi-whatsapp-automation' ),
				'show_signup'  => 'yes',
			),
			$atts,
			'fwa_otp_login'
		);

		// Already logged in.
		if ( is_user_logged_in() ) {
			return '<p class="fwa-otp-logged-in">' . esc_html__( 'You are already logged in.', 'flexi-whatsapp-automation' ) . '</p>';
		}

		wp_enqueue_style( 'fwa-otp-login' );
		wp_enqueue_script( 'fwa-otp-login' );

		$redirect = ! empty( $atts['redirect'] ) ? esc_url( $atts['redirect'] ) : '';

		ob_start();
		?>
		<div class="fwa-otp-form" id="fwa-otp-login-form" data-mode="login" data-redirect="<?php echo esc_attr( $redirect ); ?>">
			<h3><?php echo esc_html( $atts['title'] ); ?></h3>
			<p class="fwa-otp-info"><?php esc_html_e( 'You will receive a verification code on WhatsApp (not SMS).', 'flexi-whatsapp-automation' ); ?></p>

			<!-- Step 1: Phone Entry -->
			<div class="fwa-otp-step fwa-otp-step-phone" data-step="phone">
				<div class="fwa-otp-field">
					<label for="fwa-otp-phone"><?php esc_html_e( 'WhatsApp Phone Number', 'flexi-whatsapp-automation' ); ?></label>
					<input type="tel" id="fwa-otp-phone" name="phone" class="fwa-otp-input" placeholder="+1234567890" autocomplete="tel">
					<small><?php esc_html_e( 'Include country code (e.g. +1 for US, +91 for India)', 'flexi-whatsapp-automation' ); ?></small>
				</div>
				<button type="button" class="fwa-otp-btn fwa-otp-send-btn"><?php echo esc_html( $atts['button_text'] ); ?></button>
			</div>

			<!-- Step 2: OTP Verification -->
			<div class="fwa-otp-step fwa-otp-step-code" data-step="code" style="display:none;">
				<div class="fwa-otp-field">
					<label for="fwa-otp-code"><?php esc_html_e( 'Verification Code', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-otp-code" name="otp" class="fwa-otp-input fwa-otp-code-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}">
					<small class="fwa-otp-timer"></small>
				</div>
				<button type="button" class="fwa-otp-btn fwa-otp-verify-btn"><?php echo esc_html( $atts['verify_text'] ); ?></button>
				<button type="button" class="fwa-otp-link fwa-otp-resend-btn" disabled><?php esc_html_e( 'Resend Code', 'flexi-whatsapp-automation' ); ?></button>
				<button type="button" class="fwa-otp-link fwa-otp-back-btn"><?php esc_html_e( '← Change number', 'flexi-whatsapp-automation' ); ?></button>
			</div>

			<div class="fwa-otp-status"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [fwa_otp_verify]
	 *
	 * Renders a standalone phone verification form (no login behavior).
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_otp_verify( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'       => __( 'Verify Your Phone', 'flexi-whatsapp-automation' ),
				'button_text' => __( 'Send Code', 'flexi-whatsapp-automation' ),
			),
			$atts,
			'fwa_otp_verify'
		);

		wp_enqueue_style( 'fwa-otp-login' );
		wp_enqueue_script( 'fwa-otp-login' );

		ob_start();
		?>
		<div class="fwa-otp-form" id="fwa-otp-verify-form" data-mode="verify">
			<h3><?php echo esc_html( $atts['title'] ); ?></h3>
			<p class="fwa-otp-info"><?php esc_html_e( 'You will receive a verification code on WhatsApp (not SMS).', 'flexi-whatsapp-automation' ); ?></p>

			<!-- Step 1: Phone Entry -->
			<div class="fwa-otp-step fwa-otp-step-phone" data-step="phone">
				<div class="fwa-otp-field">
					<label for="fwa-otp-phone-v"><?php esc_html_e( 'WhatsApp Phone Number', 'flexi-whatsapp-automation' ); ?></label>
					<input type="tel" id="fwa-otp-phone-v" name="phone" class="fwa-otp-input" placeholder="+1234567890" autocomplete="tel">
				</div>
				<button type="button" class="fwa-otp-btn fwa-otp-send-btn"><?php echo esc_html( $atts['button_text'] ); ?></button>
			</div>

			<!-- Step 2: OTP Verification -->
			<div class="fwa-otp-step fwa-otp-step-code" data-step="code" style="display:none;">
				<div class="fwa-otp-field">
					<label for="fwa-otp-code-v"><?php esc_html_e( 'Verification Code', 'flexi-whatsapp-automation' ); ?></label>
					<input type="text" id="fwa-otp-code-v" name="otp" class="fwa-otp-input fwa-otp-code-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}">
				</div>
				<button type="button" class="fwa-otp-btn fwa-otp-verify-btn"><?php esc_html_e( 'Verify', 'flexi-whatsapp-automation' ); ?></button>
				<button type="button" class="fwa-otp-link fwa-otp-resend-btn" disabled><?php esc_html_e( 'Resend Code', 'flexi-whatsapp-automation' ); ?></button>
			</div>

			<!-- Step 3: Success -->
			<div class="fwa-otp-step fwa-otp-step-success" data-step="success" style="display:none;">
				<div class="fwa-otp-success">
					<span class="fwa-otp-check">&#10003;</span>
					<p><?php esc_html_e( 'Phone number verified successfully!', 'flexi-whatsapp-automation' ); ?></p>
				</div>
			</div>

			<div class="fwa-otp-status"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [fwa_phone_verify_bar]
	 *
	 * Renders a compact phone verification bar.
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_phone_verify_bar( $atts ) {
		$atts = shortcode_atts(
			array(
				'message' => __( 'Verify your phone number to complete your profile.', 'flexi-whatsapp-automation' ),
			),
			$atts,
			'fwa_phone_verify_bar'
		);

		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user_id   = get_current_user_id();
		$verified  = get_user_meta( $user_id, 'fwa_phone_verified', true );

		if ( 'yes' === $verified ) {
			return '';
		}

		wp_enqueue_style( 'fwa-otp-login' );
		wp_enqueue_script( 'fwa-otp-login' );

		ob_start();
		?>
		<div class="fwa-verify-bar" id="fwa-verify-bar" data-mode="verify-bar">
			<div class="fwa-verify-bar-content">
				<p><?php echo esc_html( $atts['message'] ); ?></p>
				<div class="fwa-verify-bar-form">
					<input type="tel" class="fwa-otp-input fwa-verify-bar-phone" placeholder="+1234567890">
					<button type="button" class="fwa-otp-btn fwa-otp-send-btn"><?php esc_html_e( 'Verify', 'flexi-whatsapp-automation' ); ?></button>
				</div>
				<div class="fwa-verify-bar-otp" style="display:none;">
					<input type="text" class="fwa-otp-input fwa-otp-code-input" maxlength="6" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}">
					<button type="button" class="fwa-otp-btn fwa-otp-verify-btn"><?php esc_html_e( 'Confirm', 'flexi-whatsapp-automation' ); ?></button>
				</div>
			</div>
			<div class="fwa-otp-status"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/*--------------------------------------------------------------
	 * AJAX handlers (public-facing)
	 *------------------------------------------------------------*/

	/**
	 * AJAX: Send OTP for public forms.
	 *
	 * @since 1.1.0
	 */
	public function ajax_send_otp() {
		check_ajax_referer( 'fwa_public_otp_nonce', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'flexi-whatsapp-automation' ) ) );
		}

		$result = $this->otp_manager->send_otp( $phone );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Verification code sent to your WhatsApp.', 'flexi-whatsapp-automation' ),
		) );
	}

	/**
	 * AJAX: Verify OTP for public forms.
	 *
	 * @since 1.1.0
	 */
	public function ajax_verify_otp() {
		check_ajax_referer( 'fwa_public_otp_nonce', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$otp   = isset( $_POST['otp'] ) ? sanitize_text_field( wp_unslash( $_POST['otp'] ) ) : '';
		$mode  = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'verify';

		if ( empty( $phone ) || empty( $otp ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number and code are required.', 'flexi-whatsapp-automation' ) ) );
		}

		$result = $this->otp_manager->verify_otp( $phone, $otp );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$response = array(
			'message'  => __( 'Phone number verified!', 'flexi-whatsapp-automation' ),
			'verified' => true,
		);

		// For login mode, log the user in or create account.
		if ( 'login' === $mode ) {
			$login_result = $this->handle_otp_login( $phone );
			if ( is_wp_error( $login_result ) ) {
				wp_send_json_error( array( 'message' => $login_result->get_error_message() ) );
			}
			$response['redirect'] = $login_result;
		}

		// For logged-in verify mode, save verified phone to user meta.
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'fwa_phone_verified', 'yes' );
			update_user_meta( $user_id, 'fwa_verified_phone', FWA_Helpers::sanitize_phone( $phone ) );
		}

		wp_send_json_success( $response );
	}

	/*--------------------------------------------------------------
	 * Internal helpers
	 *------------------------------------------------------------*/

	/**
	 * Handle OTP-based login: find or create user, then log in.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Verified phone number.
	 * @return string|WP_Error Redirect URL on success, WP_Error on failure.
	 */
	private function handle_otp_login( $phone ) {
		$phone = FWA_Helpers::sanitize_phone( $phone );

		// Find existing user by phone.
		$user = $this->find_user_by_phone( $phone );

		if ( ! $user ) {
			// Create a new user.
			$user = $this->create_user_from_phone( $phone );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
		}

		// Log the user in.
		wp_set_auth_cookie( $user->ID, true );
		wp_set_current_user( $user->ID );

		update_user_meta( $user->ID, 'fwa_phone_verified', 'yes' );
		update_user_meta( $user->ID, 'fwa_verified_phone', $phone );

		/**
		 * Fires after an OTP-based login.
		 *
		 * @since 1.1.0
		 *
		 * @param WP_User $user  Logged-in user.
		 * @param string  $phone Verified phone number.
		 */
		do_action( 'fwa_otp_login_success', $user, $phone );

		// Determine redirect.
		$redirect = apply_filters( 'fwa_otp_login_redirect', home_url(), $user );

		return $redirect;
	}

	/**
	 * Find a WordPress user by their verified phone.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @return WP_User|null
	 */
	private function find_user_by_phone( $phone ) {
		// Check our custom meta first.
		$users = get_users( array(
			'meta_key'   => 'fwa_verified_phone',
			'meta_value' => $phone,
			'number'     => 1,
		) );

		if ( ! empty( $users ) ) {
			return $users[0];
		}

		// Also check WooCommerce billing phone if available.
		if ( function_exists( 'wc_get_customer_id_by_billing_phone' ) ) {
			// WC doesn't have this function, but we check billing_phone meta.
			$users = get_users( array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
			) );

			if ( ! empty( $users ) ) {
				return $users[0];
			}
		}

		return null;
	}

	/**
	 * Create a new WordPress user from a phone number.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone Phone number.
	 * @return WP_User|WP_Error
	 */
	private function create_user_from_phone( $phone ) {
		$username = 'wa_' . preg_replace( '/[^0-9]/', '', $phone );
		$email    = $username . '@placeholder.whatsapp';
		$password = wp_generate_password( 24, true, true );

		/**
		 * Filter the default role for OTP-created users.
		 *
		 * @since 1.1.0
		 *
		 * @param string $role Default user role.
		 */
		$role = apply_filters( 'fwa_otp_user_role', get_option( 'default_role', 'subscriber' ) );

		$user_id = wp_insert_user( array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'role'       => $role,
		) );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, 'fwa_verified_phone', $phone );
		update_user_meta( $user_id, 'fwa_phone_verified', 'yes' );

		/**
		 * Fires after a user is created via OTP login.
		 *
		 * @since 1.1.0
		 *
		 * @param int    $user_id User ID.
		 * @param string $phone   Phone number.
		 */
		do_action( 'fwa_otp_user_created', $user_id, $phone );

		return get_user_by( 'ID', $user_id );
	}
}
