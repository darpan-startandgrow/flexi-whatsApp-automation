<?php
/**
 * WhatsApp Chat Widget for frontend.
 *
 * Provides a floating WhatsApp chat button with multi-agent support,
 * customizable display rules, and QR code generation.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Chat_Widget
 *
 * Renders a configurable WhatsApp chat widget on the frontend.
 * Supports multiple agents, display conditions, and shortcodes.
 *
 * @since 1.1.0
 */
class FWA_Chat_Widget {

	/**
	 * Default widget settings.
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private $defaults = array(
		'enabled'          => 'no',
		'position'         => 'bottom-right',
		'button_text'      => 'Chat with us',
		'welcome_message'  => 'Hello! How can we help you today?',
		'header_title'     => 'WhatsApp Support',
		'header_subtitle'  => 'Typically replies within minutes',
		'button_color'     => '#25D366',
		'text_color'       => '#ffffff',
		'show_on_mobile'   => 'yes',
		'show_on_desktop'  => 'yes',
		'agents'           => array(),
		'display_pages'    => '',
		'exclude_pages'    => '',
		'display_delay'    => 0,
		'analytics'        => 'no',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'fwa_chat_widget', array( $this, 'shortcode_chat_widget' ) );
		add_shortcode( 'fwa_chat_button', array( $this, 'shortcode_chat_button' ) );
	}

	/**
	 * Get widget settings.
	 *
	 * @since 1.1.0
	 *
	 * @return array Merged widget settings.
	 */
	public function get_settings() {
		$saved = get_option( 'fwa_chat_widget', array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $this->defaults );
	}

	/**
	 * Enqueue frontend assets for the chat widget.
	 *
	 * @since 1.1.0
	 */
	public function enqueue_assets() {
		$settings = $this->get_settings();

		if ( 'yes' !== $settings['enabled'] ) {
			return;
		}

		if ( ! $this->should_display( $settings ) ) {
			return;
		}

		wp_enqueue_style(
			'fwa-chat-widget',
			FWA_PLUGIN_URL . 'public/css/fwa-chat-widget.css',
			array(),
			FWA_VERSION
		);

		wp_enqueue_script(
			'fwa-chat-widget',
			FWA_PLUGIN_URL . 'public/js/fwa-chat-widget.js',
			array(),
			FWA_VERSION,
			true
		);

		wp_localize_script(
			'fwa-chat-widget',
			'fwa_widget',
			array(
				'settings' => $this->get_public_settings( $settings ),
			)
		);
	}

	/**
	 * Render the widget HTML in the footer.
	 *
	 * @since 1.1.0
	 */
	public function render_widget() {
		$settings = $this->get_settings();

		if ( 'yes' !== $settings['enabled'] ) {
			return;
		}

		if ( ! $this->should_display( $settings ) ) {
			return;
		}

		$agents   = $this->get_agents( $settings );
		$position = sanitize_html_class( $settings['position'] );
		?>
		<div id="fwa-chat-widget"
			class="fwa-chat-widget fwa-chat-<?php echo esc_attr( $position ); ?>"
			data-delay="<?php echo absint( $settings['display_delay'] ); ?>"
			style="display:none;">

			<!-- Chat Panel -->
			<div class="fwa-chat-panel" style="display:none;">
				<div class="fwa-chat-header" style="background-color:<?php echo esc_attr( $settings['button_color'] ); ?>;">
					<div class="fwa-chat-header-text">
						<strong><?php echo esc_html( $settings['header_title'] ); ?></strong>
						<span><?php echo esc_html( $settings['header_subtitle'] ); ?></span>
					</div>
					<button type="button" class="fwa-chat-close" aria-label="<?php esc_attr_e( 'Close', 'flexi-whatsapp-automation' ); ?>">&times;</button>
				</div>
				<div class="fwa-chat-body">
					<?php if ( ! empty( $settings['welcome_message'] ) ) : ?>
						<div class="fwa-chat-welcome"><?php echo esc_html( $settings['welcome_message'] ); ?></div>
					<?php endif; ?>
					<div class="fwa-chat-agents">
						<?php foreach ( $agents as $agent ) : ?>
							<?php
							$wa_url = $this->build_wa_url( $agent['phone'], $agent['default_message'] );
							?>
							<a href="<?php echo esc_url( $wa_url ); ?>"
								class="fwa-chat-agent"
								target="_blank"
								rel="noopener noreferrer"
								<?php if ( 'yes' === $settings['analytics'] ) : ?>
									data-fwa-track="chat-click"
									data-fwa-agent="<?php echo esc_attr( $agent['name'] ); ?>"
								<?php endif; ?>>
								<div class="fwa-chat-agent-avatar" style="background-color:<?php echo esc_attr( $settings['button_color'] ); ?>;">
									<?php echo esc_html( mb_strtoupper( mb_substr( $agent['name'], 0, 1 ) ) ); ?>
								</div>
								<div class="fwa-chat-agent-info">
									<strong><?php echo esc_html( $agent['name'] ); ?></strong>
									<?php if ( ! empty( $agent['role'] ) ) : ?>
										<span><?php echo esc_html( $agent['role'] ); ?></span>
									<?php endif; ?>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Floating Button -->
			<button type="button" class="fwa-chat-toggle" style="background-color:<?php echo esc_attr( $settings['button_color'] ); ?>;color:<?php echo esc_attr( $settings['text_color'] ); ?>;">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
					<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
				</svg>
				<?php if ( ! empty( $settings['button_text'] ) ) : ?>
					<span class="fwa-chat-btn-text"><?php echo esc_html( $settings['button_text'] ); ?></span>
				<?php endif; ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Shortcode: [fwa_chat_widget]
	 *
	 * Renders an inline chat widget (not floating).
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_chat_widget( $atts ) {
		$atts = shortcode_atts(
			array(
				'phone'   => '',
				'message' => '',
				'name'    => 'Support',
				'role'    => '',
			),
			$atts,
			'fwa_chat_widget'
		);

		if ( empty( $atts['phone'] ) ) {
			return '';
		}

		$wa_url = $this->build_wa_url( $atts['phone'], $atts['message'] );

		ob_start();
		?>
		<div class="fwa-chat-inline">
			<a href="<?php echo esc_url( $wa_url ); ?>" class="fwa-chat-inline-link" target="_blank" rel="noopener noreferrer">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="#25D366">
					<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
				</svg>
				<?php echo esc_html( $atts['name'] ); ?>
				<?php if ( ! empty( $atts['role'] ) ) : ?>
					<small>(<?php echo esc_html( $atts['role'] ); ?>)</small>
				<?php endif; ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [fwa_chat_button]
	 *
	 * Renders a simple WhatsApp button link.
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_chat_button( $atts ) {
		$atts = shortcode_atts(
			array(
				'phone' => '',
				'text'  => __( 'Chat on WhatsApp', 'flexi-whatsapp-automation' ),
				'message' => '',
				'class' => '',
			),
			$atts,
			'fwa_chat_button'
		);

		if ( empty( $atts['phone'] ) ) {
			return '';
		}

		$wa_url = $this->build_wa_url( $atts['phone'], $atts['message'] );
		$class  = 'fwa-wa-button ' . sanitize_html_class( $atts['class'] );

		return sprintf(
			'<a href="%s" class="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $wa_url ),
			esc_attr( $class ),
			esc_html( $atts['text'] )
		);
	}

	/*--------------------------------------------------------------
	 * Internal helpers
	 *------------------------------------------------------------*/

	/**
	 * Build a WhatsApp click-to-chat URL.
	 *
	 * @since 1.1.0
	 *
	 * @param string $phone   Phone number.
	 * @param string $message Pre-filled message text.
	 * @return string URL.
	 */
	private function build_wa_url( $phone, $message = '' ) {
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		$url   = 'https://wa.me/' . $phone;

		if ( ! empty( $message ) ) {
			$url .= '?text=' . rawurlencode( $message );
		}

		return $url;
	}

	/**
	 * Determine whether the widget should display on the current page.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings Widget settings.
	 * @return bool
	 */
	private function should_display( $settings ) {
		// Device checks.
		if ( 'no' === $settings['show_on_mobile'] && wp_is_mobile() ) {
			return false;
		}
		if ( 'no' === $settings['show_on_desktop'] && ! wp_is_mobile() ) {
			return false;
		}

		// Page include list.
		if ( ! empty( $settings['display_pages'] ) ) {
			$allowed = array_map( 'absint', explode( ',', $settings['display_pages'] ) );
			if ( ! in_array( get_the_ID(), $allowed, true ) ) {
				return false;
			}
		}

		// Page exclude list.
		if ( ! empty( $settings['exclude_pages'] ) ) {
			$excluded = array_map( 'absint', explode( ',', $settings['exclude_pages'] ) );
			if ( in_array( get_the_ID(), $excluded, true ) ) {
				return false;
			}
		}

		/**
		 * Filter whether the chat widget should display.
		 *
		 * @since 1.1.0
		 *
		 * @param bool  $display  Whether to display.
		 * @param array $settings Current widget settings.
		 */
		return apply_filters( 'fwa_chat_widget_display', true, $settings );
	}

	/**
	 * Get agent list from settings.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings Widget settings.
	 * @return array Agent entries.
	 */
	private function get_agents( $settings ) {
		$agents = isset( $settings['agents'] ) ? $settings['agents'] : array();

		if ( empty( $agents ) || ! is_array( $agents ) ) {
			// Return default agent if configured with a phone.
			$default_phone = get_option( 'fwa_default_phone', '' );
			if ( ! empty( $default_phone ) ) {
				return array(
					array(
						'name'            => get_bloginfo( 'name' ),
						'phone'           => $default_phone,
						'role'            => __( 'Support', 'flexi-whatsapp-automation' ),
						'default_message' => '',
					),
				);
			}
			return array();
		}

		return $agents;
	}

	/**
	 * Get settings safe for frontend output.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings Full settings.
	 * @return array Sanitized subset.
	 */
	private function get_public_settings( $settings ) {
		return array(
			'position'      => sanitize_html_class( $settings['position'] ),
			'display_delay' => absint( $settings['display_delay'] ),
			'analytics'     => $settings['analytics'],
		);
	}
}
