<?php
/**
 * Elementor widget — PanneauPocket iframe.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Elementor_Widget_Iframe
 */
class HPK_PP_Elementor_Widget_Iframe extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hpk_panneaupocket_iframe';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'PanneauPocket Widget', 'hpk-panneaupocket' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-frame-expand';
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
		return array( 'panneaupocket', 'iframe', 'widget', 'sign', 'panneau' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Paramètres', 'hpk-panneaupocket' ),
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Mode', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'widget',
				'options' => array(
					'widget'   => 'widget',
					'widgetTv' => 'widgetTv',
				),
			)
		);

		$this->add_control(
			'auto_navigation',
			array(
				'label'   => __( 'Auto-navigation (secondes)', 'hpk-panneaupocket' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '0',
				'options' => array(
					'0'  => '0',
					'10' => '10',
					'15' => '15',
					'30' => '30',
				),
			)
		);

		$this->add_control(
			'bg_color',
			array(
				'label'       => __( 'Couleur de fond (widgetTv)', 'hpk-panneaupocket' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'ffffff',
				'placeholder' => 'ffffff',
				'description' => __( 'Hexadécimal sans #', 'hpk-panneaupocket' ),
				'condition'   => array(
					'mode' => 'widgetTv',
				),
			)
		);

		$this->add_control(
			'city_id',
			array(
				'label'       => __( 'City ID', 'hpk-panneaupocket' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => get_option( 'hpk_pp_city_id', '' ),
				'description' => __( 'Laisser vide pour utiliser le City ID des réglages.', 'hpk-panneaupocket' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_dimensions',
			array(
				'label' => __( 'Dimensions', 'hpk-panneaupocket' ),
			)
		);

		$this->add_responsive_control(
			'iframe_width',
			array(
				'label'      => __( 'Largeur', 'hpk-panneaupocket' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 200, 'max' => 1200 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 330,
				),
				'selectors'  => array(
					'{{WRAPPER}} .hpk-pp-iframe-wrap iframe' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'iframe_height',
			array(
				'label'      => __( 'Hauteur', 'hpk-panneaupocket' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 200, 'max' => 1200 ),
					'vh' => array( 'min' => 20, 'max' => 100 ),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 518,
				),
				'selectors'  => array(
					'{{WRAPPER}} .hpk-pp-iframe-wrap iframe' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$width  = '';
		$height = '';

		if ( ! empty( $settings['iframe_width']['size'] ) ) {
			$width = $settings['iframe_width']['size'] . $settings['iframe_width']['unit'];
		}
		if ( ! empty( $settings['iframe_height']['size'] ) ) {
			$height = $settings['iframe_height']['size'] . $settings['iframe_height']['unit'];
		}

		echo HPK_PP_Shortcodes::get_iframe_html(
			array(
				'mode'            => $settings['mode'],
				'auto_navigation' => $settings['auto_navigation'],
				'bg_color'        => $settings['bg_color'],
				'city_id'         => $settings['city_id'],
				'width'           => $width,
				'height'          => $height,
			)
		);
	}
}
