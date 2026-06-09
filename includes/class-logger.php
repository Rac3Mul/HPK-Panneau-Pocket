<?php
/**
 * Logger for PanneauPocket API operations.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Logger
 */
class HPK_PP_Logger {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hpk_panneaupocket_logs';
	}

	/**
	 * Create logs table.
	 */
	public static function create_table() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned DEFAULT 0,
			user_id bigint(20) unsigned DEFAULT 0,
			action varchar(20) NOT NULL,
			external_id varchar(191) DEFAULT NULL,
			http_code smallint DEFAULT NULL,
			status varchar(20) DEFAULT NULL,
			api_response longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false
	 */
	public static function log( $data ) {
		global $wpdb;

		$defaults = array(
			'post_id'      => 0,
			'user_id'      => get_current_user_id(),
			'action'       => 'create',
			'external_id'  => '',
			'http_code'    => 0,
			'status'       => 'error',
			'api_response' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( is_array( $data['api_response'] ) ) {
			$data['api_response'] = wp_json_encode( $data['api_response'] );
		}

		$result = $wpdb->insert(
			self::table_name(),
			array(
				'post_id'      => absint( $data['post_id'] ),
				'user_id'      => absint( $data['user_id'] ),
				'action'       => sanitize_text_field( $data['action'] ),
				'external_id'  => sanitize_text_field( $data['external_id'] ),
				'http_code'    => absint( $data['http_code'] ),
				'status'       => sanitize_text_field( $data['status'] ),
				'api_response' => $data['api_response'],
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get logs with pagination.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'post_id'  => 0,
			'action'   => '',
			'status'   => '',
			'per_page' => 20,
			'page'     => 1,
		);

		$args   = wp_parse_args( $args, $defaults );
		$table  = self::table_name();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['post_id'] ) ) {
			$where[]  = 'post_id = %d';
			$values[] = absint( $args['post_id'] );
		}

		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$values[] = sanitize_text_field( $args['action'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}

		$where_sql = implode( ' AND ', $where );
		$per_page  = max( 1, absint( $args['per_page'] ) );
		$page      = max( 1, absint( $args['page'] ) );
		$offset    = ( $page - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = empty( $values )
			? (int) $wpdb->get_var( $count_sql )
			: (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );

		$query_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$query_values = array_merge( $values, array( $per_page, $offset ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_values ) );

		return array(
			'items' => $rows ? $rows : array(),
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Export logs as CSV string.
	 *
	 * @param array $args Query args.
	 * @return string
	 */
	public static function export_csv( $args = array() ) {
		$args['per_page'] = 10000;
		$args['page']     = 1;
		$result           = self::get_logs( $args );

		$output = fopen( 'php://temp', 'r+' );
		fputcsv( $output, array( 'Date', 'Article', 'Action', 'Code HTTP', 'Statut', 'External ID', 'Message API', 'Utilisateur' ) );

		foreach ( $result['items'] as $log ) {
			$post_title = $log->post_id ? get_the_title( $log->post_id ) : '-';
			$user       = $log->user_id ? get_userdata( $log->user_id ) : null;
			$user_name  = $user ? $user->display_name : '-';
			$message    = self::extract_message( $log->api_response );

			fputcsv(
				$output,
				array(
					$log->created_at,
					$post_title,
					$log->action,
					$log->http_code,
					$log->status,
					$log->external_id,
					$message,
					$user_name,
				)
			);
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	/**
	 * Extract message from API response JSON.
	 *
	 * @param string $api_response JSON string.
	 * @return string
	 */
	public static function extract_message( $api_response ) {
		if ( empty( $api_response ) ) {
			return '';
		}

		$data = json_decode( $api_response, true );
		if ( is_array( $data ) && ! empty( $data['message'] ) ) {
			return $data['message'];
		}

		return $api_response;
	}

	/**
	 * Purge old logs.
	 *
	 * @param int $days Retention days.
	 * @return int
	 */
	public static function purge_old( $days = 90 ) {
		global $wpdb;

		$days  = max( 1, absint( $days ) );
		$table = self::table_name();
		$date  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$date
			)
		);
	}

	/**
	 * Cron callback for log purge.
	 */
	public static function purge_old_cron() {
		$days = absint( get_option( 'hpk_pp_log_retention_days', 90 ) );
		self::purge_old( $days );
	}
}
