<?php
/**
 * Floating widget for front-end.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Floating_Widget
 */
class HPK_PP_Floating_Widget {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Floating_Widget|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Floating_Widget
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_footer', array( $this, 'render' ) );
		add_action( 'wp_footer', array( $this, 'maybe_debug_comment' ), 999 );
	}

	/**
	 * Enqueue floating widget assets.
	 */
	public function enqueue() {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );
		wp_enqueue_script( 'hpk-pp-frontend', HPK_PP_URL . 'assets/js/frontend.js', array(), HPK_PP_VERSION, true );

		wp_localize_script(
			'hpk-pp-frontend',
			'hpkPpFront',
			array(
				'rememberClosed' => '1' === get_option( 'hpk_pp_floating_remember_closed', '1' ),
			)
		);

		HPK_PP_Shortcodes::instance()->inject_css_vars();

		$position = get_option( 'hpk_pp_floating_position', 'bottom-right' );
		$width    = get_option( 'hpk_pp_floating_width', '330' );
		$height   = get_option( 'hpk_pp_floating_height', '518' );
		$w_mobile = get_option( 'hpk_pp_floating_width_mobile', '92vw' );
		$h_mobile = get_option( 'hpk_pp_floating_height_mobile', '75vh' );
		$btn_color = get_option( 'hpk_pp_color_button', '#0066cc' );
		$animations = '1' === get_option( 'hpk_pp_animations', '1' );

		if ( '' === (string) $width ) {
			$width = '330';
		}
		if ( '' === (string) $height ) {
			$height = '518';
		}

		$css = sprintf(
			'.hpk-pp-floating { --hpk-pp-panel-width: %spx; --hpk-pp-panel-height: %spx; --hpk-pp-panel-width-mobile: %s; --hpk-pp-panel-height-mobile: %s; --hpk-pp-button: %s; }
			.hpk-pp-floating--bottom-left { left: 20px; right: auto; }
			.hpk-pp-floating--bottom-right { right: 20px; left: auto; }
			%s',
			esc_attr( $width ),
			esc_attr( $height ),
			esc_attr( $w_mobile ),
			esc_attr( $h_mobile ),
			esc_attr( $btn_color ),
			$animations ? '' : '.hpk-pp-floating * { transition: none !important; animation: none !important; }'
		);
		wp_add_inline_style( 'hpk-pp-frontend', $css );
	}

	/**
	 * Diagnostic: why the widget is hidden on the front-end.
	 *
	 * @return array{will_render:bool,reasons:string[],settings:array<string,string>}
	 */
	public static function get_render_status() {
		$reasons  = array();
		$enabled  = get_option( 'hpk_pp_floating_enabled', '0' );
		$city_id  = trim( (string) get_option( 'hpk_pp_city_id', '' ) );
		$mobile   = get_option( 'hpk_pp_floating_mobile', '1' );
		$excluded = trim( (string) get_option( 'hpk_pp_floating_excluded_pages', '' ) );

		if ( '1' !== $enabled ) {
			$reasons[] = __( 'Widget flottant non activé (case à cocher + Enregistrer sur cette page).', 'hpk-panneaupocket' );
		}

		if ( '' === $city_id ) {
			$reasons[] = __( 'City ID vide — renseignez-le dans PanneauPocket → Réglages API puis Enregistrer.', 'hpk-panneaupocket' );
		}

		if ( wp_is_mobile() && '1' !== $mobile ) {
			$reasons[] = __( 'Affichage mobile désactivé.', 'hpk-panneaupocket' );
		}

		if ( '' !== $excluded && is_page() ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $excluded ) ) );
			if ( in_array( get_queried_object_id(), $ids, true ) ) {
				$reasons[] = sprintf(
					/* translators: %d: page ID */
					__( 'Page actuelle exclue (ID %d).', 'hpk-panneaupocket' ),
					get_queried_object_id()
				);
			}
		}

		return array(
			'will_render' => empty( $reasons ),
			'reasons'     => $reasons,
			'settings'    => array(
				'enabled'  => $enabled,
				'city_id'  => $city_id,
				'mobile'   => $mobile,
				'excluded' => $excluded,
			),
		);
	}

	/**
	 * HTML comment for admins when widget is blocked (view source / DevTools).
	 */
	public function maybe_debug_comment() {
		if ( is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = self::get_render_status();
		if ( $status['will_render'] ) {
			echo "\n<!-- HPK PanneauPocket: widget flottant actif -->\n";
			return;
		}

		echo "\n<!-- HPK PanneauPocket BLOQUÉ: " . esc_html( implode( ' | ', $status['reasons'] ) ) . " -->\n";
	}

	/**
	 * Check if floating widget should render.
	 *
	 * @return bool
	 */
	private function should_render() {
		if ( is_admin() ) {
			return false;
		}

		return self::get_render_status()['will_render'];
	}

	/**
	 * Render floating widget in footer.
	 */
	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}

		$mode  = get_option( 'hpk_pp_floating_mode', 'widget' );
		$auto  = absint( get_option( 'hpk_pp_floating_auto_nav', 0 ) );
		$bg    = get_option( 'hpk_pp_floating_bg_color', 'ffffff' );
		$position = get_option( 'hpk_pp_floating_position', 'bottom-right' );

		$url = HPK_PP_Api_Client::instance()->get_embed_url(
			array(
				'mode'            => $mode,
				'auto_navigation' => $auto,
				'bg_color'        => $bg,
			)
		);

		$logo = HPK_PP_URL . 'assets/img/logo-panneaupocket.svg';
		if ( '1' === get_option( 'hpk_pp_use_custom_logo' ) ) {
			$custom = get_option( 'hpk_pp_custom_logo', '' );
			if ( $custom ) {
				$logo = esc_url( $custom );
			}
		}

		include HPK_PP_PATH . 'templates/floating-widget.php';
	}
}
