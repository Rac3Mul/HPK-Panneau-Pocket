<?php
/**
 * Shortcode "À la une" for PanneauPocket news.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Une
 */
class HPK_PP_Une {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'panneaupocket_une', array( $this, 'render_shortcode' ) );
		add_action( 'init', array( $this, 'maybe_flush' ), 99 );
	}

	/**
	 * Drop the old automatic /a-la-une/ routes so a real page can use that slug.
	 */
	public function maybe_flush() {
		if ( '1.3.19' === get_option( 'hpk_pp_une_rewrite' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'hpk_pp_une_rewrite', '1.3.19', false );
	}

	/**
	 * Page that contains the shortcode.
	 *
	 * @return string
	 */
	public static function page_url() {
		$page_id = (int) get_option( 'hpk_pp_une_page_id' );
		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}

		$found = self::find_page_id();
		if ( $found > 0 ) {
			update_option( 'hpk_pp_une_page_id', $found, false );
			$url = get_permalink( $found );
			if ( $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Look for the shortcode in a published page.
	 *
	 * @return int
	 */
	private static function find_page_id() {
		global $wpdb;

		$like = '%panneaupocket_une%';
		$id   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'page' AND post_content LIKE %s ORDER BY ID DESC LIMIT 1",
				$like
			)
		);
		if ( $id ) {
			return (int) $id;
		}

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
				$like
			)
		);

		return $id ? (int) $id : 0;
	}

	/**
	 * Shortcode output. Stays inside the page the editor creates.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );

		$page_id = (int) get_queried_object_id();
		if ( $page_id > 0 && is_singular() ) {
			update_option( 'hpk_pp_une_page_id', $page_id, false );
		}

		$base = $page_id > 0 ? get_permalink( $page_id ) : self::page_url();
		if ( ! $base ) {
			$base = self::page_url();
		}

		$news  = HPK_PP_Agenda::get_news( array( 'limit' => 60 ) );
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$actu  = isset( $_GET['actu'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_GET['actu'] ) ) : '';
		$item  = '' !== $actu ? HPK_PP_Agenda::find_news( $actu ) : null;

		$filtered = $news;
		if ( '' !== $query && ! $item ) {
			$needle   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $query ) : strtolower( $query );
			$filtered = array_values(
				array_filter(
					$news,
					static function ( $row ) use ( $needle ) {
						$hay = ( $row['title'] ?? '' ) . ' ' . ( $row['excerpt'] ?? '' );
						$hay = function_exists( 'mb_strtolower' ) ? mb_strtolower( $hay ) : strtolower( $hay );
						return false !== strpos( $hay, $needle );
					}
				)
			);
		}

		$recent = $news;
		if ( $item ) {
			$current = (string) $item['id'];
			$recent  = array_values(
				array_filter(
					$news,
					static function ( $row ) use ( $current ) {
						return (string) ( $row['id'] ?? '' ) !== $current;
					}
				)
			);
		}
		$recent = array_slice( $recent, 0, 5 );

		ob_start();
		include HPK_PP_PATH . 'templates/une.php';
		return (string) ob_get_clean();
	}
}
