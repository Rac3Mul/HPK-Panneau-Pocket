<?php
/**
 * Frontend display of PanneauPocket document attachments.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Document_Display
 */
class HPK_PP_Document_Display {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Document_Display|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Document_Display
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
		add_filter( 'the_content', array( $this, 'append_documents_to_content' ), 15 );
	}

	/**
	 * Check if featured image should come from the first document.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_featured_from_doc( $post_id ) {
		$enabled = get_post_meta( $post_id, '_panneaupocket_featured_from_doc', true );
		if ( '1' === $enabled || 1 === $enabled ) {
			return true;
		}

		$sign_id = absint( get_post_meta( $post_id, '_panneaupocket_linked_sign', true ) );
		if ( $sign_id ) {
			$sign_enabled = get_post_meta( $sign_id, '_panneaupocket_featured_from_doc', true );
			return '1' === $sign_enabled || 1 === $sign_enabled;
		}

		return false;
	}

	/**
	 * Compare two document URLs (ignores query string).
	 *
	 * @param string $a URL A.
	 * @param string $b URL B.
	 * @return bool
	 */
	public static function urls_match( $a, $b ) {
		$a = untrailingslashit( (string) strtok( $a, '?' ) );
		$b = untrailingslashit( (string) strtok( $b, '?' ) );
		return $a === $b;
	}

	/**
	 * Resolve or import a media attachment from a document URL.
	 *
	 * @param string $url Image URL.
	 * @param int    $post_id Parent post ID.
	 * @return int Attachment ID or 0.
	 */
	public static function resolve_attachment_id( $url, $post_id = 0 ) {
		$url = esc_url_raw( trim( $url ) );
		if ( empty( $url ) ) {
			return 0;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_hpk_pp_source_url',
				'meta_value'     => $url,
			)
		);
		if ( ! empty( $existing[0] ) ) {
			return absint( $existing[0] );
		}

		$attachment_id = attachment_url_to_postid( $url );
		if ( ! $attachment_id ) {
			$attachment_id = attachment_url_to_postid( strtok( $url, '?' ) );
		}
		if ( $attachment_id ) {
			update_post_meta( $attachment_id, '_hpk_pp_source_url', $url );
			return absint( $attachment_id );
		}

		return self::import_image_as_attachment( $url, $post_id );
	}

	/**
	 * Import an image URL into the WordPress media library.
	 *
	 * @param string $url Image URL.
	 * @param int    $post_id Parent post ID.
	 * @return int Attachment ID or 0.
	 */
	private static function import_image_as_attachment( $url, $post_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_array = null;

		if ( 0 === strpos( $url, HPK_PP_URL ) ) {
			$file_path = HPK_PP_PATH . substr( $url, strlen( HPK_PP_URL ) );
			if ( ! file_exists( $file_path ) ) {
				return 0;
			}
			$tmp = wp_tempnam( basename( $file_path ) );
			if ( ! $tmp || ! copy( $file_path, $tmp ) ) {
				return 0;
			}
			$file_array = array(
				'name'     => basename( $file_path ),
				'tmp_name' => $tmp,
			);
		} else {
			$tmp = download_url( $url );
			if ( is_wp_error( $tmp ) ) {
				return 0;
			}
			$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
			if ( empty( $filename ) ) {
				$filename = 'panneaupocket-image.jpg';
			}
			$file_array = array(
				'name'     => $filename,
				'tmp_name' => $tmp,
			);
		}

		$attachment_id = media_handle_sideload( $file_array, $post_id );
		if ( is_wp_error( $attachment_id ) ) {
			if ( ! empty( $file_array['tmp_name'] ) && file_exists( $file_array['tmp_name'] ) ) {
				wp_delete_file( $file_array['tmp_name'] );
			}
			return 0;
		}

		update_post_meta( $attachment_id, '_hpk_pp_source_url', $url );
		return absint( $attachment_id );
	}

	/**
	 * Remove the featured image document from the public attachments list.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $documents Document URLs.
	 * @return array
	 */
	public static function filter_documents_for_display( $post_id, $documents ) {
		if ( ! self::is_featured_from_doc( $post_id ) ) {
			return $documents;
		}

		$first_image = self::get_first_image_url( $documents );
		if ( empty( $first_image ) ) {
			return $documents;
		}

		$filtered = array();
		foreach ( $documents as $url ) {
			if ( ! self::urls_match( $url, $first_image ) ) {
				$filtered[] = $url;
			}
		}

		return array_values( $filtered );
	}

	/**
	 * Find WordPress post linked to a PanneauPocket sign.
	 *
	 * @param int $sign_id Sign post ID.
	 * @return int
	 */
	public static function get_linked_wp_post_id( $sign_id ) {
		$sign_id = absint( $sign_id );
		if ( ! $sign_id ) {
			return 0;
		}

		$wp_post_id = absint( get_post_meta( $sign_id, '_panneaupocket_linked_wp_post', true ) );
		if ( $wp_post_id && 'post' === get_post_type( $wp_post_id ) ) {
			return $wp_post_id;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_panneaupocket_linked_sign',
				'meta_value'     => $sign_id,
			)
		);

		if ( ! empty( $posts[0] ) ) {
			update_post_meta( $sign_id, '_panneaupocket_linked_wp_post', absint( $posts[0] ) );
			return absint( $posts[0] );
		}

		return 0;
	}

	/**
	 * Sync sign data to a linked public WordPress post.
	 *
	 * @param int   $sign_id Sign post ID.
	 * @param array $form Form data.
	 * @return int WordPress post ID or 0.
	 */
	public static function sync_linked_wp_post( $sign_id, $form ) {
		$sign_id = absint( $sign_id );
		if ( ! $sign_id ) {
			return 0;
		}

		$wp_post_id = self::get_linked_wp_post_id( $sign_id );
		$post_data  = array(
			'post_type'    => 'post',
			'post_title'   => HPK_PP_Sanitizer::sanitize_title( $form['title'] ?? '' ),
			'post_content' => wp_kses_post( $form['content'] ?? '' ),
			'post_status'  => 'publish',
		);

		if ( $wp_post_id ) {
			$post_data['ID'] = $wp_post_id;
			wp_update_post( $post_data );
		} else {
			$wp_post_id = wp_insert_post( $post_data, true );
			if ( is_wp_error( $wp_post_id ) || ! $wp_post_id ) {
				return 0;
			}
		}

		update_post_meta( $sign_id, '_panneaupocket_linked_wp_post', $wp_post_id );
		update_post_meta( $wp_post_id, '_panneaupocket_linked_sign', $sign_id );
		update_post_meta( $wp_post_id, '_panneaupocket_enabled', '1' );

		$meta_keys = array(
			'_panneaupocket_title'       => HPK_PP_Sanitizer::sanitize_title( $form['title'] ?? '' ),
			'_panneaupocket_type'        => HPK_PP_Sanitizer::sanitize_type( $form['type'] ?? 'info' ),
			'_panneaupocket_start_at'    => HPK_PP_Sanitizer::sanitize_date( $form['start_at'] ?? '' ),
			'_panneaupocket_end_at'      => HPK_PP_Sanitizer::sanitize_date( $form['end_at'] ?? '' ),
			'_panneaupocket_content'     => wp_kses_post( $form['content'] ?? '' ),
			'_panneaupocket_has_custom_content' => ! empty( $form['content'] ) ? '1' : '0',
		);

		foreach ( $meta_keys as $key => $value ) {
			update_post_meta( $wp_post_id, $key, $value );
		}

		$docs = array();
		if ( ! empty( $form['documents'] ) && is_array( $form['documents'] ) ) {
			foreach ( $form['documents'] as $doc ) {
				$url = esc_url_raw( trim( (string) $doc ) );
				if ( $url ) {
					$docs[] = $url;
				}
			}
		}
		$docs = array_slice( $docs, 0, 5 );
		update_post_meta( $wp_post_id, '_panneaupocket_documents', $docs );

		$show_docs = ! empty( $form['show_documents_in_article'] );
		update_post_meta( $wp_post_id, '_panneaupocket_show_documents_in_article', $show_docs ? '1' : '0' );

		self::maybe_set_featured_image( $wp_post_id, $docs, ! empty( $form['featured_from_doc'] ) );

		return $wp_post_id;
	}

	/**
	 * Get sanitized document URLs for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function get_post_documents( $post_id ) {
		$documents = get_post_meta( $post_id, '_panneaupocket_documents', true );
		if ( ! is_array( $documents ) || empty( array_filter( $documents ) ) ) {
			$sign_id = absint( get_post_meta( $post_id, '_panneaupocket_linked_sign', true ) );
			if ( $sign_id ) {
				$documents = get_post_meta( $sign_id, '_panneaupocket_documents', true );
			}
		}

		if ( ! is_array( $documents ) ) {
			return array();
		}

		$clean = array();
		foreach ( $documents as $url ) {
			$url = esc_url_raw( trim( (string) $url ) );
			if ( $url ) {
				$clean[] = $url;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Whether documents should be appended to post content.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function should_show_documents_in_article( $post_id ) {
		$show = get_post_meta( $post_id, '_panneaupocket_show_documents_in_article', true );
		if ( '0' === $show || 0 === $show ) {
			return false;
		}

		$sign_id = absint( get_post_meta( $post_id, '_panneaupocket_linked_sign', true ) );
		if ( $sign_id ) {
			$sign_show = get_post_meta( $sign_id, '_panneaupocket_show_documents_in_article', true );
			if ( '0' === $sign_show || 0 === $sign_show ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get first image URL from a documents list.
	 *
	 * @param array $documents Document URLs.
	 * @return string
	 */
	public static function get_first_image_url( $documents ) {
		if ( ! is_array( $documents ) ) {
			return '';
		}

		foreach ( $documents as $url ) {
			$url = esc_url_raw( trim( (string) $url ) );
			if ( $url && HPK_PP_Sanitizer::is_display_image_url( $url ) ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Set featured image from the first image document when enabled.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $documents Document URLs.
	 * @param bool  $enabled Whether the option is checked.
	 */
	public static function maybe_set_featured_image( $post_id, $documents, $enabled ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_panneaupocket_featured_from_doc', $enabled ? '1' : '0' );

		if ( ! $enabled ) {
			return;
		}

		$image_url = self::get_first_image_url( $documents );
		if ( empty( $image_url ) ) {
			return;
		}

		$attachment_id = self::resolve_attachment_id( $image_url, $post_id );

		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
			update_post_meta( $post_id, '_panneaupocket_featured_doc_url', $image_url );
		}
	}

	/**
	 * Append document attachments to post content on singular views.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_documents_to_content( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || ! self::should_show_documents_in_article( $post_id ) ) {
			return $content;
		}

		$documents = self::get_post_documents( $post_id );
		$documents = self::filter_documents_for_display( $post_id, $documents );
		if ( empty( $documents ) ) {
			return $content;
		}

		$html = self::render_documents_html( $documents, $post_id );
		if ( empty( $html ) ) {
			return $content;
		}

		return $content . $html;
	}

	/**
	 * Build HTML for document list.
	 *
	 * @param array $documents Document URLs.
	 * @param int   $post_id   Post ID (optional, skips images used as featured).
	 * @return string
	 */
	public static function render_documents_html( $documents, $post_id = 0 ) {
		if ( empty( $documents ) || ! is_array( $documents ) ) {
			return '';
		}

		if ( $post_id ) {
			$documents = self::filter_documents_for_display( $post_id, $documents );
		}

		if ( empty( $documents ) ) {
			return '';
		}

		$items = array();
		foreach ( $documents as $url ) {
			$url = esc_url_raw( trim( (string) $url ) );
			if ( empty( $url ) ) {
				continue;
			}

			if ( HPK_PP_Sanitizer::is_display_image_url( $url ) ) {
				if ( $post_id && self::is_featured_from_doc( $post_id ) ) {
					continue;
				}
				$items[] = sprintf(
					'<figure class="hpk-pp-post-document hpk-pp-post-document--image"><img src="%1$s" alt="" loading="lazy" /></figure>',
					esc_url( $url )
				);
				continue;
			}

			if ( HPK_PP_Sanitizer::is_pdf_url( $url ) ) {
				$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
				$items[]  = sprintf(
					'<p class="hpk-pp-post-document hpk-pp-post-document--pdf"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
					esc_url( $url ),
					esc_html(
						sprintf(
							/* translators: %s: PDF file name */
							__( 'Télécharger le PDF : %s', 'hpk-panneaupocket' ),
							$filename ?: __( 'document.pdf', 'hpk-panneaupocket' )
						)
					)
				);
				continue;
			}

			$filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
			$items[]  = sprintf(
				'<p class="hpk-pp-post-document hpk-pp-post-document--file"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
				esc_url( $url ),
				esc_html( $filename ?: $url )
			);
		}

		if ( empty( $items ) ) {
			return '';
		}

		return sprintf(
			'<section class="hpk-pp-post-documents" aria-label="%1$s"><h2 class="hpk-pp-post-documents__title">%2$s</h2>%3$s</section>',
			esc_attr__( 'Pièces jointes PanneauPocket', 'hpk-panneaupocket' ),
			esc_html__( 'Pièces jointes', 'hpk-panneaupocket' ),
			implode( '', $items )
		);
	}
}
