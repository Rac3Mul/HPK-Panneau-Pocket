<?php
/**
 * Elementor widgets loader.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Elementor
 */
class HPK_PP_Elementor {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Elementor|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Elementor
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
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register Elementor widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'hpk-panneaupocket',
			array(
				'title' => __( 'PanneauPocket', 'hpk-panneaupocket' ),
				'icon'  => 'fa fa-bullhorn',
			)
		);
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once HPK_PP_PATH . 'includes/elementor/widget-iframe.php';
		require_once HPK_PP_PATH . 'includes/elementor/widget-news.php';

		$widgets_manager->register( new HPK_PP_Elementor_Widget_Iframe() );
		$widgets_manager->register( new HPK_PP_Elementor_Widget_News() );
	}
}
