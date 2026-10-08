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

		$cache_key = 'hpk_pp_agenda_v3_' . $city_id;
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
	 * Fetch the public city page and store events.
	 *
	 * @param string $city_id City ID.
	 * @return array|WP_Error
	 */
	public static function fetch_and_store( $city_id ) {
		$events = self::fetch_events( $city_id );
		if ( is_wp_error( $events ) ) {
			return $events;
		}

		set_transient( 'hpk_pp_agenda_v3_' . $city_id, $events, self::CACHE_TTL );
		update_option( 'hpk_pp_agenda_backup_v3_' . $city_id, $events, false );

		return $events;
	}

	/**
	 * Last successful snapshot.
	 *
	 * @param string $city_id City ID.
	 * @return array
	 */
	private static function get_backup( $city_id ) {
		$backup = get_option( 'hpk_pp_agenda_backup_v3_' . $city_id, array() );
		return is_array( $backup ) ? $backup : array();
	}

	/**
	 * Download and parse events.
	 *
	 * @param string $city_id City ID.
	 * @return array|WP_Error
	 */
	public static function fetch_events( $city_id ) {
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

		return self::parse_events( $html, $ville_url );
	}

	/**
	 * Extract event cards from the city HTML.
	 *
	 * @param string $html HTML.
	 * @param string $ville_url City page URL.
	 * @return array
	 */
	public static function parse_events( $html, $ville_url ) {
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
			if ( ! $preview || false === strpos( $preview->getAttribute( 'class' ), 'sign-preview--event' ) ) {
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

			$id = '';
			if ( preg_match( '/panneau=(\d+)/', $dom->saveHTML( $item ), $id_match ) ) {
				$id = $id_match[1];
			} elseif ( $item->hasAttribute( 'data-id' ) ) {
				$id = $item->getAttribute( 'data-id' );
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
				foreach ( $content->childNodes as $child ) {
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
}
