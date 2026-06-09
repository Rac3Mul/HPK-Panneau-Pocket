<?php
/**
 * Classic WordPress widgets.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Widgets
 */
class HPK_PP_Widgets {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Widgets|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Widgets
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
		add_action( 'widgets_init', array( $this, 'register' ) );
	}

	/**
	 * Register widgets.
	 */
	public function register() {
		register_widget( 'HPK_PP_Widget_Iframe' );
		register_widget( 'HPK_PP_Widget_News' );
	}
}

/**
 * Iframe widget class.
 */
class HPK_PP_Widget_Iframe extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'hpk_pp_widget_iframe',
			__( 'HPK PanneauPocket — Widget iframe', 'hpk-panneaupocket' ),
			array( 'description' => __( 'Affiche le widget PanneauPocket en iframe.', 'hpk-panneaupocket' ) )
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args Widget args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
		}

		echo HPK_PP_Shortcodes::get_iframe_html(
			array(
				'mode'            => $instance['mode'] ?? 'widget',
				'auto_navigation' => $instance['auto_navigation'] ?? '',
				'bg_color'        => $instance['bg_color'] ?? '',
				'city_id'         => $instance['city_id'] ?? '',
				'width'           => $instance['width'] ?? '',
				'height'          => $instance['height'] ?? '',
			)
		);

		echo $args['after_widget'];
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance.
	 */
	public function form( $instance ) {
		$title = $instance['title'] ?? '';
		$mode  = $instance['mode'] ?? 'widget';
		$auto  = $instance['auto_navigation'] ?? '0';
		$bg    = $instance['bg_color'] ?? '';
		$city  = $instance['city_id'] ?? '';
		$width = $instance['width'] ?? '';
		$height = $instance['height'] ?? '';
		?>
		<p>
			<label><?php esc_html_e( 'Titre :', 'hpk-panneaupocket' ); ?></label>
			<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label><?php esc_html_e( 'Mode :', 'hpk-panneaupocket' ); ?></label>
			<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<option value="widget" <?php selected( $mode, 'widget' ); ?>>widget</option>
				<option value="widgetTv" <?php selected( $mode, 'widgetTv' ); ?>>widgetTv</option>
			</select>
		</p>
		<p><label><?php esc_html_e( 'Auto-navigation :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'auto_navigation' ) ); ?>" value="<?php echo esc_attr( $auto ); ?>" /></p>
		<p><label><?php esc_html_e( 'bg_color :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'bg_color' ) ); ?>" value="<?php echo esc_attr( $bg ); ?>" /></p>
		<p><label><?php esc_html_e( 'City ID :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'city_id' ) ); ?>" value="<?php echo esc_attr( $city ); ?>" /></p>
		<p><label><?php esc_html_e( 'Largeur :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'width' ) ); ?>" value="<?php echo esc_attr( $width ); ?>" placeholder="330px" /></p>
		<p><label><?php esc_html_e( 'Hauteur :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'height' ) ); ?>" value="<?php echo esc_attr( $height ); ?>" placeholder="518px" /></p>
		<?php
	}

	/**
	 * Save widget.
	 *
	 * @param array $new New instance.
	 * @param array $old Old instance.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array(
			'title'           => sanitize_text_field( $new['title'] ?? '' ),
			'mode'            => sanitize_text_field( $new['mode'] ?? 'widget' ),
			'auto_navigation' => sanitize_text_field( $new['auto_navigation'] ?? '0' ),
			'bg_color'        => sanitize_text_field( $new['bg_color'] ?? '' ),
			'city_id'         => sanitize_text_field( $new['city_id'] ?? '' ),
			'width'           => sanitize_text_field( $new['width'] ?? '' ),
			'height'          => sanitize_text_field( $new['height'] ?? '' ),
		);
	}
}

/**
 * News widget class.
 */
class HPK_PP_Widget_News extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'hpk_pp_widget_news',
			__( 'HPK PanneauPocket — Actualités', 'hpk-panneaupocket' ),
			array( 'description' => __( 'Affiche les actualités WordPress envoyées à PanneauPocket.', 'hpk-panneaupocket' ) )
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args Widget args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
		}

		echo HPK_PP_Shortcodes::get_news_html(
			array(
				'limit'          => $instance['limit'] ?? '6',
				'per_page'       => $instance['per_page'] ?? '6',
				'layout'         => $instance['layout'] ?? 'grid',
				'show_date'      => $instance['show_date'] ?? 'true',
				'show_image'     => $instance['show_image'] ?? 'true',
				'show_type'      => $instance['show_type'] ?? 'false',
				'excerpt_length' => $instance['excerpt_length'] ?? '120',
				'pagination'     => $instance['pagination'] ?? 'false',
			)
		);

		echo $args['after_widget'];
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance.
	 */
	public function form( $instance ) {
		$fields = array(
			'title'          => $instance['title'] ?? '',
			'layout'         => $instance['layout'] ?? 'grid',
			'limit'          => $instance['limit'] ?? '6',
			'per_page'       => $instance['per_page'] ?? '6',
			'show_date'      => $instance['show_date'] ?? 'true',
			'show_image'     => $instance['show_image'] ?? 'true',
			'show_type'      => $instance['show_type'] ?? 'false',
			'excerpt_length' => $instance['excerpt_length'] ?? '120',
			'pagination'     => $instance['pagination'] ?? 'false',
		);
		?>
		<p><label><?php esc_html_e( 'Titre :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $fields['title'] ); ?>" /></p>
		<p>
			<label><?php esc_html_e( 'Layout :', 'hpk-panneaupocket' ); ?></label>
			<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
				<option value="grid" <?php selected( $fields['layout'], 'grid' ); ?>>grid</option>
				<option value="list" <?php selected( $fields['layout'], 'list' ); ?>>list</option>
				<option value="compact" <?php selected( $fields['layout'], 'compact' ); ?>>compact</option>
			</select>
		</p>
		<p><label><?php esc_html_e( 'Limit :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" value="<?php echo esc_attr( $fields['limit'] ); ?>" /></p>
		<p><label><?php esc_html_e( 'Per page :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'per_page' ) ); ?>" value="<?php echo esc_attr( $fields['per_page'] ); ?>" /></p>
		<p><label><?php esc_html_e( 'Excerpt length :', 'hpk-panneaupocket' ); ?></label><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'excerpt_length' ) ); ?>" value="<?php echo esc_attr( $fields['excerpt_length'] ); ?>" /></p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_date' ) ); ?>" value="true" <?php checked( $fields['show_date'], 'true' ); ?> /> <?php esc_html_e( 'Afficher la date', 'hpk-panneaupocket' ); ?></label></p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_image' ) ); ?>" value="true" <?php checked( $fields['show_image'], 'true' ); ?> /> <?php esc_html_e( 'Afficher l\'image', 'hpk-panneaupocket' ); ?></label></p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_type' ) ); ?>" value="true" <?php checked( $fields['show_type'], 'true' ); ?> /> <?php esc_html_e( 'Afficher le type', 'hpk-panneaupocket' ); ?></label></p>
		<p><label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'pagination' ) ); ?>" value="true" <?php checked( $fields['pagination'], 'true' ); ?> /> <?php esc_html_e( 'Pagination', 'hpk-panneaupocket' ); ?></label></p>
		<?php
	}

	/**
	 * Save widget.
	 *
	 * @param array $new New instance.
	 * @param array $old Old instance.
	 * @return array
	 */
	public function update( $new, $old ) {
		return array(
			'title'          => sanitize_text_field( $new['title'] ?? '' ),
			'layout'         => sanitize_text_field( $new['layout'] ?? 'grid' ),
			'limit'          => sanitize_text_field( $new['limit'] ?? '6' ),
			'per_page'       => sanitize_text_field( $new['per_page'] ?? '6' ),
			'show_date'      => ! empty( $new['show_date'] ) ? 'true' : 'false',
			'show_image'     => ! empty( $new['show_image'] ) ? 'true' : 'false',
			'show_type'      => ! empty( $new['show_type'] ) ? 'true' : 'false',
			'excerpt_length' => sanitize_text_field( $new['excerpt_length'] ?? '120' ),
			'pagination'     => ! empty( $new['pagination'] ) ? 'true' : 'false',
		);
	}
}
