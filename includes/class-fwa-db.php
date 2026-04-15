<?php
/**
 * Database installer.
 *
 * Creates all plugin tables using dbDelta().
 *
 * @package Flexi_WhatsApp_Automation
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FWA_DB
 *
 * Handles creation and upgrades of the seven plugin database tables.
 *
 * @since 1.0.0
 */
class FWA_DB {

	/**
	 * Create all plugin tables.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		$sql = self::get_instances_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_messages_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_contacts_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_campaigns_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_campaign_logs_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_schedules_schema( $prefix, $charset_collate );
		dbDelta( $sql );

		$sql = self::get_logs_schema( $prefix, $charset_collate );
		dbDelta( $sql );
	}

	/*--------------------------------------------------------------
	 * Individual table schemas
	 *------------------------------------------------------------*/

	/**
	 * Instances table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_instances_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_instances (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL,
			instance_id VARCHAR(100) NOT NULL,
			access_token VARCHAR(255) DEFAULT '',
			phone_number VARCHAR(20) DEFAULT '',
			status ENUM('connected','disconnected','qr_required','initializing','banned') DEFAULT 'disconnected',
			webhook_url VARCHAR(500) DEFAULT '',
			meta LONGTEXT,
			is_active TINYINT(1) DEFAULT 0,
			last_seen_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY instance_id (instance_id),
			KEY status (status),
			KEY is_active (is_active)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Messages table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_messages_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_messages (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			instance_id BIGINT(20) UNSIGNED NOT NULL,
			direction ENUM('outgoing','incoming') DEFAULT 'outgoing',
			message_type ENUM('text','image','video','audio','document','contact','poll','link_preview') DEFAULT 'text',
			recipient VARCHAR(20) NOT NULL,
			sender VARCHAR(20) DEFAULT '',
			content LONGTEXT,
			media_url VARCHAR(500) DEFAULT '',
			media_caption VARCHAR(500) DEFAULT '',
			message_id VARCHAR(100) DEFAULT '',
			status ENUM('pending','queued','sent','delivered','read','failed') DEFAULT 'pending',
			error_message TEXT DEFAULT NULL,
			campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
			schedule_id BIGINT(20) UNSIGNED DEFAULT NULL,
			retry_count INT(3) DEFAULT 0,
			sent_at DATETIME DEFAULT NULL,
			delivered_at DATETIME DEFAULT NULL,
			read_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY instance_id (instance_id),
			KEY status (status),
			KEY recipient (recipient),
			KEY campaign_id (campaign_id),
			KEY direction (direction),
			KEY created_at (created_at)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Contacts table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_contacts_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_contacts (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			instance_id BIGINT(20) UNSIGNED DEFAULT NULL,
			phone VARCHAR(20) NOT NULL,
			name VARCHAR(200) DEFAULT '',
			email VARCHAR(200) DEFAULT '',
			wp_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			tags VARCHAR(500) DEFAULT '',
			notes TEXT DEFAULT NULL,
			is_subscribed TINYINT(1) DEFAULT 1,
			meta LONGTEXT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY phone_instance (phone, instance_id),
			KEY wp_user_id (wp_user_id),
			KEY is_subscribed (is_subscribed)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Campaigns table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_campaigns_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_campaigns (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			instance_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(200) NOT NULL,
			message_type ENUM('text','image','video','audio','document') DEFAULT 'text',
			content LONGTEXT NOT NULL,
			media_url VARCHAR(500) DEFAULT '',
			media_caption VARCHAR(500) DEFAULT '',
			recipients_filter LONGTEXT,
			total_recipients INT(11) DEFAULT 0,
			sent_count INT(11) DEFAULT 0,
			delivered_count INT(11) DEFAULT 0,
			read_count INT(11) DEFAULT 0,
			failed_count INT(11) DEFAULT 0,
			status ENUM('draft','scheduled','running','paused','completed','failed') DEFAULT 'draft',
			scheduled_at DATETIME DEFAULT NULL,
			started_at DATETIME DEFAULT NULL,
			completed_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY instance_id (instance_id),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Campaign logs table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_campaign_logs_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_campaign_logs (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT(20) UNSIGNED NOT NULL,
			contact_id BIGINT(20) UNSIGNED DEFAULT NULL,
			phone VARCHAR(20) NOT NULL,
			message_id BIGINT(20) UNSIGNED DEFAULT NULL,
			status ENUM('pending','sent','delivered','read','failed') DEFAULT 'pending',
			error_message TEXT DEFAULT NULL,
			sent_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id),
			KEY status (status),
			KEY contact_id (contact_id)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Schedules table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_schedules_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_schedules (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			instance_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(200) DEFAULT '',
			type ENUM('one_time','recurring') DEFAULT 'one_time',
			message_type ENUM('text','image','video','audio','document') DEFAULT 'text',
			content LONGTEXT NOT NULL,
			media_url VARCHAR(500) DEFAULT '',
			recipient VARCHAR(20) NOT NULL,
			recurrence_rule VARCHAR(100) DEFAULT '',
			status ENUM('active','paused','completed','failed') DEFAULT 'active',
			next_run_at DATETIME DEFAULT NULL,
			last_run_at DATETIME DEFAULT NULL,
			run_count INT(11) DEFAULT 0,
			max_runs INT(11) DEFAULT 0,
			retry_count INT(3) DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY instance_id (instance_id),
			KEY status (status),
			KEY next_run_at (next_run_at),
			KEY type (type)
		) ENGINE=InnoDB {$charset_collate};";
	}

	/**
	 * Logs table schema.
	 *
	 * @param string $prefix          Table prefix.
	 * @param string $charset_collate Charset/collation string.
	 * @return string SQL statement.
	 */
	private static function get_logs_schema( $prefix, $charset_collate ) {
		return "CREATE TABLE {$prefix}fwa_logs (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			level ENUM('debug','info','warning','error','critical') DEFAULT 'info',
			source VARCHAR(100) DEFAULT '',
			message TEXT NOT NULL,
			context LONGTEXT DEFAULT NULL,
			instance_id BIGINT(20) UNSIGNED DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY source (source),
			KEY instance_id (instance_id),
			KEY created_at (created_at)
		) ENGINE=InnoDB {$charset_collate};";
	}
}
