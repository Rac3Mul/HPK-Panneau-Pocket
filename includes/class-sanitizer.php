<?php
/**
 * Sanitizer and validator for PanneauPocket data.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Sanitizer
 */
class HPK_PP_Sanitizer {

	/**
	 * Allowed HTML tags for content.
	 *
	 * @return array
	 */
	public static function allowed_html() {
		return array(
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'em'     => array(),
			'u'      => array(),
			's'      => array(),
			'del'    => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'a'      => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			),
			'h2'     => array(),
			'h3'     => array(),
		);
	}

	/**
	 * Sanitize title (max 50 chars).
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public static function sanitize_title( $title ) {
		$title = sanitize_text_field( $title );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $title, 0, 50 );
		}
		return substr( $title, 0, 50 );
	}

	/**
	 * Sanitize sign type.
	 *
	 * @param string $type Type.
	 * @return string
	 */
	public static function sanitize_type( $type ) {
		$type = sanitize_text_field( $type );
		return in_array( $type, array( 'info', 'alert' ), true ) ? $type : 'info';
	}

	/**
	 * Sanitize date to Y-m-d.
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	public static function sanitize_date( $date ) {
		$date = sanitize_text_field( $date );
		$ts   = strtotime( $date );
		if ( ! $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d', $ts );
	}

	/**
	 * Sanitize WordPress category term IDs.
	 *
	 * @param mixed $ids Category IDs.
	 * @return int[]
	 */
	public static function sanitize_category_ids( $ids ) {
		if ( ! is_array( $ids ) ) {
			return array();
		}

		$clean = array();
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id && term_exists( $id, 'category' ) ) {
				$clean[] = $id;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Clean HTML content for API.
	 *
	 * @param string $content Raw content.
	 * @return string
	 */
	public static function sanitize_content( $content ) {
		$content = strip_shortcodes( $content );
		$content = preg_replace( '/<(script|iframe|video|audio|form|embed|object)[^>]*>.*?<\/\1>/is', '', $content );
		$content = preg_replace( '/<(script|iframe|video|audio|form|embed|object)[^>]*\/?>/i', '', $content );

		// TinyMCE inline styles -> tags supported by PanneauPocket.
		$content = preg_replace( '/<span[^>]*style="[^"]*text-decoration\s*:\s*underline[^"]*"[^>]*>(.*?)<\/span>/is', '<u>$1</u>', $content );
		$content = preg_replace( '/<span[^>]*style="[^"]*line-through[^"]*"[^>]*>(.*?)<\/span>/is', '<s>$1</s>', $content );
		$content = str_replace( array( '<b>', '</b>', '<i>', '</i>' ), array( '<strong>', '</strong>', '<em>', '</em>' ), $content );

		$content = wp_kses( $content, self::allowed_html() );
		$content = self::normalize_content_line_breaks( $content );

		return trim( $content );
	}

	/**
	 * Convert editor HTML to PanneauPocket format: one <p> block with <br /> line breaks.
	 *
	 * @see https://gestion.panneaupocket.com/public-api (content example: "<p></p>")
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	public static function normalize_content_line_breaks( $content ) {
		if ( '' === trim( $content ) ) {
			return '';
		}

		$content = self::convert_lists_to_line_breaks( $content );

		$content = preg_replace( '/<h([23])(?:\s[^>]*)?>(.*?)<\/h\1>/is', '<strong>$2</strong><br />', $content );

		// Empty lines from TinyMCE (Enter twice) before merging paragraphs.
		$content = self::convert_empty_blocks_to_breaks( $content );

		$content = preg_replace( '/<\/p>\s*<p(?:\s[^>]*)?>/i', '<br />', $content );
		$content = preg_replace( '/<\/div>\s*<div(?:\s[^>]*)?>/i', '<br />', $content );
		$content = preg_replace( '/<p(?:\s[^>]*)?>/i', '', $content );
		$content = preg_replace( '/<\/p>/i', '<br />', $content );
		$content = preg_replace( '/<div(?:\s[^>]*)?>/i', '', $content );
		$content = preg_replace( '/<\/div>/i', '<br />', $content );

		if ( false !== strpos( $content, "\n" ) && ! preg_match( '/<br\b/i', $content ) ) {
			$content = nl2br( $content, false );
		}

		$content = preg_replace( '/<br\s*\/?>/i', '<br />', $content );
		$content = preg_replace( '/&nbsp;/i', ' ', $content );
		$content = preg_replace( '/(?:<br\s*\/?>\s*){3,}/i', '<br /><br />', $content );
		$content = preg_replace( '/(?:<br\s*\/?>\s*)+$/i', '', $content );
		$content = trim( $content );

		if ( '' === $content ) {
			return '';
		}

		if ( preg_match( '/^<p(?:\s[^>]*)?>.*<\/p>$/is', $content ) && 1 === preg_match_all( '/<p\b/i', $content ) ) {
			return $content;
		}

		return '<p>' . $content . '</p>';
	}

	/**
	 * Turn empty editor blocks into line breaks (double Enter in TinyMCE).
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	private static function convert_empty_blocks_to_breaks( $content ) {
		return preg_replace(
			'/<(?:p|div)(?:\s[^>]*)?>\s*(?:&nbsp;|<br\s[^>]*\/?>)?\s*<\/(?:p|div)>/i',
			'<br />',
			$content
		);
	}

	/**
	 * Flatten ul/ol into br-separated lines (PanneauPocket ignores list block tags).
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	private static function convert_lists_to_line_breaks( $content ) {
		$content = preg_replace_callback(
			'/<ul(?:\s[^>]*)?>(.*?)<\/ul>/is',
			static function ( $matches ) {
				return self::extract_list_lines( $matches[1], false );
			},
			$content
		);

		$content = preg_replace_callback(
			'/<ol(?:\s[^>]*)?>(.*?)<\/ol>/is',
			static function ( $matches ) {
				return self::extract_list_lines( $matches[1], true );
			},
			$content
		);

		return $content;
	}

	/**
	 * @param string $inner List inner HTML.
	 * @param bool   $ordered Ordered list.
	 * @return string
	 */
	private static function extract_list_lines( $inner, $ordered ) {
		if ( ! preg_match_all( '/<li(?:\s[^>]*)?>(.*?)<\/li>/is', $inner, $items, PREG_SET_ORDER ) ) {
			return '';
		}

		$lines = array();
		$index = 1;
		foreach ( $items as $item ) {
			$line = trim( $item[1] );
			if ( '' === $line ) {
				continue;
			}
			$prefix          = $ordered ? $index . '. ' : '• ';
			$lines[]         = $prefix . $line;
			++$index;
		}

		return implode( '<br />', $lines );
	}

	/**
	 * Get lowercase file extension from a URL path.
	 *
	 * @param string $url Document URL.
	 * @return string
	 */
	public static function get_url_extension( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( empty( $path ) ) {
			return '';
		}
		return strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	}

	/**
	 * Check if URL points to an allowed image (jpg/png).
	 *
	 * @param string $url Document URL.
	 * @return bool
	 */
	public static function is_image_url( $url ) {
		return in_array( self::get_url_extension( $url ), array( 'jpg', 'jpeg', 'png' ), true );
	}

	/**
	 * Check if URL is displayable as an image on WordPress (includes webp).
	 *
	 * @param string $url Document URL.
	 * @return bool
	 */
	public static function is_display_image_url( $url ) {
		return in_array( self::get_url_extension( $url ), array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ), true );
	}

	/**
	 * Check if URL points to a PDF.
	 *
	 * @param string $url Document URL.
	 * @return bool
	 */
	public static function is_pdf_url( $url ) {
		return 'pdf' === self::get_url_extension( $url );
	}

	/**
	 * Validate and sanitize document URLs.
	 *
	 * @param array $urls Document URLs.
	 * @return array|WP_Error
	 */
	public static function sanitize_documents( $urls ) {
		if ( ! is_array( $urls ) ) {
			$urls = array();
		}

		$allowed_ext = array( 'jpg', 'jpeg', 'png', 'pdf' );
		$clean       = array();
		$errors      = array();

		foreach ( $urls as $url ) {
			$url = esc_url_raw( trim( $url ) );
			if ( empty( $url ) ) {
				continue;
			}

			if ( ! self::is_public_url( $url ) ) {
				$errors[] = sprintf(
					/* translators: %s: URL */
					__( 'URL non publique ou invalide : %s', 'hpk-panneaupocket' ),
					$url
				);
				continue;
			}

			$ext = self::get_url_extension( $url );

			if ( ! in_array( $ext, $allowed_ext, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: URL */
					__( 'Format de fichier non accepté : %s', 'hpk-panneaupocket' ),
					$url
				);
				continue;
			}

			$size_check = self::check_file_size( $url );
			if ( is_wp_error( $size_check ) ) {
				$errors[] = $size_check->get_error_message();
				continue;
			}

			$clean[] = $url;
		}

		$clean = array_slice( array_unique( $clean ), 0, 5 );

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'invalid_documents', implode( ' ', $errors ) );
		}

		return $clean;
	}

	/**
	 * Check if URL is public (not localhost).
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_public_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] ?? '' );
		$blocked = array( 'localhost', '127.0.0.1', '0.0.0.0', '::1' );
		if ( in_array( $host, $blocked, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check remote file size (max 15 Mo).
	 *
	 * @param string $url URL.
	 * @return true|WP_Error
	 */
	public static function check_file_size( $url ) {
		$response = wp_remote_head(
			$url,
			array(
				'timeout'   => 5,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return true;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 ) {
			return new WP_Error(
				'url_not_accessible',
				sprintf(
					/* translators: %s: URL */
					__( 'Document inaccessible : %s', 'hpk-panneaupocket' ),
					$url
				)
			);
		}

		$size = wp_remote_retrieve_header( $response, 'content-length' );
		if ( $size && (int) $size > 15 * 1024 * 1024 ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: URL */
					__( 'Fichier trop volumineux (max 15 Mo) : %s', 'hpk-panneaupocket' ),
					$url
				)
			);
		}

		return true;
	}

	/**
	 * Build and validate full sign payload.
	 *
	 * @param array $data Input data.
	 * @return array|WP_Error
	 */
	public static function build_payload( $data ) {
		$title   = self::sanitize_title( $data['title'] ?? '' );
		$content = self::sanitize_content( $data['content'] ?? '' );
		$start   = self::sanitize_date( $data['start_at'] ?? '' );
		$end     = ! empty( $data['end_at'] ) ? self::sanitize_date( $data['end_at'] ) : null;
		$type    = self::sanitize_type( $data['type'] ?? 'info' );

		if ( empty( $start ) ) {
			return new WP_Error( 'missing_start', __( 'La date de début (startAt) est obligatoire.', 'hpk-panneaupocket' ) );
		}

		if ( empty( $title ) && empty( $content ) && empty( $data['documents'] ) ) {
			return new WP_Error( 'empty_sign', __( 'Le titre, le contenu ou au moins un document est requis.', 'hpk-panneaupocket' ) );
		}

		$documents = self::sanitize_documents( $data['documents'] ?? array() );
		if ( is_wp_error( $documents ) ) {
			return $documents;
		}

		$payload = array(
			'title'     => $title,
			'content'   => $content,
			'startAt'   => $start,
			'type'      => $type,
			'documents' => $documents,
		);

		if ( $end ) {
			$payload['endAt'] = $end;
		}

		return $payload;
	}

	/**
	 * Get external ID for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @return string
	 */
	public static function get_external_id( $post_id, $post_type = 'post' ) {
		$stored = get_post_meta( $post_id, '_panneaupocket_external_id', true );
		if ( ! empty( $stored ) ) {
			return sanitize_text_field( $stored );
		}

		if ( 'hpk_pp_sign' === $post_type ) {
			return 'wordpress-pp-sign-' . absint( $post_id );
		}

		return 'wordpress-post-' . absint( $post_id );
	}
}
