<?php
/**
 * Shortcodes for PanneauPocket display.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Shortcodes
 */
class HPK_PP_Shortcodes {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Shortcodes
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_shortcode( 'panneaupocket_widget', array( $this, 'render_widget' ) );
		add_shortcode( 'panneaupocket_news', array( $this, 'render_news' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend() {
		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );
		$this->inject_css_vars();
	}

	/**
	 * Inject CSS custom properties from settings.
	 */
	public function inject_css_vars() {
		$css = sprintf(
			':root {
				--hpk-pp-primary: %s;
				--hpk-pp-secondary: %s;
				--hpk-pp-text: %s;
				--hpk-pp-button: %s;
			}',
			esc_attr( get_option( 'hpk_pp_color_primary', '#0066cc' ) ),
			esc_attr( get_option( 'hpk_pp_color_secondary', '#004499' ) ),
			esc_attr( get_option( 'hpk_pp_color_text', '#333333' ) ),
			esc_attr( get_option( 'hpk_pp_color_button', '#0066cc' ) )
		);
		wp_add_inline_style( 'hpk-pp-frontend', $css );
	}

	/**
	 * Render iframe widget shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_widget( $atts ) {
		return self::get_iframe_html( $atts );
	}

	/**
	 * Get iframe HTML (shared with widgets/blocks).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function get_iframe_html( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'mode'             => 'widget',
				'auto_navigation'  => '',
				'bg_color'         => '',
				'city_id'          => '',
				'width'            => '',
				'height'           => '',
			),
			$atts,
			'panneaupocket_widget'
		);

		$mode = in_array( $atts['mode'], array( 'widget', 'widgetTv' ), true ) ? $atts['mode'] : 'widget';
		$auto = '' !== $atts['auto_navigation'] ? absint( $atts['auto_navigation'] ) : absint( get_option( 'hpk_pp_floating_auto_nav', 0 ) );

		$embed_args = array(
			'city_id'         => ! empty( $atts['city_id'] ) ? $atts['city_id'] : get_option( 'hpk_pp_city_id', '' ),
			'mode'            => $mode,
			'auto_navigation' => $auto,
			'bg_color'        => ! empty( $atts['bg_color'] ) ? $atts['bg_color'] : get_option( 'hpk_pp_floating_bg_color', 'ffffff' ),
		);

		$url = HPK_PP_Api_Client::instance()->get_embed_url( $embed_args );
		if ( empty( $url ) ) {
			return '<p class="hpk-pp-error">' . esc_html__( 'City ID manquant. Configurez PanneauPocket > Réglages API.', 'hpk-panneaupocket' ) . '</p>';
		}

		if ( 'widgetTv' === $mode ) {
			$style = 'position:relative;height:100%;width:100%;z-index:10;border:none;';
		} else {
			$w = ! empty( $atts['width'] ) ? $atts['width'] : '330px';
			$h = ! empty( $atts['height'] ) ? $atts['height'] : '518px';
			$style = 'height:' . esc_attr( $h ) . ';width:' . esc_attr( $w ) . ';border:none;';
		}

		$iframe_mode = $mode;

		ob_start();
		include HPK_PP_PATH . 'templates/iframe-widget.php';
		return ob_get_clean();
	}

	/**
	 * Render news shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_news( $atts ) {
		return self::get_news_html( $atts );
	}

	/**
	 * Get news HTML (shared with widgets/blocks).
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function get_news_html( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'limit'          => '6',
				'per_page'       => '6',
				'layout'         => 'grid',
				'show_date'      => 'true',
				'show_image'     => 'true',
				'show_type'      => 'false',
				'excerpt_length' => '120',
				'pagination'     => 'false',
			),
			$atts,
			'panneaupocket_news'
		);

		$layout = in_array( $atts['layout'], array( 'grid', 'list', 'compact' ), true ) ? $atts['layout'] : 'grid';
		$paged  = max( 1, absint( get_query_var( 'paged' ) ?: ( $_GET['hpk_pp_page'] ?? 1 ) ) );
		$per_page = 'true' === $atts['pagination'] ? absint( $atts['per_page'] ) : absint( $atts['limit'] );

		$query_args = array(
			'post_type'      => array( 'post', 'hpk_pp_sign' ),
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => '_panneaupocket_enabled',
					'value' => '1',
				),
				array(
					'key'     => '_panneaupocket_last_status',
					'value'   => array( 'envoye', 'modifie' ),
					'compare' => 'IN',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $query_args );

		$template_args = array(
			'query'          => $query,
			'show_date'      => filter_var( $atts['show_date'], FILTER_VALIDATE_BOOLEAN ),
			'show_image'     => filter_var( $atts['show_image'], FILTER_VALIDATE_BOOLEAN ),
			'show_type'      => filter_var( $atts['show_type'], FILTER_VALIDATE_BOOLEAN ),
			'excerpt_length' => absint( $atts['excerpt_length'] ),
			'pagination'     => filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN ),
			'paged'          => $paged,
		);

		$template = HPK_PP_PATH . 'templates/news-' . $layout . '.php';
		if ( ! file_exists( $template ) ) {
			$template = HPK_PP_PATH . 'templates/news-grid.php';
		}

		ob_start();
		include $template;
		wp_reset_postdata();
		return ob_get_clean();
	}
}
