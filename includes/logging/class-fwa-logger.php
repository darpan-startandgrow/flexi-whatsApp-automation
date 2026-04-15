<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central logging system for Flexi WhatsApp Automation.
 *
 * Stores logs in the database with optional file fallback.
 */
class FWA_Logger {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether file logging is enabled.
	 *
	 * @var bool
	 */
	private $file_logging;

	/**
	 * Minimum log level.
	 *
	 * @var string
	 */
	private $log_level;

	/**
	 * Path to the current log file.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Ordered log levels.
	 *
	 * @var array
	 */
	private static $log_levels = array(
		'debug'    => 0,
		'info'     => 1,
		'warning'  => 2,
		'error'    => 3,
		'critical' => 4,
	);

	/**
	 * Private constructor – use get_instance().
	 */
	private function __construct() {
		$this->file_logging = (bool) get_option( 'fwa_file_logging', false );
		$this->log_level    = get_option( 'fwa_log_level', 'info' );
		$this->log_file     = $this->get_log_file_path();

		if ( $this->file_logging ) {
			$this->maybe_create_log_directory();
		}
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Log a message.
	 *
	 * @param string      $level       Log level.
	 * @param string      $message     Log message.
	 * @param array       $context     Additional context data.
	 * @param string      $source      Source identifier.
	 * @param int|null    $instance_id Related instance ID.
	 */
	public function log( $level, $message, $context = array(), $source = '', $instance_id = null ) {
		if ( ! isset( self::$log_levels[ $level ] ) ) {
			return;
		}

		if ( self::$log_levels[ $level ] < self::$log_levels[ $this->log_level ] ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'fwa_logs';

		$ip_address = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$context_json = wp_json_encode( $context );

		$wpdb->insert(
			$table,
			array(
				'level'       => $level,
				'message'     => $message,
				'context'     => $context_json,
				'source'      => $source,
				'instance_id' => $instance_id,
				'ip_address'  => $ip_address,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( $this->file_logging ) {
			$this->write_to_file( $level, $message, $context, $source );
		}

		/**
		 * Fires when a log entry is written.
		 *
		 * @param string $level   Log level.
		 * @param string $message Log message.
		 * @param array  $context Additional context.
		 * @param string $source  Source identifier.
		 */
		do_action( 'fwa_log_entry', $level, $message, $context, $source );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function debug( $message, $context = array(), $source = '' ) {
		$this->log( 'debug', $message, $context, $source );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function info( $message, $context = array(), $source = '' ) {
		$this->log( 'info', $message, $context, $source );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function warning( $message, $context = array(), $source = '' ) {
		$this->log( 'warning', $message, $context, $source );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function error( $message, $context = array(), $source = '' ) {
		$this->log( 'error', $message, $context, $source );
	}

	/**
	 * Log a critical message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function critical( $message, $context = array(), $source = '' ) {
		$this->log( 'critical', $message, $context, $source );
	}

	/**
	 * Retrieve logs with filtering and pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array {
	 *     @type array $logs  Array of log objects.
	 *     @type int   $total Total matching entries.
	 * }
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fwa_logs';

		$defaults = array(
			'level'       => '',
			'source'      => '',
			'instance_id' => null,
			'search'      => '',
			'date_from'   => '',
			'date_to'     => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'limit'       => 20,
			'offset'      => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['level'] ) ) {
			$where[]  = 'level = %s';
			$values[] = $args['level'];
		}

		if ( ! empty( $args['source'] ) ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}

		if ( null !== $args['instance_id'] ) {
			$where[]  = 'instance_id = %d';
			$values[] = $args['instance_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'message LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = $args['date_to'];
		}

		$where_clause = implode( ' AND ', $where );

		$allowed_orderby = array( 'id', 'level', 'source', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Results.
		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$query_values   = array_merge( $values, array( (int) $args['limit'], (int) $args['offset'] ) );
		$prepared       = $wpdb->prepare( $sql, $query_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$logs           = $wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'logs'  => $logs,
			'total' => $total,
		);
	}

	/**
	 * Get a single log entry.
	 *
	 * @param int $id Log entry ID.
	 * @return object|null
	 */
	public function get_log( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fwa_logs';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
		);
	}

	/**
	 * Delete logs matching given criteria.
	 *
	 * @param array $args Deletion criteria.
	 * @return int Number of deleted rows.
	 */
	public function delete_logs( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fwa_logs';

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['older_than'] ) ) {
			$where[]  = 'created_at < %s';
			$values[] = $args['older_than'];
		}

		if ( ! empty( $args['level'] ) ) {
			$where[]  = 'level = %s';
			$values[] = $args['level'];
		}

		if ( ! empty( $args['source'] ) ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}

		$where_clause = implode( ' AND ', $where );

		$sql = "DELETE FROM {$table} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Clean up old logs.
	 *
	 * Intended to be called by the daily cron event 'fwa_cleanup_logs'.
	 *
	 * @param int $days Number of days to retain. Default 30.
	 */
	public function cleanup( $days = 30 ) {
		$older_than = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$deleted_count = $this->delete_logs( array( 'older_than' => $older_than ) );

		// Clean up old log files.
		if ( $this->file_logging ) {
			$upload_dir = wp_upload_dir();
			$log_dir    = $upload_dir['basedir'] . '/fwa-logs';

			if ( is_dir( $log_dir ) ) {
				$files = glob( $log_dir . '/fwa-*.log' );
				if ( is_array( $files ) ) {
					$cutoff = strtotime( "-{$days} days" );
					foreach ( $files as $file ) {
						if ( filemtime( $file ) < $cutoff ) {
							wp_delete_file( $file );
						}
					}
				}
			}
		}

		/**
		 * Fires after old logs have been cleaned up.
		 *
		 * @param int $deleted_count Number of database rows deleted.
		 * @param int $days          Retention period in days.
		 */
		do_action( 'fwa_logs_cleaned', $deleted_count, $days );
	}

	/**
	 * Export logs matching criteria as CSV.
	 *
	 * @param array  $args   Query arguments (same as get_logs).
	 * @param string $format Export format. Currently only 'csv'.
	 * @return string CSV content.
	 */
	public function export_logs( $args = array(), $format = 'csv' ) {
		$args['limit'] = isset( $args['limit'] ) ? $args['limit'] : 10000;
		$result        = $this->get_logs( $args );
		$logs          = $result['logs'];

		/**
		 * Filters the columns included in log exports.
		 *
		 * @param array $columns Column headers.
		 */
		$columns = apply_filters( 'fwa_export_log_columns', array(
			'Date',
			'Level',
			'Source',
			'Message',
			'Instance ID',
			'IP',
		) );

		$output = fopen( 'php://memory', 'r+' );
		fputcsv( $output, $columns );

		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->created_at,
				$log->level,
				$log->source,
				$log->message,
				$log->instance_id,
				$log->ip_address,
			) );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	/**
	 * Get aggregate log statistics.
	 *
	 * @return array
	 */
	public function get_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'fwa_logs';

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$by_level = array();
		foreach ( array_keys( self::$log_levels ) as $level ) {
			$by_level[ $level ] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE level = %s", $level )
			);
		}

		$today       = current_time( 'Y-m-d' );
		$today_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
				$today . ' 00:00:00'
			)
		);

		$sources = $wpdb->get_col( "SELECT DISTINCT source FROM {$table} WHERE source != '' ORDER BY source ASC" );

		return array(
			'total'       => $total,
			'by_level'    => $by_level,
			'today_count' => $today_count,
			'sources'     => $sources,
		);
	}

	/**
	 * Get the log file path for a given date.
	 *
	 * @param string $date Date string (Y-m-d). Defaults to today.
	 * @return string Full file path.
	 */
	public function get_log_file_path( $date = '' ) {
		if ( empty( $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/fwa-logs';

		$this->maybe_create_log_directory();

		return $log_dir . '/fwa-' . $date . '.log';
	}

	/**
	 * Write a formatted log line to the file.
	 *
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @param string $source  Source identifier.
	 */
	public function write_to_file( $level, $message, $context, $source ) {
		$this->log_file = $this->get_log_file_path();

		$timestamp    = current_time( 'Y-m-d H:i:s' );
		$level_upper  = strtoupper( $level );
		$context_json = ! empty( $context ) ? wp_json_encode( $context ) : '';

		$line = sprintf(
			'[%s] [%s] [%s] %s',
			$timestamp,
			$level_upper,
			$source,
			$message
		);

		if ( ! empty( $context_json ) ) {
			$line .= ' | ' . $context_json;
		}

		$line .= PHP_EOL;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->log_file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Create the log directory with a protective .htaccess file.
	 */
	private function maybe_create_log_directory() {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/fwa-logs';

		if ( ! is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir );

			$htaccess = $log_dir . '/.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $htaccess, 'deny from all' );
			}
		}
	}
}
