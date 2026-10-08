<?php
/**
 * Elementor widget — PanneauPocket agenda.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Elementor_Widget_Agenda
 */
class HPK_PP_Elementor_Widget_Agenda extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hpk_panneaupocket_agenda';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'PanneauPocket Agenda', 'hpk-panneaupocket' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
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
		return array( 'panneaupocket', 'agenda', 'événement', 'calendrier' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Agenda', 'hpk-panneaupocket' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Mise en page', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list' => __( 'Liste', 'hpk-panneaupocket' ),
					'grid' => __( 'Grille', 'hpk-panneaupocket' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Nombre d\'événements', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 30,
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => __( 'Afficher l\'image', 'hpk-panneaupocket' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => __( 'Afficher le texte', 'hpk-panneaupocket' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'   => __( 'Longueur du texte', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 420,
				'min'     => 80,
				'max'     => 800,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );

		$settings = $this->get_settings_for_display();

		echo HPK_PP_Shortcodes::get_agenda_html(
			array(
				'layout'         => $settings['layout'],
				'limit'          => (string) $settings['limit'],
				'show_image'     => ( 'yes' === $settings['show_image'] ) ? 'true' : 'false',
				'show_excerpt'   => ( 'yes' === $settings['show_excerpt'] ) ? 'true' : 'false',
				'excerpt_length' => (string) $settings['excerpt_length'],
			)
		);
	}
}
