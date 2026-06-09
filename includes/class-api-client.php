<?php
/**
 * PanneauPocket API client.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Api_Client
 */
class HPK_PP_Api_Client {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Api_Client|null
	 */
	private static $instance = null;

	/**
	 * Environment URLs.
	 *
	 * @var array
	 */
	private static $environments = array(
		'production' => 'https://gestion.panneaupocket.com',
		'staging'    => 'https://staging.gestion.panneaupocket.com',
	);

	/**
	 * Embed iframe base URLs (public app, not API gestion).
	 *
	 * @var array
	 */
	private static $embed_environments = array(
		'production' => 'https://app.panneaupocket.com',
		'staging'    => 'https://app.panneaupocket.com',
	);

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Api_Client
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get API base URL.
	 *
	 * @return string
	 */
	public function get_base_url() {
		$custom = trim( get_option( 'hpk_pp_api_url', '' ) );
		if ( ! empty( $custom ) ) {
			return untrailingslashit( $custom );
		}

		$env = get_option( 'hpk_pp_environment', 'production' );
		return self::$environments[ $env ] ?? self::$environments['production'];
	}

	/**
	 * Default embed base URL (public app — not the API gestion domain).
	 */
	const EMBED_URL_DEFAULT = 'https://app.panneaupocket.com';

	/**
	 * Get embed iframe base URL (app.panneaupocket.com).
	 *
	 * @return string
	 */
	public function get_embed_base_url() {
		$custom = trim( get_option( 'hpk_pp_embed_url', '' ) );

		if ( ! empty( $custom ) ) {
			$custom = untrailingslashit( $custom );
			// L'iframe ne doit jamais utiliser le domaine API gestion.* (404 garanti).
			if ( false !== strpos( $custom, 'gestion.panneaupocket.com' ) ) {
				return self::EMBED_URL_DEFAULT;
			}
			return $custom;
		}

		return self::EMBED_URL_DEFAULT;
	}

	/**
	 * Encrypt token for storage.
	 *
	 * @param string $token Plain token.
	 * @return string
	 */
	public static function encrypt_token( $token ) {
		if ( empty( $token ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$iv  = openssl_random_pseudo_bytes( 16 );
		$enc = openssl_encrypt( $token, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $enc ) {
			return base64_encode( $token );
		}

		return base64_encode( $iv . $enc );
	}

	/**
	 * Decrypt stored token.
	 *
	 * @param string $encrypted Encrypted token.
	 * @return string
	 */
	public static function decrypt_token( $encrypted ) {
		if ( empty( $encrypted ) ) {
			return '';
		}

		$key  = self::get_encryption_key();
		$data = base64_decode( $encrypted, true );

		if ( false === $data || strlen( $data ) < 17 ) {
			return base64_decode( $encrypted ) ?: '';
		}

		$iv  = substr( $data, 0, 16 );
		$enc = substr( $data, 16 );
		$dec = openssl_decrypt( $enc, 'AES-256-CBC', $key, 0, $iv );

		return false !== $dec ? $dec : '';
	}

	/**
	 * Get encryption key from WP salts.
	 *
	 * @return string
	 */
	private static function get_encryption_key() {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'hpk-pp-default-key';
		$salt .= defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : 'hpk-pp-secure-key';
		return hash( 'sha256', $salt, true );
	}

	/**
	 * Get decrypted API token.
	 *
	 * @return string
	 */
	public function get_token() {
		$stored = get_option( 'hpk_pp_api_token', '' );
		return self::decrypt_token( $stored );
	}

	/**
	 * Save encrypted token.
	 *
	 * @param string $token Plain token.
	 */
	public function save_token( $token ) {
		if ( empty( $token ) ) {
			return;
		}
		update_option( 'hpk_pp_api_token', self::encrypt_token( $token ) );
	}

	/**
	 * Test API connection.
	 *
	 * @return array
	 */
	public function test_connection() {
		$response = $this->request( 'GET', '/public-api/entities/favorities/count' );

		HPK_PP_Logger::log(
			array(
				'action'       => 'test',
				'external_id'  => '',
				'http_code'    => $response['http_code'],
				'status'       => $response['success'] ? 'success' : 'error',
				'api_response' => $response['body'],
			)
		);

		if ( $response['success'] ) {
			update_option( 'hpk_pp_last_test_success', current_time( 'mysql' ) );
			delete_option( 'hpk_pp_last_test_error' );
		} else {
			update_option( 'hpk_pp_last_test_error', $response['message'] );
		}

		return $response;
	}

	/**
	 * Create a sign.
	 *
	 * @param array $payload Sign payload with id.
	 * @return array
	 */
	public function create_sign( $payload ) {
		return $this->request( 'POST', '/public-api/signs', $payload );
	}

	/**
	 * Update a sign.
	 *
	 * @param string $external_id External ID.
	 * @param array  $payload Sign payload.
	 * @return array
	 */
	public function update_sign( $external_id, $payload ) {
		$external_id = rawurlencode( $external_id );
		return $this->request( 'PUT', '/public-api/signs/' . $external_id, $payload );
	}

	/**
	 * Build embed iframe URL.
	 *
	 * @param array $args Embed args.
	 * @return string
	 */
	public function get_embed_url( $args = array() ) {
		$defaults = array(
			'city_id'         => get_option( 'hpk_pp_city_id', '' ),
			'mode'            => 'widget',
			'auto_navigation' => 0,
			'bg_color'        => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$city_id = sanitize_text_field( $args['city_id'] );
		if ( empty( $city_id ) ) {
			return '';
		}

		$mode = in_array( $args['mode'], array( 'widget', 'widgetTv' ), true ) ? $args['mode'] : 'widget';
		$auto = absint( $args['auto_navigation'] );

		$params = array(
			'mode'           => $mode,
			'autoNavigation' => $auto,
		);

		if ( 'widgetTv' === $mode && ! empty( $args['bg_color'] ) ) {
			$params['bgColor'] = preg_replace( '/[^a-fA-F0-9]/', '', $args['bg_color'] );
		}

		return add_query_arg( $params, $this->get_embed_base_url() . '/embeded/' . rawurlencode( $city_id ) );
	}

	/**
	 * Perform HTTP request.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $endpoint API endpoint.
	 * @param array|null $body Request body.
	 * @return array
	 */
	private function request( $method, $endpoint, $body = null ) {
		$token = $this->get_token();
		if ( empty( $token ) ) {
			return $this->normalize_response(
				array(
					'response' => array( 'code' => 401 ),
					'body'     => wp_json_encode( array( 'message' => 'Token API manquant.' ) ),
				),
				401
			);
		}

		$url = $this->get_base_url() . $endpoint;

		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success'    => false,
				'http_code'  => 0,
				'body'       => array( 'message' => $response->get_error_message() ),
				'message'    => $response->get_error_message(),
				'rate_limit' => array(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		return $this->normalize_response( $response, $code );
	}

	/**
	 * Normalize API response.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @param int            $code HTTP code.
	 * @return array
	 */
	private function normalize_response( $response, $code ) {
		$body_raw = wp_remote_retrieve_body( $response );
		$body     = json_decode( $body_raw, true );
		if ( ! is_array( $body ) ) {
			$body = array( 'message' => $body_raw );
		}

		$rate_limit = array(
			'remaining'   => wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' ),
			'retry_after' => wp_remote_retrieve_header( $response, 'x-ratelimit-retry-after' ),
			'limit'       => wp_remote_retrieve_header( $response, 'x-ratelimit-limit' ),
		);

		$success = in_array( $code, array( 200, 201, 204 ), true );
		$message = $this->map_message( $code, $body, $rate_limit );

		return array(
			'success'    => $success,
			'http_code'  => (int) $code,
			'body'       => $body,
			'message'    => $message,
			'rate_limit' => $rate_limit,
		);
	}

	/**
	 * Map HTTP code to French message.
	 *
	 * @param int   $code HTTP code.
	 * @param array $body Response body.
	 * @param array $rate_limit Rate limit headers.
	 * @return string
	 */
	private function map_message( $code, $body, $rate_limit ) {
		$api_msg = $body['message'] ?? '';

		switch ( $code ) {
			case 201:
				return __( 'Publication envoyée avec succès.', 'hpk-panneaupocket' );
			case 204:
				return __( 'Publication mise à jour avec succès.', 'hpk-panneaupocket' );
			case 200:
				return __( 'Connexion API réussie.', 'hpk-panneaupocket' );
			case 401:
				return __( 'Erreur : token API invalide ou expiré.', 'hpk-panneaupocket' );
			case 404:
				return __( 'Erreur : publication introuvable sur PanneauPocket.', 'hpk-panneaupocket' );
			case 422:
				if ( stripos( $api_msg, 'title' ) !== false || stripos( $api_msg, '50' ) !== false ) {
					return __( 'Erreur : titre trop long.', 'hpk-panneaupocket' );
				}
				return $api_msg ?: __( 'Erreur : données invalides.', 'hpk-panneaupocket' );
			case 429:
				$retry = ! empty( $rate_limit['retry_after'] ) ? $rate_limit['retry_after'] : '';
				return $retry
					? sprintf(
						/* translators: %s: retry timestamp */
						__( 'Erreur : limite de requêtes atteinte. Réessayer après %s.', 'hpk-panneaupocket' ),
						$retry
					)
					: __( 'Erreur : limite de requêtes atteinte. Réessayer plus tard.', 'hpk-panneaupocket' );
			default:
				return $api_msg ?: __( 'Erreur inconnue lors de l\'appel API.', 'hpk-panneaupocket' );
		}
	}
}
