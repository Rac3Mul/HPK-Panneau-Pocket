<?php
/**
 * Elementor widget — PanneauPocket public news carousel.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Elementor_Widget_Actus
 */
class HPK_PP_Elementor_Widget_Actus extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hpk_panneaupocket_actus';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'PanneauPocket Actualités', 'hpk-panneaupocket' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-carousel';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'hpk-panneaupocket', 'general' );
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'panneaupocket', 'actualité', 'news', 'carousel', 'article' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Actualités', 'hpk-panneaupocket' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Nombre d\'actualités', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 8,
				'min'     => 1,
				'max'     => 24,
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Texte du bouton', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Plus de détails', 'hpk-panneaupocket' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo HPK_PP_Shortcodes::instance()->render_actus(
			array(
				'limit'       => (string) $settings['limit'],
				'button_text' => $settings['button_text'],
			)
		);
	}
}
