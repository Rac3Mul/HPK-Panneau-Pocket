<?php
/**
 * Admin UI helpers — modern layout components.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Admin_UI
 */
class HPK_PP_Admin_UI {

	/**
	 * Resolve logo URL for previews.
	 *
	 * @return string
	 */
	public static function get_active_logo_url() {
		$use_custom = get_option( 'hpk_pp_use_custom_logo', '' );
		$custom     = get_option( 'hpk_pp_custom_logo', '' );

		if ( ( '1' === $use_custom || 1 === $use_custom ) && ! empty( $custom ) ) {
			return esc_url( $custom );
		}

		return HPK_PP_Image_Library::get_default_logo_url();
	}

	/**
	 * Page header.
	 *
	 * @param string $title Page title.
	 * @param string $subtitle Optional subtitle.
	 */
	public static function page_header( $title, $subtitle = '' ) {
		echo '<div class="hpk-pp-page-header">';
		echo '<h1 class="hpk-pp-page-header__title">' . esc_html( $title ) . '</h1>';
		if ( $subtitle ) {
			echo '<p class="hpk-pp-page-header__subtitle">' . esc_html( $subtitle ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Open split layout (form + aside).
	 */
	public static function layout_split_open() {
		echo '<div class="hpk-pp-layout-split">';
	}

	/**
	 * Open main column.
	 */
	public static function layout_main_open() {
		echo '<div class="hpk-pp-layout-split__main">';
	}

	/**
	 * Open aside column.
	 */
	public static function layout_aside_open() {
		echo '</div><aside class="hpk-pp-layout-split__aside">';
	}

	/**
	 * Close split layout.
	 */
	public static function layout_split_close() {
		echo '</aside></div>';
	}

	/**
	 * Open card.
	 *
	 * @param string $title Card title.
	 * @param string $class Extra class.
	 */
	public static function card_open( $title = '', $class = '' ) {
		$class = trim( 'hpk-pp-card ' . $class );
		echo '<section class="' . esc_attr( $class ) . '">';
		if ( $title ) {
			echo '<h2 class="hpk-pp-card__title">' . esc_html( $title ) . '</h2>';
		}
		echo '<div class="hpk-pp-card__body">';
	}

	/**
	 * Close card.
	 */
	public static function card_close() {
		echo '</div></section>';
	}

	/**
	 * Open fields grid.
	 */
	public static function fields_open() {
		echo '<div class="hpk-pp-fields">';
	}

	/**
	 * Close fields grid.
	 */
	public static function fields_close() {
		echo '</div>';
	}

	/**
	 * Color field row.
	 *
	 * @param string $name Field name.
	 * @param string $label Label.
	 * @param string $value Hex value.
	 * @param string $description Description.
	 */
	public static function field_color( $name, $label, $value, $description = '' ) {
		?>
		<div class="hpk-pp-field">
			<label class="hpk-pp-field__label" for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="hpk-pp-field__control">
				<input type="color" class="hpk-pp-input-color" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<span class="hpk-pp-color-swatch" style="background-color:<?php echo esc_attr( $value ); ?>"></span>
				<code class="hpk-pp-color-code"><?php echo esc_html( $value ); ?></code>
			</div>
			<?php if ( $description ) : ?>
				<p class="hpk-pp-field__help"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Toggle checkbox field.
	 *
	 * @param string $name Field name.
	 * @param string $label Label.
	 * @param mixed  $checked Current value.
	 * @param string $description Description.
	 */
	public static function field_toggle( $name, $label, $checked, $description = '' ) {
		?>
		<div class="hpk-pp-field hpk-pp-field--toggle">
			<label class="hpk-pp-toggle">
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0" />
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked, '1' ); ?> />
				<span class="hpk-pp-toggle__track" aria-hidden="true"></span>
				<span class="hpk-pp-toggle__label"><?php echo esc_html( $label ); ?></span>
			</label>
			<?php if ( $description ) : ?>
				<p class="hpk-pp-field__help"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Text / url / number field.
	 *
	 * @param string $type Input type.
	 * @param string $name Field name.
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $description Description.
	 * @param array  $attrs Extra attributes.
	 */
	public static function field_input( $type, $name, $label, $value, $description = '', $attrs = array() ) {
		$id = $attrs['id'] ?? $name;
		unset( $attrs['id'] );
		$attr_html = '';
		foreach ( $attrs as $key => $val ) {
			$attr_html .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $val ) );
		}
		?>
		<div class="hpk-pp-field">
			<label class="hpk-pp-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="hpk-pp-field__control">
				<input type="<?php echo esc_attr( $type ); ?>" class="hpk-pp-input regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo $attr_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
			</div>
			<?php if ( $description ) : ?>
				<p class="hpk-pp-field__help"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Select field.
	 *
	 * @param string $name Field name.
	 * @param string $label Label.
	 * @param array  $options Options value => label.
	 * @param string $selected Selected value.
	 * @param string $description Description.
	 */
	public static function field_select( $name, $label, $options, $selected, $description = '' ) {
		?>
		<div class="hpk-pp-field">
			<label class="hpk-pp-field__label" for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="hpk-pp-field__control">
				<select class="hpk-pp-select" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php if ( $description ) : ?>
				<p class="hpk-pp-field__help"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Logo preview panel for display settings.
	 */
	public static function render_logo_preview_panel() {
		$logo_url   = self::get_active_logo_url();
		$btn_color  = get_option( 'hpk_pp_color_button', '#ffffff' );
		$primary    = get_option( 'hpk_pp_color_primary', '#0066cc' );
		$use_custom = get_option( 'hpk_pp_use_custom_logo', '' );
		?>
		<div class="hpk-pp-preview-panel">
			<h2 class="hpk-pp-preview-panel__title"><?php esc_html_e( 'Aperçu en direct', 'hpk-panneaupocket' ); ?></h2>
			<p class="hpk-pp-preview-panel__hint"><?php esc_html_e( 'Visualisation du bouton flottant sur votre site.', 'hpk-panneaupocket' ); ?></p>

			<div class="hpk-pp-logo-preview" data-default-logo="<?php echo esc_url( HPK_PP_Image_Library::get_default_logo_url() ); ?>">
				<div class="hpk-pp-logo-preview__browser">
					<div class="hpk-pp-logo-preview__browser-bar">
						<span></span><span></span><span></span>
					</div>
					<div class="hpk-pp-logo-preview__browser-body">
						<div class="hpk-pp-logo-preview__page-line"></div>
						<div class="hpk-pp-logo-preview__page-line hpk-pp-logo-preview__page-line--short"></div>
						<button type="button" class="hpk-pp-logo-preview__btn" style="background:<?php echo esc_attr( $btn_color ); ?>">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="" width="32" height="32" />
						</button>
					</div>
				</div>

				<dl class="hpk-pp-logo-preview__meta">
					<div>
						<dt><?php esc_html_e( 'Logo actif', 'hpk-panneaupocket' ); ?></dt>
						<dd class="hpk-pp-logo-preview__source"><?php echo ( '1' === $use_custom ) ? esc_html__( 'Personnalisé', 'hpk-panneaupocket' ) : esc_html__( 'PanneauPocket (défaut)', 'hpk-panneaupocket' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Couleur bouton', 'hpk-panneaupocket' ); ?></dt>
						<dd data-preview="btn-color"><span class="hpk-pp-logo-preview__chip" style="background:<?php echo esc_attr( $btn_color ); ?>"></span> <span class="hpk-pp-logo-preview__value"><?php echo esc_html( $btn_color ); ?></span></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Couleur principale', 'hpk-panneaupocket' ); ?></dt>
						<dd data-preview="primary-color"><span class="hpk-pp-logo-preview__chip" style="background:<?php echo esc_attr( $primary ); ?>"></span> <span class="hpk-pp-logo-preview__value"><?php echo esc_html( $primary ); ?></span></dd>
					</div>
				</dl>
			</div>
		</div>
		<?php
	}

	/**
	 * Status banner.
	 *
	 * @param bool   $success Success state.
	 * @param string $title Title.
	 * @param string $message Message.
	 */
	public static function status_banner( $success, $title, $message = '' ) {
		$class = $success ? 'is-success' : 'is-warning';
		echo '<div class="hpk-pp-status-banner ' . esc_attr( $class ) . '">';
		echo '<strong>' . esc_html( $title ) . '</strong>';
		if ( $message ) {
			echo '<p>' . esc_html( $message ) . '</p>';
		}
		echo '</div>';
	}
}
