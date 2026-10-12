<?php
/**
 * Public "À la une" pages for PanneauPocket news.
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
		add_action( 'init', array( $this, 'register_routes' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'render' ) );
		add_action( 'init', array( $this, 'maybe_flush' ), 99 );
	}

	/**
	 * Pretty URLs.
	 */
	public function register_routes() {
		add_rewrite_rule( '^a-la-une/([0-9]+)/?$', 'index.php?hpk_pp_une=$matches[1]', 'top' );
		add_rewrite_rule( '^a-la-une/?$', 'index.php?hpk_pp_une=liste', 'top' );
	}

	/**
	 * Query var.
	 *
	 * @param array $vars Vars.
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'hpk_pp_une';
		return $vars;
	}

	/**
	 * Flush once after the update so the new URLs work.
	 */
	public function maybe_flush() {
		if ( '1.3.18' === get_option( 'hpk_pp_une_rewrite' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'hpk_pp_une_rewrite', '1.3.18', false );
	}

	/**
	 * Render the archive or a single news page.
	 */
	public function render() {
		$une = get_query_var( 'hpk_pp_une' );
		if ( '' === $une || false === $une || null === $une ) {
			return;
		}

		$news  = HPK_PP_Agenda::get_news( array( 'limit' => 60 ) );
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$item  = ( 'liste' === $une ) ? null : HPK_PP_Agenda::find_news( $une );

		if ( 'liste' !== $une && ! $item ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		$filtered = $news;
		if ( '' !== $query ) {
			$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $query ) : strtolower( $query );
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

		$title = $item ? $item['title'] : __( 'À la une', 'hpk-panneaupocket' );
		if ( '' !== $query && ! $item ) {
			$title = sprintf( __( 'Recherche : %s', 'hpk-panneaupocket' ), $query );
		}

		global $wp_query;
		$wp_query->is_404     = false;
		$wp_query->is_page    = true;
		$wp_query->is_singular = true;
		status_header( 200 );

		add_filter(
			'pre_get_document_title',
			static function () use ( $title ) {
				return $title . ' – ' . get_bloginfo( 'name' );
			}
		);
		add_filter(
			'document_title_parts',
			static function ( $parts ) use ( $title ) {
				$parts['title'] = $title;
				return $parts;
			}
		);

		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );

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

		include HPK_PP_PATH . 'templates/une.php';
		exit;
	}
}
