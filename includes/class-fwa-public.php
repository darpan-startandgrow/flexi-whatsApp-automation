<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend / public-facing hooks for Flexi WhatsApp Automation.
 */
class FWA_Public {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue public-facing scripts and styles.
	 *
	 * Currently a placeholder for future frontend features.
	 */
	public function enqueue_scripts() {
		/**
		 * Fires when public scripts are being enqueued.
		 *
		 * Allows extensions to register their own frontend assets.
		 */
		do_action( 'fwa_public_enqueue_scripts' );
	}
}
