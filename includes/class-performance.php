<?php
/**
 * Front-end loading trims that do not change the layout.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Performance
 */
class HPK_PP_Performance {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'disable_emoji' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_unused_calendar' ), 100 );
		add_filter( 'style_loader_tag', array( $this, 'defer_google_fonts' ), 10, 2 );
		add_filter( 'wp_resource_hints', array( $this, 'resource_hints' ), 10, 2 );
	}

	/**
	 * Emoji scripts are unused on a municipal site.
	 */
	public function disable_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	/**
	 * The Events Calendar prints single-event CSS on every page.
	 */
	public function dequeue_unused_calendar() {
		if ( is_admin() || $this->is_events_view() ) {
			return;
		}

		$styles = array(
			'tribe-events-v2-single-skeleton',
			'tribe-events-v2-single-skeleton-full',
			'tec-events-elementor-widgets-base-styles',
		);
		foreach ( $styles as $handle ) {
			wp_dequeue_style( $handle );
		}

		if ( ! is_singular() ) {
			wp_deregister_script( 'wp-embed' );
		}
	}

	/**
	 * Whether the current request is an events screen.
	 *
	 * @return bool
	 */
	private function is_events_view() {
		if ( is_singular( 'tribe_events' ) || is_post_type_archive( 'tribe_events' ) ) {
			return true;
		}
		$post_type = get_query_var( 'post_type' );
		if ( 'tribe_events' === $post_type || ( is_array( $post_type ) && in_array( 'tribe_events', $post_type, true ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Keep Google Fonts from blocking the first paint.
	 *
	 * @param string $html Tag.
	 * @param string $handle Handle.
	 * @return string
	 */
	public function defer_google_fonts( $html, $handle ) {
		unset( $handle );
		if ( false === strpos( $html, 'fonts.googleapis.com' ) || false !== strpos( $html, 'onload=' ) ) {
			return $html;
		}
		$swapped = preg_replace( "/media=(['\"])all\\1/", "media='print' onload=\"this.media='all'\"", $html, 1 );
		return is_string( $swapped ) ? $swapped : $html;
	}

	/**
	 * Open connections used by the news images and the font files.
	 *
	 * @param array  $urls URLs.
	 * @param string $relation Relation type.
	 * @return array
	 */
	public function resource_hints( $urls, $relation ) {
		if ( 'preconnect' !== $relation ) {
			return $urls;
		}
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
		$urls[] = 'https://panneau-pocket.s3.gra.perf.cloud.ovh.net';
		return $urls;
	}

}
