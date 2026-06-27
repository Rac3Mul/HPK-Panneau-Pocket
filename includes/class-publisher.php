<?php
/**
 * Publisher — sends WordPress content to PanneauPocket API.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Publisher
 */
class HPK_PP_Publisher {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Publisher|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Publisher
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'save_post', array( $this, 'maybe_auto_sync' ), 20, 3 );
		add_action( 'wp_ajax_hpk_pp_send_now', array( $this, 'ajax_send_now' ) );
		add_action( 'wp_ajax_hpk_pp_update_now', array( $this, 'ajax_update_now' ) );
		add_action( 'wp_ajax_hpk_pp_preview_payload', array( $this, 'ajax_preview_payload' ) );
	}

	/**
	 * Check publish capability.
	 *
	 * @return bool
	 */
	public static function can_publish() {
		$cap = apply_filters( 'hpk_pp_publish_capability', 'publish_posts' );
		return current_user_can( $cap ) || current_user_can( 'hpk_pp_publish_signs' );
	}

	/**
	 * Auto sync on post save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Is update.
	 */
	public function maybe_auto_sync( $post_id, $post, $update ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		$enabled = get_post_meta( $post_id, '_panneaupocket_enabled', true );
		if ( '1' !== $enabled && 1 !== $enabled ) {
			return;
		}

		$draft_mode = get_post_meta( $post_id, '_panneaupocket_draft_mode', true );
		if ( '1' === $draft_mode || 1 === $draft_mode ) {
			return;
		}

		$auto_send   = get_option( 'hpk_pp_auto_send_on_publish', '0' );
		$auto_update = get_option( 'hpk_pp_auto_update_on_save', '1' );

		$last_code = (int) get_post_meta( $post_id, '_panneaupocket_last_http_code', true );
		$is_sent   = in_array( $last_code, array( 201, 204 ), true );

		if ( ! $update && 'publish' === $post->post_status && '1' === $auto_send ) {
			$this->publish_post( $post_id, $is_sent ? 'update' : 'create' );
			return;
		}

		if ( $update && '1' === $auto_update && $is_sent ) {
			$this->publish_post( $post_id, 'update' );
		}
	}

	/**
	 * Supported post types for sync.
	 *
	 * @return array
	 */
	public function get_supported_post_types() {
		return apply_filters( 'hpk_pp_supported_post_types', array( 'post', 'hpk_pp_sign' ) );
	}

	/**
	 * Publish or update a post on PanneauPocket.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action create|update|auto.
	 * @return array
	 */
	public function publish_post( $post_id, $action = 'auto' ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'success' => false,
				'message' => __( 'Article introuvable.', 'hpk-panneaupocket' ),
			);
		}

		$data    = $this->collect_post_data( $post_id );
		$payload = HPK_PP_Sanitizer::build_payload( $data );

		if ( is_wp_error( $payload ) ) {
			$this->update_post_status( $post_id, 'error', 0, $payload->get_error_message() );
			return array(
				'success' => false,
				'message' => $payload->get_error_message(),
			);
		}

		$external_id = HPK_PP_Sanitizer::get_external_id( $post_id, $post->post_type );
		update_post_meta( $post_id, '_panneaupocket_external_id', $external_id );

		$api     = HPK_PP_Api_Client::instance();
		$last_code = (int) get_post_meta( $post_id, '_panneaupocket_last_http_code', true );
		$is_sent   = in_array( $last_code, array( 201, 204 ), true );

		if ( 'auto' === $action ) {
			$action = $is_sent ? 'update' : 'create';
		}

		if ( 'create' === $action && $is_sent ) {
			$action = 'update';
		}

		if ( 'create' === $action ) {
			$payload['id'] = $external_id;
			$response      = $api->create_sign( $payload );

			if ( ! $response['success'] && 422 === $response['http_code'] ) {
				$response = $api->update_sign( $external_id, $payload );
				$action   = 'update';
			}
		} else {
			$response = $api->update_sign( $external_id, $payload );
			if ( ! $response['success'] && 404 === $response['http_code'] ) {
				$payload['id'] = $external_id;
				$response      = $api->create_sign( $payload );
				$action        = 'create';
			}
		}

		$status = $response['success'] ? ( 'update' === $action ? 'modifie' : 'envoye' ) : 'erreur';

		$this->update_post_status(
			$post_id,
			$status,
			$response['http_code'],
			wp_json_encode( $response['body'] )
		);

		HPK_PP_Logger::log(
			array(
				'post_id'      => $post_id,
				'action'       => 'create' === $action ? 'create' : 'update',
				'external_id'  => $external_id,
				'http_code'    => $response['http_code'],
				'status'       => $response['success'] ? 'success' : 'error',
				'api_response' => $response['body'],
			)
		);

		return $response;
	}

	/**
	 * Collect post data for API payload.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function collect_post_data( $post_id ) {
		$post = get_post( $post_id );

		$pp_title   = get_post_meta( $post_id, '_panneaupocket_title', true );
		$use_wp     = get_post_meta( $post_id, '_panneaupocket_use_wp_content', true );
		$pp_content = get_post_meta( $post_id, '_panneaupocket_content', true );
		$use_thumb  = get_post_meta( $post_id, '_panneaupocket_use_featured', true );
		$documents  = get_post_meta( $post_id, '_panneaupocket_documents', true );

		if ( ! is_array( $documents ) ) {
			$documents = array();
		}

		$title = ! empty( $pp_title ) ? $pp_title : $post->post_title;

		if ( '1' === $use_wp || 1 === $use_wp || ( empty( $pp_content ) && '1' !== get_post_meta( $post_id, '_panneaupocket_has_custom_content', true ) ) ) {
			$content = $post->post_content;
		} else {
			$content = $pp_content;
		}

		if ( '1' === $use_thumb || 1 === $use_thumb ) {
			$thumb_id = get_post_thumbnail_id( $post_id );
			if ( $thumb_id ) {
				$thumb_url = wp_get_attachment_url( $thumb_id );
				if ( $thumb_url ) {
					array_unshift( $documents, $thumb_url );
				}
			}
		}

		return array(
			'title'     => $title,
			'content'   => $content,
			'start_at'  => get_post_meta( $post_id, '_panneaupocket_start_at', true ) ?: gmdate( 'Y-m-d' ),
			'end_at'    => get_post_meta( $post_id, '_panneaupocket_end_at', true ),
			'type'      => get_post_meta( $post_id, '_panneaupocket_type', true ) ?: 'info',
			'documents' => $documents,
		);
	}

	/**
	 * Update post meta status after API call.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status Status string.
	 * @param int    $http_code HTTP code.
	 * @param string $response API response.
	 */
	private function update_post_status( $post_id, $status, $http_code, $response ) {
		update_post_meta( $post_id, '_panneaupocket_last_status', $status );
		update_post_meta( $post_id, '_panneaupocket_last_http_code', absint( $http_code ) );
		update_post_meta( $post_id, '_panneaupocket_last_response', $response );
		update_post_meta( $post_id, '_panneaupocket_last_sync', current_time( 'mysql' ) );
	}

	/**
	 * Save metabox meta from POST data.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_metabox_meta( $post_id ) {
		if ( ! isset( $_POST['hpk_pp_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hpk_pp_metabox_nonce'] ) ), 'hpk_pp_metabox' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! self::can_publish() ) {
			return;
		}

		$enabled = isset( $_POST['_panneaupocket_enabled'] ) ? '1' : '0';
		update_post_meta( $post_id, '_panneaupocket_enabled', $enabled );

		update_post_meta( $post_id, '_panneaupocket_draft_mode', isset( $_POST['_panneaupocket_draft_mode'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_panneaupocket_title', HPK_PP_Sanitizer::sanitize_title( wp_unslash( $_POST['_panneaupocket_title'] ?? '' ) ) );
		update_post_meta( $post_id, '_panneaupocket_type', HPK_PP_Sanitizer::sanitize_type( wp_unslash( $_POST['_panneaupocket_type'] ?? 'info' ) ) );
		update_post_meta( $post_id, '_panneaupocket_start_at', HPK_PP_Sanitizer::sanitize_date( wp_unslash( $_POST['_panneaupocket_start_at'] ?? '' ) ) );
		update_post_meta( $post_id, '_panneaupocket_end_at', HPK_PP_Sanitizer::sanitize_date( wp_unslash( $_POST['_panneaupocket_end_at'] ?? '' ) ) );

		$content = wp_kses_post( wp_unslash( $_POST['_panneaupocket_content'] ?? '' ) );
		update_post_meta( $post_id, '_panneaupocket_content', $content );
		update_post_meta( $post_id, '_panneaupocket_has_custom_content', ! empty( $content ) ? '1' : '0' );

		update_post_meta( $post_id, '_panneaupocket_use_wp_content', isset( $_POST['_panneaupocket_use_wp_content'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_panneaupocket_use_featured', isset( $_POST['_panneaupocket_use_featured'] ) ? '1' : '0' );

		$docs_raw = isset( $_POST['_panneaupocket_documents'] ) ? wp_unslash( $_POST['_panneaupocket_documents'] ) : array();
		$docs     = array();
		if ( is_array( $docs_raw ) ) {
			foreach ( $docs_raw as $doc ) {
				$url = esc_url_raw( trim( $doc ) );
				if ( $url ) {
					$docs[] = $url;
				}
			}
		}
		update_post_meta( $post_id, '_panneaupocket_documents', array_slice( $docs, 0, 5 ) );

		$featured_from_doc = isset( $_POST['_panneaupocket_featured_from_doc'] );
		HPK_PP_Document_Display::maybe_set_featured_image( $post_id, $docs, $featured_from_doc );

		if ( empty( get_post_meta( $post_id, '_panneaupocket_external_id', true ) ) ) {
			$post_type = get_post_type( $post_id );
			update_post_meta( $post_id, '_panneaupocket_external_id', HPK_PP_Sanitizer::get_external_id( $post_id, $post_type ) );
		}
	}

	/**
	 * AJAX: send now.
	 */
	public function ajax_send_now() {
		check_ajax_referer( 'hpk_pp_admin', 'nonce' );

		if ( ! self::can_publish() ) {
			wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'hpk-panneaupocket' ) ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'ID article invalide.', 'hpk-panneaupocket' ) ) );
		}

		update_post_meta( $post_id, '_panneaupocket_enabled', '1' );
		$result = $this->publish_post( $post_id, 'create' );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/**
	 * AJAX: update now.
	 */
	public function ajax_update_now() {
		check_ajax_referer( 'hpk_pp_admin', 'nonce' );

		if ( ! self::can_publish() ) {
			wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'hpk-panneaupocket' ) ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'ID article invalide.', 'hpk-panneaupocket' ) ) );
		}

		$result = $this->publish_post( $post_id, 'update' );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/**
	 * AJAX: preview payload.
	 */
	public function ajax_preview_payload() {
		check_ajax_referer( 'hpk_pp_admin', 'nonce' );

		if ( ! self::can_publish() ) {
			wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'hpk-panneaupocket' ) ) );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$data    = $this->collect_post_data( $post_id );
		$payload = HPK_PP_Sanitizer::build_payload( $data );

		if ( is_wp_error( $payload ) ) {
			wp_send_json_error( array( 'message' => $payload->get_error_message() ) );
		}

		$post        = get_post( $post_id );
		$external_id = HPK_PP_Sanitizer::get_external_id( $post_id, $post ? $post->post_type : 'post' );
		$payload['id'] = $external_id;

		wp_send_json_success( array( 'payload' => $payload ) );
	}

	/**
	 * Create standalone hpk_pp_sign post from admin form.
	 *
	 * @param array $form Form data.
	 * @return int|WP_Error
	 */
	public function create_standalone_sign( $form ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'hpk_pp_sign',
				'post_title'  => HPK_PP_Sanitizer::sanitize_title( $form['title'] ?? '' ),
				'post_status' => 'publish',
				'post_content'=> wp_kses_post( $form['content'] ?? '' ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->save_standalone_meta( $post_id, $form );
		update_post_meta( $post_id, '_panneaupocket_enabled', '1' );
		update_post_meta( $post_id, '_panneaupocket_external_id', HPK_PP_Sanitizer::get_external_id( $post_id, 'hpk_pp_sign' ) );

		return $post_id;
	}

	/**
	 * Update standalone sign.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $form Form data.
	 */
	public function update_standalone_sign( $post_id, $form ) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => HPK_PP_Sanitizer::sanitize_title( $form['title'] ?? '' ),
				'post_content' => wp_kses_post( $form['content'] ?? '' ),
			)
		);

		$this->save_standalone_meta( $post_id, $form );
	}

	/**
	 * Save standalone meta fields.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $form Form data.
	 */
	private function save_standalone_meta( $post_id, $form ) {
		update_post_meta( $post_id, '_panneaupocket_title', HPK_PP_Sanitizer::sanitize_title( $form['title'] ?? '' ) );
		update_post_meta( $post_id, '_panneaupocket_type', HPK_PP_Sanitizer::sanitize_type( $form['type'] ?? 'info' ) );
		update_post_meta( $post_id, '_panneaupocket_start_at', HPK_PP_Sanitizer::sanitize_date( $form['start_at'] ?? '' ) );
		update_post_meta( $post_id, '_panneaupocket_end_at', HPK_PP_Sanitizer::sanitize_date( $form['end_at'] ?? '' ) );
		update_post_meta( $post_id, '_panneaupocket_content', wp_kses_post( $form['content'] ?? '' ) );
		update_post_meta( $post_id, '_panneaupocket_use_wp_content', ! empty( $form['use_wp_content'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_panneaupocket_use_featured', ! empty( $form['use_featured'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_panneaupocket_draft_mode', ! empty( $form['draft_mode'] ) ? '1' : '0' );

		$docs = array();
		if ( ! empty( $form['documents'] ) && is_array( $form['documents'] ) ) {
			foreach ( $form['documents'] as $doc ) {
				$url = esc_url_raw( trim( $doc ) );
				if ( $url ) {
					$docs[] = $url;
				}
			}
		}
		update_post_meta( $post_id, '_panneaupocket_documents', array_slice( $docs, 0, 5 ) );
	}
}
