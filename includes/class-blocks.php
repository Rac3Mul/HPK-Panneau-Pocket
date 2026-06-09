<?php
/**
 * Gutenberg blocks registration.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Blocks
 */
class HPK_PP_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Blocks
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
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register Gutenberg blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'hpk-pp-block-iframe-editor',
			HPK_PP_URL . 'blocks/iframe/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
			HPK_PP_VERSION,
			true
		);

		wp_register_script(
			'hpk-pp-block-news-editor',
			HPK_PP_URL . 'blocks/news/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
			HPK_PP_VERSION,
			true
		);

		register_block_type(
			HPK_PP_PATH . 'blocks/iframe',
			array(
				'render_callback' => array( $this, 'render_iframe_block' ),
				'editor_script'   => 'hpk-pp-block-iframe-editor',
			)
		);

		register_block_type(
			HPK_PP_PATH . 'blocks/news',
			array(
				'render_callback' => array( $this, 'render_news_block' ),
				'editor_script'   => 'hpk-pp-block-news-editor',
			)
		);
	}

	/**
	 * Render iframe block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_iframe_block( $attributes ) {
		return HPK_PP_Shortcodes::get_iframe_html(
			array(
				'mode'            => $attributes['mode'] ?? 'widget',
				'auto_navigation' => isset( $attributes['autoNavigation'] ) ? (string) $attributes['autoNavigation'] : '',
				'bg_color'        => $attributes['bgColor'] ?? '',
				'city_id'         => $attributes['cityId'] ?? '',
				'width'           => $attributes['width'] ?? '',
				'height'          => $attributes['height'] ?? '',
			)
		);
	}

	/**
	 * Render news block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_news_block( $attributes ) {
		return HPK_PP_Shortcodes::get_news_html(
			array(
				'limit'          => isset( $attributes['limit'] ) ? (string) $attributes['limit'] : '6',
				'per_page'       => isset( $attributes['perPage'] ) ? (string) $attributes['perPage'] : '6',
				'layout'         => $attributes['layout'] ?? 'grid',
				'show_date'      => ! empty( $attributes['showDate'] ) ? 'true' : 'false',
				'show_image'     => ! empty( $attributes['showImage'] ) ? 'true' : 'false',
				'show_type'      => ! empty( $attributes['showType'] ) ? 'true' : 'false',
				'excerpt_length' => isset( $attributes['excerptLength'] ) ? (string) $attributes['excerptLength'] : '120',
				'pagination'     => ! empty( $attributes['pagination'] ) ? 'true' : 'false',
			)
		);
	}
}
