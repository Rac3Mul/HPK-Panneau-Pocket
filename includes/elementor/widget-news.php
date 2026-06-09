<?php
/**
 * Elementor widget — PanneauPocket actualités.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Elementor_Widget_News
 */
class HPK_PP_Elementor_Widget_News extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hpk_panneaupocket_news';
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
		return 'eicon-posts-grid';
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
	 * Keywords for search.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'panneaupocket', 'news', 'actualités', 'sign', 'panneau' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Contenu', 'hpk-panneaupocket' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Mise en page', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'    => __( 'Grille', 'hpk-panneaupocket' ),
					'list'    => __( 'Liste', 'hpk-panneaupocket' ),
					'compact' => __( 'Compact', 'hpk-panneaupocket' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Nombre d\'actualités', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->add_control(
			'per_page',
			array(
				'label'     => __( 'Par page (pagination)', 'hpk-panneaupocket' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 1,
				'max'       => 50,
				'condition' => array(
					'pagination' => 'yes',
				),
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'   => __( 'Longueur extrait', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 120,
				'min'     => 20,
				'max'     => 500,
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'        => __( 'Afficher la date', 'hpk-panneaupocket' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
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
			'show_type',
			array(
				'label'        => __( 'Afficher le type', 'hpk-panneaupocket' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'pagination',
			array(
				'label'        => __( 'Pagination', 'hpk-panneaupocket' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'hpk-panneaupocket' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'primary_color',
			array(
				'label'     => __( 'Couleur principale', 'hpk-panneaupocket' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => get_option( 'hpk_pp_color_primary', '#0066cc' ),
				'selectors' => array(
					'{{WRAPPER}}' => '--hpk-pp-primary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => __( 'Couleur du texte', 'hpk-panneaupocket' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => get_option( 'hpk_pp_color_text', '#333333' ),
				'selectors' => array(
					'{{WRAPPER}}' => '--hpk-pp-text: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		wp_enqueue_style( 'hpk-pp-frontend', HPK_PP_URL . 'assets/css/frontend.css', array(), HPK_PP_VERSION );

		$settings = $this->get_settings_for_display();

		echo HPK_PP_Shortcodes::get_news_html(
			array(
				'layout'         => $settings['layout'],
				'limit'          => (string) $settings['limit'],
				'per_page'       => (string) ( $settings['per_page'] ?: $settings['limit'] ),
				'show_date'      => ( 'yes' === $settings['show_date'] ) ? 'true' : 'false',
				'show_image'     => ( 'yes' === $settings['show_image'] ) ? 'true' : 'false',
				'show_type'      => ( 'yes' === $settings['show_type'] ) ? 'true' : 'false',
				'excerpt_length' => (string) $settings['excerpt_length'],
				'pagination'     => ( 'yes' === $settings['pagination'] ) ? 'true' : 'false',
			)
		);
	}
}
