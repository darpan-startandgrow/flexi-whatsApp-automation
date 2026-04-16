<?php
/**
 * Advanced Phone Field — country-code picker with GeoIP detection.
 *
 * Mirrors the wawp.net "Advanced Phone Field" feature:
 *  - Renders a flag + dial-code picker attached to any tel input that
 *    carries a data-fwa-phone attribute.
 *  - Auto-detects visitor country via GeoIP (ipapi.co) with a
 *    configurable fallback default.
 *  - Supports country allow-list and block-list.
 *  - Integrates with [fwa_otp_login] / [fwa_otp_verify] shortcodes.
 *  - Provides a hook for WooCommerce checkout phone fields.
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_Phone_Field
 *
 * @since 1.2.0
 */
class FWA_Phone_Field {

	/**
	 * Comprehensive country dial-code list.
	 *
	 * Each entry: [ iso2, name, dialCode ]
	 *
	 * @since 1.2.0
	 * @var array
	 */
	private static $countries = array(
		array( 'AF', 'Afghanistan',                   '93' ),
		array( 'AL', 'Albania',                       '355' ),
		array( 'DZ', 'Algeria',                       '213' ),
		array( 'AD', 'Andorra',                       '376' ),
		array( 'AO', 'Angola',                        '244' ),
		array( 'AR', 'Argentina',                     '54' ),
		array( 'AM', 'Armenia',                       '374' ),
		array( 'AU', 'Australia',                     '61' ),
		array( 'AT', 'Austria',                       '43' ),
		array( 'AZ', 'Azerbaijan',                    '994' ),
		array( 'BS', 'Bahamas',                       '1242' ),
		array( 'BH', 'Bahrain',                       '973' ),
		array( 'BD', 'Bangladesh',                    '880' ),
		array( 'BB', 'Barbados',                      '1246' ),
		array( 'BY', 'Belarus',                       '375' ),
		array( 'BE', 'Belgium',                       '32' ),
		array( 'BZ', 'Belize',                        '501' ),
		array( 'BJ', 'Benin',                         '229' ),
		array( 'BT', 'Bhutan',                        '975' ),
		array( 'BO', 'Bolivia',                       '591' ),
		array( 'BA', 'Bosnia and Herzegovina',        '387' ),
		array( 'BW', 'Botswana',                      '267' ),
		array( 'BR', 'Brazil',                        '55' ),
		array( 'BN', 'Brunei',                        '673' ),
		array( 'BG', 'Bulgaria',                      '359' ),
		array( 'BF', 'Burkina Faso',                  '226' ),
		array( 'BI', 'Burundi',                       '257' ),
		array( 'KH', 'Cambodia',                      '855' ),
		array( 'CM', 'Cameroon',                      '237' ),
		array( 'CA', 'Canada',                        '1' ),
		array( 'CV', 'Cape Verde',                    '238' ),
		array( 'CF', 'Central African Republic',      '236' ),
		array( 'TD', 'Chad',                          '235' ),
		array( 'CL', 'Chile',                         '56' ),
		array( 'CN', 'China',                         '86' ),
		array( 'CO', 'Colombia',                      '57' ),
		array( 'KM', 'Comoros',                       '269' ),
		array( 'CG', 'Congo',                         '242' ),
		array( 'CD', 'Congo (DRC)',                   '243' ),
		array( 'CR', 'Costa Rica',                    '506' ),
		array( 'HR', 'Croatia',                       '385' ),
		array( 'CU', 'Cuba',                          '53' ),
		array( 'CY', 'Cyprus',                        '357' ),
		array( 'CZ', 'Czech Republic',                '420' ),
		array( 'DK', 'Denmark',                       '45' ),
		array( 'DJ', 'Djibouti',                      '253' ),
		array( 'DO', 'Dominican Republic',            '1809' ),
		array( 'EC', 'Ecuador',                       '593' ),
		array( 'EG', 'Egypt',                         '20' ),
		array( 'SV', 'El Salvador',                   '503' ),
		array( 'GQ', 'Equatorial Guinea',             '240' ),
		array( 'ER', 'Eritrea',                       '291' ),
		array( 'EE', 'Estonia',                       '372' ),
		array( 'ET', 'Ethiopia',                      '251' ),
		array( 'FJ', 'Fiji',                          '679' ),
		array( 'FI', 'Finland',                       '358' ),
		array( 'FR', 'France',                        '33' ),
		array( 'GA', 'Gabon',                         '241' ),
		array( 'GM', 'Gambia',                        '220' ),
		array( 'GE', 'Georgia',                       '995' ),
		array( 'DE', 'Germany',                       '49' ),
		array( 'GH', 'Ghana',                         '233' ),
		array( 'GR', 'Greece',                        '30' ),
		array( 'GT', 'Guatemala',                     '502' ),
		array( 'GN', 'Guinea',                        '224' ),
		array( 'GW', 'Guinea-Bissau',                 '245' ),
		array( 'GY', 'Guyana',                        '592' ),
		array( 'HT', 'Haiti',                         '509' ),
		array( 'HN', 'Honduras',                      '504' ),
		array( 'HK', 'Hong Kong',                     '852' ),
		array( 'HU', 'Hungary',                       '36' ),
		array( 'IS', 'Iceland',                       '354' ),
		array( 'IN', 'India',                         '91' ),
		array( 'ID', 'Indonesia',                     '62' ),
		array( 'IR', 'Iran',                          '98' ),
		array( 'IQ', 'Iraq',                          '964' ),
		array( 'IE', 'Ireland',                       '353' ),
		array( 'IL', 'Israel',                        '972' ),
		array( 'IT', 'Italy',                         '39' ),
		array( 'JM', 'Jamaica',                       '1876' ),
		array( 'JP', 'Japan',                         '81' ),
		array( 'JO', 'Jordan',                        '962' ),
		array( 'KZ', 'Kazakhstan',                    '7' ),
		array( 'KE', 'Kenya',                         '254' ),
		array( 'KW', 'Kuwait',                        '965' ),
		array( 'KG', 'Kyrgyzstan',                    '996' ),
		array( 'LA', 'Laos',                          '856' ),
		array( 'LV', 'Latvia',                        '371' ),
		array( 'LB', 'Lebanon',                       '961' ),
		array( 'LS', 'Lesotho',                       '266' ),
		array( 'LR', 'Liberia',                       '231' ),
		array( 'LY', 'Libya',                         '218' ),
		array( 'LI', 'Liechtenstein',                 '423' ),
		array( 'LT', 'Lithuania',                     '370' ),
		array( 'LU', 'Luxembourg',                    '352' ),
		array( 'MO', 'Macau',                         '853' ),
		array( 'MK', 'North Macedonia',               '389' ),
		array( 'MG', 'Madagascar',                    '261' ),
		array( 'MW', 'Malawi',                        '265' ),
		array( 'MY', 'Malaysia',                      '60' ),
		array( 'MV', 'Maldives',                      '960' ),
		array( 'ML', 'Mali',                          '223' ),
		array( 'MT', 'Malta',                         '356' ),
		array( 'MR', 'Mauritania',                    '222' ),
		array( 'MU', 'Mauritius',                     '230' ),
		array( 'MX', 'Mexico',                        '52' ),
		array( 'MD', 'Moldova',                       '373' ),
		array( 'MC', 'Monaco',                        '377' ),
		array( 'MN', 'Mongolia',                      '976' ),
		array( 'ME', 'Montenegro',                    '382' ),
		array( 'MA', 'Morocco',                       '212' ),
		array( 'MZ', 'Mozambique',                    '258' ),
		array( 'MM', 'Myanmar',                       '95' ),
		array( 'NA', 'Namibia',                       '264' ),
		array( 'NP', 'Nepal',                         '977' ),
		array( 'NL', 'Netherlands',                   '31' ),
		array( 'NZ', 'New Zealand',                   '64' ),
		array( 'NI', 'Nicaragua',                     '505' ),
		array( 'NE', 'Niger',                         '227' ),
		array( 'NG', 'Nigeria',                       '234' ),
		array( 'NO', 'Norway',                        '47' ),
		array( 'OM', 'Oman',                          '968' ),
		array( 'PK', 'Pakistan',                      '92' ),
		array( 'PA', 'Panama',                        '507' ),
		array( 'PG', 'Papua New Guinea',              '675' ),
		array( 'PY', 'Paraguay',                      '595' ),
		array( 'PE', 'Peru',                          '51' ),
		array( 'PH', 'Philippines',                   '63' ),
		array( 'PL', 'Poland',                        '48' ),
		array( 'PT', 'Portugal',                      '351' ),
		array( 'QA', 'Qatar',                         '974' ),
		array( 'RO', 'Romania',                       '40' ),
		array( 'RU', 'Russia',                        '7' ),
		array( 'RW', 'Rwanda',                        '250' ),
		array( 'SA', 'Saudi Arabia',                  '966' ),
		array( 'SN', 'Senegal',                       '221' ),
		array( 'RS', 'Serbia',                        '381' ),
		array( 'SL', 'Sierra Leone',                  '232' ),
		array( 'SG', 'Singapore',                     '65' ),
		array( 'SK', 'Slovakia',                      '421' ),
		array( 'SI', 'Slovenia',                      '386' ),
		array( 'SO', 'Somalia',                       '252' ),
		array( 'ZA', 'South Africa',                  '27' ),
		array( 'SS', 'South Sudan',                   '211' ),
		array( 'ES', 'Spain',                         '34' ),
		array( 'LK', 'Sri Lanka',                     '94' ),
		array( 'SD', 'Sudan',                         '249' ),
		array( 'SR', 'Suriname',                      '597' ),
		array( 'SZ', 'Swaziland',                     '268' ),
		array( 'SE', 'Sweden',                        '46' ),
		array( 'CH', 'Switzerland',                   '41' ),
		array( 'SY', 'Syria',                         '963' ),
		array( 'TW', 'Taiwan',                        '886' ),
		array( 'TJ', 'Tajikistan',                    '992' ),
		array( 'TZ', 'Tanzania',                      '255' ),
		array( 'TH', 'Thailand',                      '66' ),
		array( 'TG', 'Togo',                          '228' ),
		array( 'TT', 'Trinidad and Tobago',           '1868' ),
		array( 'TN', 'Tunisia',                       '216' ),
		array( 'TR', 'Turkey',                        '90' ),
		array( 'TM', 'Turkmenistan',                  '993' ),
		array( 'UG', 'Uganda',                        '256' ),
		array( 'UA', 'Ukraine',                       '380' ),
		array( 'AE', 'United Arab Emirates',          '971' ),
		array( 'GB', 'United Kingdom',                '44' ),
		array( 'US', 'United States',                 '1' ),
		array( 'UY', 'Uruguay',                       '598' ),
		array( 'UZ', 'Uzbekistan',                    '998' ),
		array( 'VE', 'Venezuela',                     '58' ),
		array( 'VN', 'Vietnam',                       '84' ),
		array( 'YE', 'Yemen',                         '967' ),
		array( 'ZM', 'Zambia',                        '260' ),
		array( 'ZW', 'Zimbabwe',                      '263' ),
	);

	/**
	 * Constructor – registers hooks.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'fwa_otp_public_js_data', array( $this, 'add_phone_field_data' ) );

		// WooCommerce checkout integration.
		if ( function_exists( 'is_checkout' ) ) {
			add_action( 'wp_footer', array( $this, 'maybe_init_wc_checkout' ), 20 );
		}
	}

	/**
	 * Enqueue phone field CSS and JS.
	 *
	 * @since 1.2.0
	 */
	public function enqueue_assets() {
		$enabled = get_option( 'fwa_phone_field_enabled', 'yes' );
		if ( 'yes' !== $enabled ) {
			return;
		}

		wp_register_style(
			'fwa-phone-field',
			FWA_PLUGIN_URL . 'public/css/fwa-phone-field.css',
			array(),
			FWA_VERSION
		);

		wp_register_script(
			'fwa-phone-field',
			FWA_PLUGIN_URL . 'public/js/fwa-phone-field.js',
			array( 'jquery' ),
			FWA_VERSION,
			true
		);

		wp_localize_script(
			'fwa-phone-field',
			'fwa_phone_field',
			$this->get_js_data()
		);

		// Auto-load on pages with OTP shortcodes or WooCommerce checkout.
		if ( is_checkout() || has_shortcode( get_post()->post_content ?? '', 'fwa_otp_login' ) ) {
			wp_enqueue_style( 'fwa-phone-field' );
			wp_enqueue_script( 'fwa-phone-field' );
		}
	}

	/**
	 * Build the localized JS data array.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public function get_js_data() {
		$countries_out = array();
		foreach ( self::$countries as $c ) {
			$countries_out[] = array(
				'iso2'     => $c[0],
				'name'     => $c[1],
				'dialCode' => $c[2],
			);
		}

		return array(
			'countries'        => $countries_out,
			'default_country'  => strtoupper( get_option( 'fwa_phone_field_default_country', 'US' ) ),
			'geoip_country'    => $this->detect_geoip_country(),
			'allow_countries'  => get_option( 'fwa_phone_field_allow_countries', '' ),
			'block_countries'  => get_option( 'fwa_phone_field_block_countries', '' ),
			'geoip_enabled'    => get_option( 'fwa_phone_field_geoip', 'yes' ),
			'strings'          => array(
				'invalid_phone' => __( 'Please enter a valid phone number including country code.', 'flexi-whatsapp-automation' ),
				'search'        => __( 'Search country…', 'flexi-whatsapp-automation' ),
			),
		);
	}

	/**
	 * Detect visitor country via GeoIP (ipapi.co — free tier).
	 *
	 * Results are cached in a transient keyed by IP to avoid repeated requests.
	 *
	 * @since 1.2.0
	 *
	 * @return string ISO2 country code or empty string.
	 */
	public function detect_geoip_country() {
		if ( 'yes' !== get_option( 'fwa_phone_field_geoip', 'yes' ) ) {
			return '';
		}

		$ip = FWA_Helpers::get_client_ip();
		if ( empty( $ip ) || '127.0.0.1' === $ip || '::1' === $ip ) {
			return '';
		}

		$key     = 'fwa_geoip_' . md5( $ip );
		$cached  = get_transient( $key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$response = wp_remote_get(
			'https://ipapi.co/' . rawurlencode( $ip ) . '/country/',
			array(
				'timeout'   => 3,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = strtoupper( trim( wp_remote_retrieve_body( $response ) ) );

		// Validate: 2-letter uppercase.
		if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
			set_transient( $key, $code, DAY_IN_SECONDS );
			return $code;
		}

		return '';
	}

	/**
	 * Add phone field data to the OTP JS localization.
	 *
	 * @since 1.2.0
	 *
	 * @param array $data Existing OTP JS data.
	 * @return array
	 */
	public function add_phone_field_data( $data ) {
		$data['phone_field'] = $this->get_js_data();
		return $data;
	}

	/**
	 * Output a small inline script to activate the picker on the WC checkout
	 * billing phone field after the page has loaded.
	 *
	 * @since 1.2.0
	 */
	public function maybe_init_wc_checkout() {
		if ( ! is_checkout() ) {
			return;
		}

		if ( 'yes' !== get_option( 'fwa_phone_field_wc_checkout', 'yes' ) ) {
			return;
		}

		wp_enqueue_style( 'fwa-phone-field' );
		wp_enqueue_script( 'fwa-phone-field' );

		$field_selector = esc_js( get_option( 'fwa_phone_field_wc_selector', '#billing_phone' ) );
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			var $field = $('<?php echo $field_selector; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>');
			if ($field.length && typeof fwaPhoneField !== 'undefined') {
				fwaPhoneField.attach($field);
			}
		});
		</script>
		<?php
	}

	/**
	 * Return the full country list (for use by admin UI).
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public static function get_countries() {
		$out = array();
		foreach ( self::$countries as $c ) {
			$out[ $c[0] ] = $c[1] . ' (+' . $c[2] . ')';
		}
		return $out;
	}
}
