<?php
/**
 * Public agenda reader (city page HTML — no official agenda API).
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Agenda
 */
class HPK_PP_Agenda {

	const CACHE_TTL = 1800;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule_refresh' ) );
		add_action( 'hpk_pp_refresh_agenda', array( __CLASS__, 'refresh_cache' ) );
	}

	/**
	 * Hourly refresh so the front stays fast.
	 */
	public function schedule_refresh() {
		if ( ! wp_next_scheduled( 'hpk_pp_refresh_agenda' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'hpk_pp_refresh_agenda' );
		}
	}

	/**
	 * Refresh cached events for the configured city.
	 */
	public static function refresh_cache() {
		$city_id = trim( (string) get_option( 'hpk_pp_city_id', '' ) );
		if ( '' === $city_id ) {
			return;
		}
		self::fetch_and_store( $city_id );
	}

	/**
	 * Events ready for display.
	 *
	 * @param array $args Display args.
	 * @return array
	 */
	public static function get_events( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'city_id'      => '',
				'limit'        => 6,
				'include_past' => false,
			)
		);

		$city_id = trim( (string) ( $args['city_id'] ? $args['city_id'] : get_option( 'hpk_pp_city_id', '' ) ) );
		if ( '' === $city_id ) {
			return array();
		}

		$cache_key = 'hpk_pp_agenda_v5_' . $city_id;
		$events    = get_transient( $cache_key );

		if ( false === $events ) {
			$fetched = self::fetch_and_store( $city_id );
			$events  = is_wp_error( $fetched ) ? self::get_backup( $city_id ) : $fetched;
		}

		if ( ! is_array( $events ) ) {
			return array();
		}

		if ( empty( $args['include_past'] ) ) {
			$today = current_time( 'Y-m-d' );
			$events = array_values(
				array_filter(
					$events,
					static function ( $event ) use ( $today ) {
						return empty( $event['date'] ) || $event['date'] >= $today;
					}
				)
			);
		}

		usort(
			$events,
			static function ( $a, $b ) {
				return strcmp( (string) ( $a['date'] ?? '9999-12-31' ), (string) ( $b['date'] ?? '9999-12-31' ) );
			}
		);

		$limit = absint( $args['limit'] );
		if ( $limit > 0 ) {
			$events = array_slice( $events, 0, $limit );
		}

		return $events;
	}

	/**
	 * Info signs (not events, not alerts), newest first.
	 *
	 * @param array $args Display args.
	 * @return array
	 */
	public static function get_news( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'city_id' => '',
				'limit'   => 8,
			)
		);

		$city_id = trim( (string) ( $args['city_id'] ? $args['city_id'] : get_option( 'hpk_pp_city_id', '' ) ) );
		if ( '' === $city_id ) {
			return array();
		}

		$cache_key = 'hpk_pp_news_v2_' . $city_id;
		$news      = get_transient( $cache_key );

		if ( false === $news ) {
			$fetched = self::fetch_and_store( $city_id );
			$news    = is_wp_error( $fetched ) ? self::get_news_backup( $city_id ) : get_transient( $cache_key );
		}

		if ( ! is_array( $news ) ) {
			return array();
		}

		usort(
			$news,
			static function ( $a, $b ) {
				return strcmp( (string) ( $b['date'] ?? '0000-00-00' ), (string) ( $a['date'] ?? '0000-00-00' ) );
			}
		);

		$limit = absint( $args['limit'] );
		if ( $limit > 0 ) {
			$news = array_slice( $news, 0, $limit );
		}

		return $news;
	}

	/**
	 * One news item by public id.
	 *
	 * @param string $id Panneau id.
	 * @return array|null
	 */
	public static function find_news( $id ) {
		$id = preg_replace( '/\D+/', '', (string) $id );
		if ( '' === $id ) {
			return null;
		}
		foreach ( self::get_news( array( 'limit' => 60 ) ) as $item ) {
			if ( (string) ( $item['id'] ?? '' ) === $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Whether a published page contains the À la une shortcode.
	 *
	 * @return bool
	 */
	public static function has_une_page() {
		$page_id = (int) get_option( 'hpk_pp_une_page_id' );
		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			return true;
		}
		return self::locate_une_page() > 0;
	}

	/**
	 * Archive URL.
	 *
	 * @return string
	 */
	public static function archive_url() {
		$page_id = (int) get_option( 'hpk_pp_une_page_id' );
		if ( $page_id < 1 || 'publish' !== get_post_status( $page_id ) ) {
			$page_id = self::locate_une_page();
			if ( $page_id > 0 ) {
				update_option( 'hpk_pp_une_page_id', $page_id, false );
			}
		}
		if ( $page_id > 0 ) {
			$link = get_permalink( $page_id );
			if ( $link ) {
				return $link;
			}
		}
		return home_url( '/' );
	}

	/**
	 * Find the published page that contains the À la une shortcode.
	 *
	 * @return int
	 */
	private static function locate_une_page() {
		global $wpdb;

		$like = '%panneaupocket_une%';
		$id   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_content LIKE %s ORDER BY ID DESC LIMIT 1",
				$like
			)
		);
		if ( $id ) {
			return (int) $id;
		}

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_elementor_data' AND pm.meta_value LIKE %s AND p.post_type = 'page' AND p.post_status = 'publish' LIMIT 1",
				$like
			)
		);

		return $id ? (int) $id : 0;
	}

	/**
	 * Single news URL.
	 *
	 * @param string $id Panneau id.
	 * @return string
	 */
	public static function news_url( $id ) {
		$id = preg_replace( '/\D+/', '', (string) $id );
		return add_query_arg( 'actu', $id, self::archive_url() );
	}

	/**
	 * French long date from Y-m-d.
	 *
	 * @param string $date Y-m-d.
	 * @return string
	 */
	public static function format_long_date( $date ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $date, $m ) ) {
			return '';
		}
		$names = array( 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
		$index = (int) $m[2] - 1;
		if ( ! isset( $names[ $index ] ) ) {
			return '';
		}
		return (int) $m[3] . ' ' . $names[ $index ] . ' ' . $m[1];
	}

	/**
	 * Fetch the public city page and store events.
	 *
	 * @param string $city_id City ID.
	 * @return array|WP_Error
	 */
	public static function fetch_and_store( $city_id ) {
		$page = self::download_city( $city_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		$events = self::shrink_images( self::parse_signs( $page['html'], $page['url'], 'event' ), 6 );
		$news   = self::shrink_images( self::parse_signs( $page['html'], $page['url'], 'news' ), 12 );

		set_transient( 'hpk_pp_agenda_v5_' . $city_id, $events, self::CACHE_TTL );
		update_option( 'hpk_pp_agenda_backup_v5_' . $city_id, $events, false );
		set_transient( 'hpk_pp_news_v2_' . $city_id, $news, self::CACHE_TTL );
		update_option( 'hpk_pp_news_backup_v2_' . $city_id, $news, false );

		return $events;
	}

	/**
	 * Copy remote posters into a small local JPEG.
	 *
	 * PanneauPocket serves full-size files from S3 with no cache lifetime.
	 *
	 * @param array $items News or events.
	 * @param int   $limit How many posters to copy on this run.
	 * @return array
	 */
	private static function shrink_images( $items, $limit ) {
		$done  = 0;
		$start = microtime( true );
		foreach ( $items as $index => $item ) {
			if ( $done >= $limit || ( microtime( true ) - $start ) > 12 ) {
				break;
			}
			if ( empty( $item['image'] ) || false === strpos( $item['image'], 'panneau-pocket' ) ) {
				continue;
			}
			$local = self::localize_image( $item['image'] );
			if ( $local !== $item['image'] ) {
				$items[ $index ]['image'] = $local;
				$done++;
			}
		}
		return $items;
	}

	/**
	 * Download one poster and store a 960px JPEG on this site.
	 *
	 * @param string $url Remote image.
	 * @return string
	 */
	private static function localize_image( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || 0 !== strpos( $url, 'https://' ) ) {
			return $url;
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return $url;
		}

		$dir  = trailingslashit( $upload['basedir'] ) . 'hpk-pp';
		$base = trailingslashit( $upload['baseurl'] ) . 'hpk-pp';
		if ( ! wp_mkdir_p( $dir ) ) {
			return $url;
		}

		$rules = $dir . '/.htaccess';
		if ( ! file_exists( $rules ) ) {
			file_put_contents( $rules, "<IfModule mod_headers.c>\nHeader set Cache-Control \"public, max-age=31536000\"\n</IfModule>\n" );
		}

		$name   = md5( $url ) . '.jpg';
		$path   = $dir . '/' . $name;
		$public = $base . '/' . $name;
		if ( file_exists( $path ) && filesize( $path ) > 800 ) {
			return $public;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 20 );
		if ( is_wp_error( $tmp ) ) {
			return $url;
		}

		$editor = wp_get_image_editor( $tmp );
		if ( is_wp_error( $editor ) ) {
			wp_delete_file( $tmp );
			return $url;
		}

		$size = $editor->get_size();
		if ( ! empty( $size['width'] ) && (int) $size['width'] > 960 ) {
			$editor->resize( 960, null, false );
		}
		$editor->set_quality( 72 );
		$saved = $editor->save( $path, 'image/jpeg' );
		wp_delete_file( $tmp );

		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			return $url;
		}

		return $public;
	}

	/**
	 * Last successful snapshot.
	 *
	 * @param string $city_id City ID.
	 * @return array
	 */
	private static function get_backup( $city_id ) {
		$backup = get_option( 'hpk_pp_agenda_backup_v5_' . $city_id, array() );
		return is_array( $backup ) ? $backup : array();
	}

	/**
	 * Last successful news snapshot.
	 *
	 * @param string $city_id City ID.
	 * @return array
	 */
	private static function get_news_backup( $city_id ) {
		$backup = get_option( 'hpk_pp_news_backup_v2_' . $city_id, array() );
		return is_array( $backup ) ? $backup : array();
	}

	/**
	 * Download and parse events.
	 *
	 * @param string $city_id City ID.
	 * @return array|WP_Error
	 */
	public static function fetch_events( $city_id ) {
		$page = self::download_city( $city_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		return self::parse_signs( $page['html'], $page['url'], 'event' );
	}

	/**
	 * Download the public city page.
	 *
	 * @param string $city_id City ID.
	 * @return array|WP_Error html and url.
	 */
	private static function download_city( $city_id ) {
		$city_id = preg_replace( '/\D+/', '', (string) $city_id );
		if ( '' === $city_id ) {
			return new WP_Error( 'hpk_pp_no_city', __( 'City ID manquant.', 'hpk-panneaupocket' ) );
		}

		$embed_base = untrailingslashit( get_option( 'hpk_pp_embed_url', 'https://app.panneaupocket.com' ) );
		$embed_url  = $embed_base . '/embeded/' . rawurlencode( $city_id ) . '?mode=widget&autoNavigation=0';

		$embed = wp_remote_get(
			$embed_url,
			array(
				'timeout' => 20,
				'headers' => array( 'User-Agent' => 'HPK-PanneauPocket-Connect/' . HPK_PP_VERSION ),
			)
		);

		if ( is_wp_error( $embed ) ) {
			return $embed;
		}

		$embed_html = wp_remote_retrieve_body( $embed );
		if ( ! preg_match( '#https://app\.panneaupocket\.com/ville/[0-9]+[^"\'?\s]+#', $embed_html, $match ) ) {
			return new WP_Error( 'hpk_pp_no_ville', __( 'Page publique de la commune introuvable.', 'hpk-panneaupocket' ) );
		}

		$ville_url = html_entity_decode( $match[0], ENT_QUOTES, 'UTF-8' );
		$ville     = wp_remote_get(
			$ville_url,
			array(
				'timeout' => 25,
				'headers' => array( 'User-Agent' => 'HPK-PanneauPocket-Connect/' . HPK_PP_VERSION ),
			)
		);

		if ( is_wp_error( $ville ) ) {
			return $ville;
		}

		$html = wp_remote_retrieve_body( $ville );
		if ( '' === $html ) {
			return new WP_Error( 'hpk_pp_empty', __( 'Réponse vide de PanneauPocket.', 'hpk-panneaupocket' ) );
		}

		return array(
			'html' => $html,
			'url'  => $ville_url,
		);
	}

	/**
	 * Extract event cards from the city HTML.
	 *
	 * @param string $html HTML.
	 * @param string $ville_url City page URL.
	 * @return array
	 */
	public static function parse_events( $html, $ville_url ) {
		return self::parse_signs( $html, $ville_url, 'event' );
	}

	/**
	 * Extract signs from the city HTML.
	 *
	 * @param string $html HTML.
	 * @param string $ville_url City page URL.
	 * @param string $kind event, news or all.
	 * @return array
	 */
	public static function parse_signs( $html, $ville_url, $kind = 'all' ) {
		$events = array();

		if ( ! class_exists( 'DOMDocument' ) ) {
			return $events;
		}

		$dom = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$xpath = new DOMXPath( $dom );
		$items = $xpath->query( '//div[contains(@class,"sign-carousel--item")]' );
		if ( ! $items ) {
			return $events;
		}

		foreach ( $items as $item ) {
			$preview = $xpath->query( './/div[contains(@class,"sign-preview")]', $item )->item( 0 );
			if ( ! $preview ) {
				continue;
			}

			$class    = $preview->getAttribute( 'class' );
			$is_event = false !== strpos( $class, 'sign-preview--event' );
			$is_alert = false !== strpos( $class, 'sign-preview--alert' );
			if ( 'event' === $kind && ! $is_event ) {
				continue;
			}
			if ( 'news' === $kind && ( $is_event || $is_alert ) ) {
				continue;
			}

			$title_node = $xpath->query( './/div[contains(@class,"sign-preview__content")]/div[contains(@class,"title")]', $item )->item( 0 );
			$date_node  = $xpath->query( './/span[contains(@class,"date")]', $item )->item( 0 );
			$content    = $xpath->query( './/div[contains(@class,"content")]', $item )->item( 0 );

			$title = $title_node ? trim( $title_node->textContent ) : '';
			if ( '' === $title ) {
				continue;
			}

			$date_label = $date_node ? trim( preg_replace( '/\s+/u', ' ', $date_node->textContent ) ) : '';
			$date       = self::parse_date( $date_label );
			$date_parts = self::date_parts( $date );

			$id = '';
			if ( preg_match( '/panneau=(\d+)/', $dom->saveHTML( $item ), $id_match ) ) {
				$id = $id_match[1];
			} elseif ( $item->hasAttribute( 'data-id' ) ) {
				$id = preg_replace( '/\D+/', '', $item->getAttribute( 'data-id' ) );
			}
			if ( '' === $id ) {
				$id = (string) sprintf( '%u', crc32( $title . '|' . $date ) );
			}

			$image = '';
			if ( $content ) {
				$img = $xpath->query( './/img', $content )->item( 0 );
				if ( $img && $img->getAttribute( 'src' ) ) {
					$image = $img->getAttribute( 'src' );
				}
			}

			$html = '';
			$text = '';
			if ( $content ) {
				$skip_title = true;
				foreach ( $content->childNodes as $child ) {
					if ( $skip_title ) {
						if ( XML_TEXT_NODE === $child->nodeType && '' === trim( $child->textContent ) ) {
							continue;
						}
						$label = self::normalize_label( $child->textContent );
						$same  = function_exists( 'mb_strtolower' )
							? mb_strtolower( $label ) === mb_strtolower( $title )
							: strtolower( $label ) === strtolower( $title );
						$skip_title = false;
						if ( $same ) {
							continue;
						}
					}
					$html .= $dom->saveHTML( $child );
				}
				$html = preg_replace( '/<a[^>]*>\s*<img[^>]*>\s*<\/a>/i', '', $html );
				$html = preg_replace( '/<img[^>]*>/i', '', $html );
				$html = wp_kses_post( $html );
				$text = self::html_to_text( $html );
			}

			$events[] = array(
				'id'         => $id,
				'title'      => $title,
				'date'       => $date,
				'day'        => $date_parts['day'],
				'month'      => $date_parts['month'],
				'date_label' => $date_label,
				'excerpt'    => $text,
				'html'       => $html,
				'image'      => $image,
				'url'        => $id ? add_query_arg( 'panneau', $id, $ville_url ) : $ville_url,
			);
		}

		return $events;
	}

	/**
	 * Shorten text without removing line breaks.
	 *
	 * @param string $text Text.
	 * @param int    $length Max characters.
	 * @return string
	 */
	public static function trim_text( $text, $length ) {
		$text   = (string) $text;
		$length = absint( $length );
		$len    = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		if ( $length < 1 || $len <= $length ) {
			return $text;
		}

		$cut = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $length ) : substr( $text, 0, $length );
		$cut = preg_replace( '/\s+\S*$/u', '', $cut );

		return rtrim( $cut ) . '…';
	}

	/**
	 * Drop a repeated title from the article body.
	 *
	 * @param string $html Article HTML.
	 * @param string $title Article title.
	 * @return string
	 */
	public static function strip_repeated_title( $html, $title ) {
		$needle = self::fold_label( $title );
		if ( '' === $needle || '' === trim( (string) $html ) ) {
			return $html;
		}

		$quoted        = preg_quote( $title, '/' );
		$stripped_lead = false;
		$html          = preg_replace_callback(
			'/<(p|div|li|h[1-6])\b[^>]*>.*?<\/\1>/is',
			static function ( $match ) use ( $needle, $quoted, &$stripped_lead ) {
				$text = HPK_PP_Agenda::fold_label( wp_strip_all_tags( $match[0] ) );
				$text = preg_replace( '/^[^\p{L}\p{N}]+/u', '', $text );
				if ( $text === $needle ) {
					return '';
				}
				if ( ! $stripped_lead && 0 === strpos( (string) $text, $needle ) ) {
					$stripped_lead = true;
					return preg_replace( '/' . $quoted . '/iu', '', $match[0], 1 );
				}
				return $match[0];
			},
			$html
		);

		if ( ! $stripped_lead ) {
			$html = preg_replace(
				'/^(\s*(?:<[^>]+>\s*)*)' . $quoted . '\b/iu',
				'$1',
				$html,
				1
			);
		}

		return trim( (string) $html );
	}

	/**
	 * Lowercase label used to compare a title with body text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function fold_label( $text ) {
		$text = self::normalize_label( wp_strip_all_tags( (string) $text ) );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	}

	/**
	 * Collapse whitespace for a title comparison.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function normalize_label( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( $text );
	}

	/**
	 * Plain text that keeps paragraph breaks.
	 *
	 * @param string $html HTML content.
	 * @return string
	 */
	private static function html_to_text( $html ) {
		$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$html = preg_replace( '/<\/(p|div|li|h[1-6]|tr)\s*>/i', "\n", $html );
		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( "/[ \t]+/u", ' ', $text );
		$text = preg_replace( "/ *\n */u", "\n", $text );
		$text = preg_replace( "/\n{3,}/u", "\n\n", $text );

		return trim( $text );
	}

	/**
	 * First d/m/Y date in a label.
	 *
	 * @param string $label Date label.
	 * @return string Y-m-d or empty.
	 */
	private static function parse_date( $label ) {
		if ( preg_match( '/(\d{2})\/(\d{2})\/(\d{4})/', $label, $m ) ) {
			return $m[3] . '-' . $m[2] . '-' . $m[1];
		}
		return '';
	}

	/**
	 * Day and month label for a Y-m-d date.
	 *
	 * @param string $date Y-m-d.
	 * @return array{day:string,month:string}
	 */
	private static function date_parts( $date ) {
		$empty = array(
			'day'   => '',
			'month' => '',
		);
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $date, $m ) ) {
			return $empty;
		}

		$names = array( 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc' );
		$index = (int) $m[2] - 1;
		if ( ! isset( $names[ $index ] ) ) {
			return $empty;
		}

		return array(
			'day'   => (string) (int) $m[3],
			'month' => $names[ $index ] . ', ' . $m[1],
		);
	}
}
