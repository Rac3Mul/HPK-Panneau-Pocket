<?php
/**
 * Admin pages and settings.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Admin
 */
class HPK_PP_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Admin
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'pre_update_option_hpk_pp_environment', array( $this, 'sync_api_url_on_env_change' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hpk_pp_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'admin_post_hpk_pp_export_logs', array( $this, 'export_logs' ) );
		add_action( 'admin_post_hpk_pp_purge_logs', array( $this, 'purge_logs' ) );
		add_action( 'admin_post_hpk_pp_save_publication', array( $this, 'save_publication' ) );
	}

	/**
	 * Register admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'PanneauPocket', 'hpk-panneaupocket' ),
			__( 'PanneauPocket', 'hpk-panneaupocket' ),
			'manage_options',
			'hpk-pp-settings',
			array( $this, 'render_api_page' ),
			'dashicons-megaphone',
			30
		);

		add_submenu_page( 'hpk-pp-settings', __( 'Réglages API', 'hpk-panneaupocket' ), __( 'Réglages API', 'hpk-panneaupocket' ), 'manage_options', 'hpk-pp-settings', array( $this, 'render_api_page' ) );
		add_submenu_page( 'hpk-pp-settings', __( 'Affichage', 'hpk-panneaupocket' ), __( 'Affichage', 'hpk-panneaupocket' ), 'manage_options', 'hpk-pp-display', array( $this, 'render_display_page' ) );
		add_submenu_page( 'hpk-pp-settings', __( 'Widget flottant', 'hpk-panneaupocket' ), __( 'Widget flottant', 'hpk-panneaupocket' ), 'manage_options', 'hpk-pp-floating', array( $this, 'render_floating_page' ) );
		add_submenu_page( 'hpk-pp-settings', __( 'Shortcodes', 'hpk-panneaupocket' ), __( 'Shortcodes', 'hpk-panneaupocket' ), 'manage_options', 'hpk-pp-shortcodes', array( $this, 'render_shortcodes_page' ) );
		add_submenu_page( 'hpk-pp-settings', __( 'Publication', 'hpk-panneaupocket' ), __( 'Publication', 'hpk-panneaupocket' ), 'publish_posts', 'hpk-pp-publication', array( $this, 'render_publication_page' ) );
		add_submenu_page( 'hpk-pp-settings', __( 'Logs', 'hpk-panneaupocket' ), __( 'Logs', 'hpk-panneaupocket' ), 'manage_options', 'hpk-pp-logs', array( $this, 'render_logs_page' ) );
	}

	/**
	 * Register settings (separate groups per admin page to avoid cross-save wipes).
	 */
	public function register_settings() {
		$api_settings = array(
			'hpk_pp_environment',
			'hpk_pp_api_url',
			'hpk_pp_auto_send_on_publish',
			'hpk_pp_auto_update_on_save',
			'hpk_pp_log_retention_days',
		);

		foreach ( $api_settings as $setting ) {
			register_setting( 'hpk_pp_api_settings', $setting, array( 'sanitize_callback' => array( $this, 'sanitize_setting' ) ) );
		}

		register_setting(
			'hpk_pp_api_settings',
			'hpk_pp_city_id',
			array( 'sanitize_callback' => array( $this, 'sanitize_city_id' ) )
		);

		register_setting(
			'hpk_pp_api_settings',
			'hpk_pp_embed_url',
			array(
				'sanitize_callback' => array( $this, 'sanitize_embed_url' ),
				'default'           => 'https://app.panneaupocket.com',
			)
		);

		register_setting(
			'hpk_pp_api_settings',
			'hpk_pp_api_token_plain',
			array(
				'sanitize_callback' => array( $this, 'sanitize_token' ),
				'type'              => 'string',
			)
		);

		$display_settings = array(
			'hpk_pp_color_primary',
			'hpk_pp_color_secondary',
			'hpk_pp_color_text',
			'hpk_pp_color_button',
			'hpk_pp_custom_logo',
			'hpk_pp_use_custom_logo',
			'hpk_pp_animations',
			'hpk_pp_responsive_mobile',
		);

		foreach ( $display_settings as $setting ) {
			register_setting( 'hpk_pp_display_settings', $setting, array( 'sanitize_callback' => array( $this, 'sanitize_setting' ) ) );
		}

		$floating_settings = array(
			'hpk_pp_floating_enabled',
			'hpk_pp_floating_position',
			'hpk_pp_floating_mode',
			'hpk_pp_floating_width',
			'hpk_pp_floating_height',
			'hpk_pp_floating_width_mobile',
			'hpk_pp_floating_height_mobile',
			'hpk_pp_floating_auto_nav',
			'hpk_pp_floating_bg_color',
			'hpk_pp_floating_mobile',
			'hpk_pp_floating_excluded_pages',
			'hpk_pp_floating_remember_closed',
		);

		foreach ( $floating_settings as $setting ) {
			register_setting( 'hpk_pp_floating_settings', $setting, array( 'sanitize_callback' => array( $this, 'sanitize_setting' ) ) );
		}
	}

	/**
	 * Sync API URL when environment changes (if URL not customized).
	 *
	 * @param string $new New env.
	 * @param string $old Old env.
	 * @return string
	 */
	public function sync_api_url_on_env_change( $new, $old ) {
		if ( $new === $old ) {
			return $new;
		}
		$urls = array(
			'production' => 'https://gestion.panneaupocket.com',
			'staging'    => 'https://staging.gestion.panneaupocket.com',
		);
		if ( isset( $urls[ $new ] ) ) {
			update_option( 'hpk_pp_api_url', $urls[ $new ] );
		}
		return $new;
	}

	/**
	 * Output hidden + checkbox pair for Settings API.
	 *
	 * @param string $name Option name.
	 * @param string $checked Current value.
	 */
	private function checkbox_field( $name, $checked ) {
		printf(
			'<input type="hidden" name="%1$s" value="0" /><label><input type="checkbox" name="%1$s" value="1" %2$s /></label>',
			esc_attr( $name ),
			checked( $checked, '1', false )
		);
	}

	/**
	 * Sanitize City ID (numeric string).
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function sanitize_city_id( $value ) {
		return preg_replace( '/\D/', '', (string) $value );
	}

	/**
	 * Read option with fallback when stored value is empty string.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_stored_option( $key, $default = '' ) {
		$value = get_option( $key, $default );
		if ( '' === $value || false === $value ) {
			return $default;
		}
		return $value;
	}

	/**
	 * Sanitize generic setting.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public function sanitize_setting( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize embed URL — force app.* domain, never gestion.*.
	 *
	 * @param string $value URL.
	 * @return string
	 */
	public function sanitize_embed_url( $value ) {
		$value = esc_url_raw( trim( $value ) );
		if ( empty( $value ) || false !== strpos( $value, 'gestion.panneaupocket.com' ) ) {
			return 'https://app.panneaupocket.com';
		}
		return untrailingslashit( $value );
	}

	/**
	 * Sanitize and encrypt token on save.
	 *
	 * @param string $value Plain token.
	 * @return string
	 */
	public function sanitize_token( $value ) {
		$value = sanitize_text_field( $value );
		if ( ! empty( $value ) ) {
			HPK_PP_Api_Client::instance()->save_token( $value );
		}
		return '';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$is_plugin_page = strpos( $hook, 'hpk-pp' ) !== false || 'post.php' === $hook || 'post-new.php' === $hook;

		if ( ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style( 'hpk-pp-admin', HPK_PP_URL . 'assets/css/admin.css', array(), HPK_PP_VERSION );
		wp_enqueue_script( 'hpk-pp-admin', HPK_PP_URL . 'assets/js/admin.js', array( 'jquery' ), HPK_PP_VERSION, true );

		$admin_i18n = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hpk_pp_admin' ),
			'i18n'    => array(
				'testing'  => __( 'Test en cours…', 'hpk-panneaupocket' ),
				'sending'  => __( 'Envoi en cours…', 'hpk-panneaupocket' ),
				'success'  => __( 'Succès', 'hpk-panneaupocket' ),
				'error'    => __( 'Erreur', 'hpk-panneaupocket' ),
				'copied'   => __( 'Copié !', 'hpk-panneaupocket' ),
			),
		);

		wp_localize_script( 'hpk-pp-admin', 'hpkPpAdmin', $admin_i18n );

		$is_publication_page = ( false !== strpos( $hook, 'hpk-pp-publication' ) );

		if ( 'post.php' === $hook || 'post-new.php' === $hook || $is_publication_page ) {
			wp_enqueue_media();
		}

		if ( $is_publication_page ) {
			wp_enqueue_editor();
			wp_enqueue_script(
				'hpk-pp-publication',
				HPK_PP_URL . 'assets/js/publication.js',
				array( 'jquery', 'hpk-pp-admin' ),
				HPK_PP_VERSION,
				true
			);
			wp_localize_script(
				'hpk-pp-publication',
				'hpkPpPublication',
				array(
					'editorId'      => 'hpk_pp_publication_content',
					'communityName' => get_bloginfo( 'name' ),
					'cityId'        => get_option( 'hpk_pp_city_id', '' ),
					'logoUrl'       => HPK_PP_Image_Library::get_default_logo_url(),
					'emojis'        => array( '😀', '😊', '👍', '❤️', '🎉', '📅', '📢', '⚠️', '🚧', '🏛️', '🎭', '🎵', '🏃', '🚗', '🚌', '🌳', '🌞', '🌧️', '❄️', '🔥', '💧', '📍', '🕐', '✅', '❌', '🎁', '🍽️', '👨‍👩‍👧', '📞', '✉️' ),
					'i18n'          => array(
						'previewTitle'       => __( 'Aperçu PanneauPocket', 'hpk-panneaupocket' ),
						'placeholderTitle'   => __( 'Écrivez le titre', 'hpk-panneaupocket' ),
						'placeholderContent' => __( 'Votre message apparaîtra ici…', 'hpk-panneaupocket' ),
						'typeInfo'           => __( 'Information', 'hpk-panneaupocket' ),
						'typeAlert'          => __( 'Alerte', 'hpk-panneaupocket' ),
					),
				)
			);
		}
	}

	/**
	 * AJAX test connection.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'hpk_pp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'hpk-panneaupocket' ) ) );
		}

		$result = HPK_PP_Api_Client::instance()->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/**
	 * Export logs CSV.
	 */
	public function export_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission refusée.', 'hpk-panneaupocket' ) );
		}

		check_admin_referer( 'hpk_pp_export_logs' );

		$args = array();
		if ( ! empty( $_GET['post_id'] ) ) {
			$args['post_id'] = absint( $_GET['post_id'] );
		}

		$csv = HPK_PP_Logger::export_csv( $args );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=hpk-panneaupocket-logs-' . gmdate( 'Y-m-d' ) . '.csv' );
		echo $csv;
		exit;
	}

	/**
	 * Purge logs manually.
	 */
	public function purge_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission refusée.', 'hpk-panneaupocket' ) );
		}

		check_admin_referer( 'hpk_pp_purge_logs' );

		$days = absint( get_option( 'hpk_pp_log_retention_days', 90 ) );
		HPK_PP_Logger::purge_old( $days );

		wp_safe_redirect( admin_url( 'admin.php?page=hpk-pp-logs&purged=1' ) );
		exit;
	}

	/**
	 * Save standalone publication form.
	 */
	public function save_publication() {
		if ( ! HPK_PP_Publisher::can_publish() ) {
			wp_die( esc_html__( 'Permission refusée.', 'hpk-panneaupocket' ) );
		}

		check_admin_referer( 'hpk_pp_publication' );

		$form = array(
			'title'          => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'type'           => sanitize_text_field( wp_unslash( $_POST['type'] ?? 'info' ) ),
			'start_at'       => sanitize_text_field( wp_unslash( $_POST['start_at'] ?? '' ) ),
			'end_at'         => sanitize_text_field( wp_unslash( $_POST['end_at'] ?? '' ) ),
			'content'        => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
			'use_wp_content' => ! empty( $_POST['use_wp_content'] ),
			'use_featured'   => ! empty( $_POST['use_featured'] ),
			'draft_mode'     => ! empty( $_POST['draft_mode'] ),
			'documents'      => isset( $_POST['documents'] ) ? array_map( 'esc_url_raw', wp_unslash( (array) $_POST['documents'] ) ) : array(),
		);

		$action  = sanitize_text_field( wp_unslash( $_POST['pub_action'] ?? 'create' ) );
		$post_id = absint( $_POST['sign_id'] ?? 0 );
		$publisher = HPK_PP_Publisher::instance();

		if ( $post_id && 'update' === $action ) {
			$publisher->update_standalone_sign( $post_id, $form );
		} else {
			$post_id = $publisher->create_standalone_sign( $form );
			if ( is_wp_error( $post_id ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=hpk-pp-publication&error=1' ) );
				exit;
			}
		}

		if ( empty( $form['draft_mode'] ) ) {
			$sync_action = ( 'update' === $action ) ? 'update' : 'create';
			$publisher->publish_post( $post_id, $sync_action );
		}

		if ( ! empty( $_POST['create_wp_post'] ) ) {
			$this->create_linked_wp_post( $post_id, $form );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=hpk-pp-publication&saved=1&sign_id=' . $post_id ) );
		exit;
	}

	/**
	 * Create linked public WordPress post from standalone sign.
	 *
	 * @param int   $sign_id Sign post ID.
	 * @param array $form Form data.
	 */
	private function create_linked_wp_post( $sign_id, $form ) {
		$wp_post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_title'   => $form['title'],
				'post_content' => $form['content'],
				'post_status'  => 'publish',
			)
		);

		if ( $wp_post_id && ! is_wp_error( $wp_post_id ) ) {
			update_post_meta( $wp_post_id, '_panneaupocket_enabled', '1' );
			update_post_meta( $wp_post_id, '_panneaupocket_linked_sign', $sign_id );
		}
	}

	/**
	 * Render API settings page.
	 */
	public function render_api_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$last_success = get_option( 'hpk_pp_last_test_success', '' );
		$last_error   = get_option( 'hpk_pp_last_test_error', '' );
		?>
		<div class="wrap hpk-pp-admin">
			<h1><?php esc_html_e( 'PanneauPocket — Réglages API', 'hpk-panneaupocket' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'hpk_pp_api_settings' ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Environnement', 'hpk-panneaupocket' ); ?></th>
						<td>
							<select name="hpk_pp_environment">
								<option value="production" <?php selected( get_option( 'hpk_pp_environment' ), 'production' ); ?>><?php esc_html_e( 'Production', 'hpk-panneaupocket' ); ?></option>
								<option value="staging" <?php selected( get_option( 'hpk_pp_environment' ), 'staging' ); ?>><?php esc_html_e( 'Staging', 'hpk-panneaupocket' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'URL API', 'hpk-panneaupocket' ); ?></th>
						<td>
							<input type="url" name="hpk_pp_api_url" value="<?php echo esc_attr( $this->get_stored_option( 'hpk_pp_api_url', 'https://gestion.panneaupocket.com' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'API REST (token, publications) : gestion.panneaupocket.com', 'hpk-panneaupocket' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'URL embed iframe', 'hpk-panneaupocket' ); ?></th>
						<td>
							<input type="url" name="hpk_pp_embed_url" value="<?php echo esc_attr( $this->get_stored_option( 'hpk_pp_embed_url', 'https://app.panneaupocket.com' ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Affichage widget iframe : app.panneaupocket.com (distinct de l\'URL API).', 'hpk-panneaupocket' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Token Bearer', 'hpk-panneaupocket' ); ?></th>
						<td>
							<input type="password" name="hpk_pp_api_token_plain" value="" class="regular-text" autocomplete="new-password" placeholder="••••••••" />
							<p class="description"><?php esc_html_e( 'Laisser vide pour conserver le token actuel. Le token n\'est jamais affiché en clair.', 'hpk-panneaupocket' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'City ID', 'hpk-panneaupocket' ); ?></th>
						<td>
							<input type="text" name="hpk_pp_city_id" value="<?php echo esc_attr( get_option( 'hpk_pp_city_id', '' ) ); ?>" class="regular-text" inputmode="numeric" pattern="[0-9]*" placeholder="1463772976" />
							<p class="description"><?php esc_html_e( 'Identifiant commune PanneauPocket (chiffres uniquement). Obligatoire pour le widget flottant.', 'hpk-panneaupocket' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Publication automatique', 'hpk-panneaupocket' ); ?></th>
						<td>
							<label><?php $this->checkbox_field( 'hpk_pp_auto_send_on_publish', get_option( 'hpk_pp_auto_send_on_publish' ) ); ?> <?php esc_html_e( 'Envoyer automatiquement à la publication WordPress', 'hpk-panneaupocket' ); ?></label><br />
							<label><?php $this->checkbox_field( 'hpk_pp_auto_update_on_save', get_option( 'hpk_pp_auto_update_on_save', '1' ) ); ?> <?php esc_html_e( 'Mettre à jour automatiquement sur PanneauPocket à la modification', 'hpk-panneaupocket' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Rétention logs (jours)', 'hpk-panneaupocket' ); ?></th>
						<td><input type="number" name="hpk_pp_log_retention_days" value="<?php echo esc_attr( $this->get_stored_option( 'hpk_pp_log_retention_days', 90 ) ); ?>" min="7" max="365" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Tester la connexion API', 'hpk-panneaupocket' ); ?></h2>
			<button type="button" class="button button-secondary hpk-pp-test-connection"><?php esc_html_e( 'Tester la connexion', 'hpk-panneaupocket' ); ?></button>
			<div class="hpk-pp-test-result">
				<?php if ( $last_success ) : ?>
					<p class="hpk-pp-notice success"><?php printf( esc_html__( 'Dernier test réussi : %s', 'hpk-panneaupocket' ), esc_html( $last_success ) ); ?></p>
				<?php endif; ?>
				<?php if ( $last_error ) : ?>
					<p class="hpk-pp-notice error"><?php printf( esc_html__( 'Dernière erreur : %s', 'hpk-panneaupocket' ), esc_html( $last_error ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render display settings page.
	 */
	public function render_display_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap hpk-pp-admin">
			<h1><?php esc_html_e( 'PanneauPocket — Affichage', 'hpk-panneaupocket' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'hpk_pp_display_settings' ); ?>
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Couleur principale', 'hpk-panneaupocket' ); ?></th><td><input type="color" name="hpk_pp_color_primary" value="<?php echo esc_attr( get_option( 'hpk_pp_color_primary', '#0066cc' ) ); ?>" /></td></tr>
					<tr><th><?php esc_html_e( 'Couleur secondaire', 'hpk-panneaupocket' ); ?></th><td><input type="color" name="hpk_pp_color_secondary" value="<?php echo esc_attr( get_option( 'hpk_pp_color_secondary', '#004499' ) ); ?>" /></td></tr>
					<tr><th><?php esc_html_e( 'Couleur du texte', 'hpk-panneaupocket' ); ?></th><td><input type="color" name="hpk_pp_color_text" value="<?php echo esc_attr( get_option( 'hpk_pp_color_text', '#333333' ) ); ?>" /></td></tr>
					<tr><th><?php esc_html_e( 'Couleur du bouton flottant', 'hpk-panneaupocket' ); ?></th><td><input type="color" name="hpk_pp_color_button" value="<?php echo esc_attr( get_option( 'hpk_pp_color_button', '#0066cc' ) ); ?>" /></td></tr>
					<tr>
						<th><?php esc_html_e( 'Logo personnalisé', 'hpk-panneaupocket' ); ?></th>
						<td>
							<input type="hidden" name="hpk_pp_custom_logo" id="hpk_pp_custom_logo" value="<?php echo esc_attr( get_option( 'hpk_pp_custom_logo', '' ) ); ?>" />
							<button type="button" class="button hpk-pp-logo-picker"><?php esc_html_e( 'Choisir un logo', 'hpk-panneaupocket' ); ?></button>
							<label><?php $this->checkbox_field( 'hpk_pp_use_custom_logo', get_option( 'hpk_pp_use_custom_logo' ) ); ?> <?php esc_html_e( 'Utiliser le logo personnalisé', 'hpk-panneaupocket' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Options', 'hpk-panneaupocket' ); ?></th>
						<td>
							<label><?php $this->checkbox_field( 'hpk_pp_animations', get_option( 'hpk_pp_animations', '1' ) ); ?> <?php esc_html_e( 'Activer les animations', 'hpk-panneaupocket' ); ?></label><br />
							<label><?php $this->checkbox_field( 'hpk_pp_responsive_mobile', get_option( 'hpk_pp_responsive_mobile', '1' ) ); ?> <?php esc_html_e( 'Activer le responsive mobile', 'hpk-panneaupocket' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render floating widget settings page.
	 */
	public function render_floating_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = HPK_PP_Floating_Widget::get_render_status();
		?>
		<div class="wrap hpk-pp-admin">
			<h1><?php esc_html_e( 'PanneauPocket — Widget flottant', 'hpk-panneaupocket' ); ?></h1>

			<div class="notice <?php echo $status['will_render'] ? 'notice-success' : 'notice-warning'; ?> inline" style="padding:12px 16px;margin:16px 0;">
				<p><strong><?php esc_html_e( 'État sur le site public', 'hpk-panneaupocket' ); ?></strong></p>
				<?php if ( $status['will_render'] ) : ?>
					<p><?php esc_html_e( 'Le widget sera injecté sur le front (bouton rond en bas à droite/gauche).', 'hpk-panneaupocket' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Le widget ne s\'affiche pas actuellement car :', 'hpk-panneaupocket' ); ?></p>
					<ul style="list-style:disc;margin-left:20px;">
						<?php foreach ( $status['reasons'] as $reason ) : ?>
							<li><?php echo esc_html( $reason ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<p>
					<?php esc_html_e( 'Valeurs enregistrées :', 'hpk-panneaupocket' ); ?>
					<code>activé=<?php echo esc_html( $status['settings']['enabled'] ); ?></code>,
					<code>city_id=<?php echo esc_html( $status['settings']['city_id'] ? $status['settings']['city_id'] : '(vide)' ); ?></code>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'hpk_pp_floating_settings' ); ?>
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Activer', 'hpk-panneaupocket' ); ?></th><td><label><?php $this->checkbox_field( 'hpk_pp_floating_enabled', get_option( 'hpk_pp_floating_enabled' ) ); ?> <?php esc_html_e( 'Activer le widget flottant', 'hpk-panneaupocket' ); ?></label></td></tr>
					<tr>
						<th><?php esc_html_e( 'Position', 'hpk-panneaupocket' ); ?></th>
						<td>
							<select name="hpk_pp_floating_position">
								<option value="bottom-right" <?php selected( get_option( 'hpk_pp_floating_position' ), 'bottom-right' ); ?>><?php esc_html_e( 'Bas droite', 'hpk-panneaupocket' ); ?></option>
								<option value="bottom-left" <?php selected( get_option( 'hpk_pp_floating_position' ), 'bottom-left' ); ?>><?php esc_html_e( 'Bas gauche', 'hpk-panneaupocket' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Mode', 'hpk-panneaupocket' ); ?></th>
						<td>
							<select name="hpk_pp_floating_mode">
								<option value="widget" <?php selected( get_option( 'hpk_pp_floating_mode', 'widget' ), 'widget' ); ?>>widget</option>
								<option value="widgetTv" <?php selected( get_option( 'hpk_pp_floating_mode' ), 'widgetTv' ); ?>>widgetTv</option>
							</select>
						</td>
					</tr>
					<tr><th><?php esc_html_e( 'Largeur desktop', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_width" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_width', '330' ) ); ?>" /> px</td></tr>
					<tr><th><?php esc_html_e( 'Hauteur desktop', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_height" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_height', '518' ) ); ?>" /> px</td></tr>
					<tr><th><?php esc_html_e( 'Largeur mobile', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_width_mobile" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_width_mobile', '92vw' ) ); ?>" /></td></tr>
					<tr><th><?php esc_html_e( 'Hauteur mobile', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_height_mobile" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_height_mobile', '75vh' ) ); ?>" /></td></tr>
					<tr>
						<th><?php esc_html_e( 'Auto-navigation', 'hpk-panneaupocket' ); ?></th>
						<td>
							<select name="hpk_pp_floating_auto_nav">
								<?php foreach ( array( 0, 10, 15, 30 ) as $sec ) : ?>
									<option value="<?php echo esc_attr( $sec ); ?>" <?php selected( get_option( 'hpk_pp_floating_auto_nav', '0' ), (string) $sec ); ?>><?php echo esc_html( $sec ); ?>s</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr><th><?php esc_html_e( 'Couleur fond (widgetTv)', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_bg_color" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_bg_color', 'ffffff' ) ); ?>" placeholder="ffffff" /></td></tr>
					<tr><th><?php esc_html_e( 'Mobile', 'hpk-panneaupocket' ); ?></th><td><label><?php $this->checkbox_field( 'hpk_pp_floating_mobile', get_option( 'hpk_pp_floating_mobile', '1' ) ); ?> <?php esc_html_e( 'Afficher sur mobile', 'hpk-panneaupocket' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Pages exclues', 'hpk-panneaupocket' ); ?></th><td><input type="text" name="hpk_pp_floating_excluded_pages" value="<?php echo esc_attr( get_option( 'hpk_pp_floating_excluded_pages', '' ) ); ?>" class="large-text" placeholder="12,45,78" /><p class="description"><?php esc_html_e( 'IDs de pages séparés par des virgules.', 'hpk-panneaupocket' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Mémoriser fermeture', 'hpk-panneaupocket' ); ?></th><td><label><?php $this->checkbox_field( 'hpk_pp_floating_remember_closed', get_option( 'hpk_pp_floating_remember_closed', '1' ) ); ?> <?php esc_html_e( 'Retenir l\'état fermé (localStorage)', 'hpk-panneaupocket' ); ?></label></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render shortcodes help page.
	 */
	public function render_shortcodes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$examples = array(
			'[panneaupocket_widget]',
			'[panneaupocket_widget mode="widget" auto_navigation="0"]',
			'[panneaupocket_widget mode="widgetTv" auto_navigation="10" city_id="1463772976"]',
			'[panneaupocket_widget mode="widgetTv" auto_navigation="15" bg_color="ffffff"]',
			'[panneaupocket_news limit="6" layout="grid" show_date="true" show_image="true"]',
			'[panneaupocket_news layout="list" pagination="true" per_page="10"]',
			'[panneaupocket_news layout="compact" show_type="true" excerpt_length="80"]',
		);
		?>
		<div class="wrap hpk-pp-admin">
			<h1><?php esc_html_e( 'PanneauPocket — Shortcodes', 'hpk-panneaupocket' ); ?></h1>
			<p><?php esc_html_e( 'Copiez ces shortcodes dans vos pages, articles ou widgets texte.', 'hpk-panneaupocket' ); ?></p>

			<h2><?php esc_html_e( 'Widget iframe', 'hpk-panneaupocket' ); ?></h2>
			<p><code>[panneaupocket_widget]</code></p>
			<p><?php esc_html_e( 'URL embed : app.panneaupocket.com/embeded/{cityId} — distincte de l\'URL API (gestion.panneaupocket.com).', 'hpk-panneaupocket' ); ?></p>
			<p><?php esc_html_e( 'Attributs : mode, auto_navigation, bg_color, city_id, width, height', 'hpk-panneaupocket' ); ?></p>

			<h2><?php esc_html_e( 'Actualités WordPress synchronisées', 'hpk-panneaupocket' ); ?></h2>
			<p><code>[panneaupocket_news]</code></p>
			<p><?php esc_html_e( 'Attributs : limit, per_page, layout (grid/list/compact), show_date, show_image, show_type, excerpt_length, pagination', 'hpk-panneaupocket' ); ?></p>

			<h2><?php esc_html_e( 'Exemples', 'hpk-panneaupocket' ); ?></h2>
			<?php foreach ( $examples as $ex ) : ?>
				<div class="hpk-pp-copy-block">
					<code><?php echo esc_html( $ex ); ?></code>
					<button type="button" class="button hpk-pp-copy-btn" data-copy="<?php echo esc_attr( $ex ); ?>"><?php esc_html_e( 'Copier', 'hpk-panneaupocket' ); ?></button>
				</div>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Widgets WordPress', 'hpk-panneaupocket' ); ?></h2>
			<p><?php esc_html_e( 'Deux widgets sont disponibles dans Apparence > Widgets : « HPK PanneauPocket — Widget iframe » et « HPK PanneauPocket — Actualités ».', 'hpk-panneaupocket' ); ?></p>
			<p><?php esc_html_e( 'Deux blocs Gutenberg sont également disponibles dans l\'éditeur de blocs.', 'hpk-panneaupocket' ); ?></p>
			<h2><?php esc_html_e( 'Elementor', 'hpk-panneaupocket' ); ?></h2>
			<p><?php esc_html_e( 'Si Elementor est installé, deux widgets apparaissent dans la catégorie « PanneauPocket » : « PanneauPocket Widget » (iframe) et « PanneauPocket Actualités ».', 'hpk-panneaupocket' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render standalone publication page.
	 */
	public function render_publication_page() {
		if ( ! HPK_PP_Publisher::can_publish() ) {
			wp_die( esc_html__( 'Permission refusée.', 'hpk-panneaupocket' ) );
		}

		$sign_id = absint( $_GET['sign_id'] ?? 0 );
		$sign    = $sign_id ? get_post( $sign_id ) : null;

		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Publication enregistrée.', 'hpk-panneaupocket' ) . '</p></div>';
		}

		$title    = $sign ? get_post_meta( $sign_id, '_panneaupocket_title', true ) : '';
		$type     = $sign ? get_post_meta( $sign_id, '_panneaupocket_type', true ) : 'info';
		$start_at = $sign ? get_post_meta( $sign_id, '_panneaupocket_start_at', true ) : gmdate( 'Y-m-d' );
		$end_at   = $sign ? get_post_meta( $sign_id, '_panneaupocket_end_at', true ) : '';
		$content  = $sign ? get_post_meta( $sign_id, '_panneaupocket_content', true ) : '';
		$docs     = $sign ? get_post_meta( $sign_id, '_panneaupocket_documents', true ) : array( '' );
		if ( ! is_array( $docs ) || empty( $docs ) ) {
			$docs = array( '' );
		}
		$title_len = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
		$logo_url  = HPK_PP_Image_Library::get_default_logo_url();
		$library   = HPK_PP_Image_Library::get_base_library();
		?>
		<div class="wrap hpk-pp-admin hpk-pp-publication">
			<h1><?php esc_html_e( 'PanneauPocket — Publication', 'hpk-panneaupocket' ); ?></h1>
			<p><?php esc_html_e( 'Publiez directement sur PanneauPocket sans créer d\'article WordPress public.', 'hpk-panneaupocket' ); ?></p>

			<div class="hpk-pp-publication__layout">
				<div class="hpk-pp-publication__form">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hpk-pp-publication-form">
						<input type="hidden" name="action" value="hpk_pp_save_publication" />
						<input type="hidden" name="sign_id" value="<?php echo esc_attr( $sign_id ); ?>" />
						<?php wp_nonce_field( 'hpk_pp_publication' ); ?>

						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Titre (max 50)', 'hpk-panneaupocket' ); ?></th>
								<td>
									<div class="hpk-pp-title-row">
										<input type="text" name="title" value="<?php echo esc_attr( $title ); ?>" maxlength="50" class="regular-text hpk-pp-title-input hpk-pp-preview-title" required />
										<button type="button" class="button hpk-pp-emoji-trigger" data-target=".hpk-pp-preview-title" aria-label="<?php esc_attr_e( 'Insérer un emoji dans le titre', 'hpk-panneaupocket' ); ?>">😊</button>
									</div>
									<span class="hpk-pp-char-count"><span class="hpk-pp-char-current"><?php echo esc_html( $title_len ); ?></span>/50</span>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Type', 'hpk-panneaupocket' ); ?></th>
								<td>
									<select name="type" class="hpk-pp-preview-type">
										<option value="info" <?php selected( $type, 'info' ); ?>><?php esc_html_e( 'Information', 'hpk-panneaupocket' ); ?></option>
										<option value="alert" <?php selected( $type, 'alert' ); ?>><?php esc_html_e( 'Alerte', 'hpk-panneaupocket' ); ?></option>
									</select>
								</td>
							</tr>
							<tr><th><?php esc_html_e( 'Date début', 'hpk-panneaupocket' ); ?></th><td><input type="date" name="start_at" value="<?php echo esc_attr( $start_at ); ?>" required /></td></tr>
							<tr><th><?php esc_html_e( 'Date fin', 'hpk-panneaupocket' ); ?></th><td><input type="date" name="end_at" value="<?php echo esc_attr( $end_at ); ?>" /></td></tr>
							<tr>
								<th><?php esc_html_e( 'Contenu', 'hpk-panneaupocket' ); ?></th>
								<td>
									<p class="description"><?php esc_html_e( 'Utilisez la barre d\'outils (gras, italique, liens…) ou le bouton emoji — pas besoin d\'écrire du HTML à la main.', 'hpk-panneaupocket' ); ?></p>
									<p>
										<button type="button" class="button hpk-pp-emoji-trigger" data-target-editor="<?php echo esc_attr( 'hpk_pp_publication_content' ); ?>">
											<?php esc_html_e( 'Insérer un emoji', 'hpk-panneaupocket' ); ?>
										</button>
									</p>
									<?php
									wp_editor(
										$content,
										'hpk_pp_publication_content',
										array(
											'textarea_name' => 'content',
											'textarea_rows' => 12,
											'media_buttons' => false,
											'teeny'         => false,
											'quicktags'     => true,
											'tinymce'       => array(
												'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,removeformat',
												'toolbar2' => '',
											),
										)
									);
									?>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Images & documents', 'hpk-panneaupocket' ); ?></th>
								<td>
									<div class="hpk-pp-copyright-warning" role="note">
										<?php esc_html_e( 'Utiliser des images en ligne peut entraîner des risques juridiques et des amendes si vous n\'avez pas les droits d\'auteur. Préférez notre banque d\'images dédiée ci-dessous ou la médiathèque WordPress.', 'hpk-panneaupocket' ); ?>
									</div>
									<p class="description">
										<?php esc_html_e( 'Jusqu\'à 5 fichiers (jpg, png ou pdf, 15 Mo max). L\'API PanneauPocket télécharge le fichier depuis l\'URL publique.', 'hpk-panneaupocket' ); ?>
									</p>

									<?php if ( ! empty( $library ) ) : ?>
										<?php
										$library_count = 0;
										foreach ( $library as $images ) {
											$library_count += count( $images );
										}
										?>
										<details class="hpk-pp-image-library">
											<summary class="hpk-pp-image-library__toggle">
												<?php esc_html_e( 'Bibliothèque PanneauPocket', 'hpk-panneaupocket' ); ?>
												<span class="hpk-pp-image-library__count">(<?php echo esc_html( $library_count ); ?> <?php esc_html_e( 'images', 'hpk-panneaupocket' ); ?>)</span>
											</summary>
											<div class="hpk-pp-image-library__body">
												<p class="description"><?php esc_html_e( 'Survolez une miniature pour l\'agrandir. Cliquez pour l\'ajouter aux documents.', 'hpk-panneaupocket' ); ?></p>
												<?php foreach ( $library as $category => $images ) : ?>
													<div class="hpk-pp-image-library__category">
														<h5 class="hpk-pp-image-library__category-title"><?php echo esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $category ) ) ); ?></h5>
														<div class="hpk-pp-image-library__grid">
															<?php foreach ( $images as $image ) : ?>
																<button type="button" class="hpk-pp-library-pick" data-url="<?php echo esc_url( $image['url'] ); ?>" title="<?php echo esc_attr( $image['name'] ); ?>">
																	<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['name'] ); ?>" loading="lazy" />
																</button>
															<?php endforeach; ?>
														</div>
													</div>
												<?php endforeach; ?>
											</div>
										</details>
									<?php endif; ?>

									<div class="hpk-pp-documents" data-input-name="documents[]">
										<?php foreach ( $docs as $doc ) : ?>
											<p class="hpk-pp-doc-row">
												<input type="url" name="documents[]" value="<?php echo esc_url( $doc ); ?>" class="large-text hpk-pp-doc-url" placeholder="https://" />
												<button type="button" class="button hpk-pp-media-btn"><?php esc_html_e( 'Média WP', 'hpk-panneaupocket' ); ?></button>
												<button type="button" class="button hpk-pp-remove-doc" title="<?php esc_attr_e( 'Retirer', 'hpk-panneaupocket' ); ?>" aria-label="<?php esc_attr_e( 'Retirer ce document', 'hpk-panneaupocket' ); ?>">&times;</button>
											</p>
										<?php endforeach; ?>
										<button type="button" class="button hpk-pp-add-doc"><?php esc_html_e( 'Ajouter un fichier', 'hpk-panneaupocket' ); ?></button>
									</div>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Options', 'hpk-panneaupocket' ); ?></th>
								<td>
									<label><input type="checkbox" name="draft_mode" value="1" /> <?php esc_html_e( 'Préparer sans envoyer', 'hpk-panneaupocket' ); ?></label><br />
									<label><input type="checkbox" name="create_wp_post" value="1" /> <?php esc_html_e( 'Créer aussi un article WordPress public', 'hpk-panneaupocket' ); ?></label>
								</td>
							</tr>
						</table>

						<p>
							<button type="submit" name="pub_action" value="create" class="button button-primary"><?php esc_html_e( 'Envoyer sur PanneauPocket', 'hpk-panneaupocket' ); ?></button>
							<?php if ( $sign_id ) : ?>
								<button type="submit" name="pub_action" value="update" class="button"><?php esc_html_e( 'Mettre à jour sur PanneauPocket', 'hpk-panneaupocket' ); ?></button>
							<?php endif; ?>
						</p>
					</form>
				</div>

				<aside class="hpk-pp-publication__preview-wrap" aria-label="<?php esc_attr_e( 'Aperçu PanneauPocket', 'hpk-panneaupocket' ); ?>">
					<h2 class="hpk-pp-publication__preview-heading"><?php esc_html_e( 'Aperçu PanneauPocket', 'hpk-panneaupocket' ); ?></h2>
					<div class="hpk-pp-phone-preview">
						<div class="hpk-pp-phone-preview__header">
							<div class="hpk-pp-phone-preview__community">
								<strong class="hpk-pp-preview-community-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
								<?php if ( get_option( 'hpk_pp_city_id', '' ) ) : ?>
									<span class="hpk-pp-phone-preview__city-id"><?php echo esc_html( get_option( 'hpk_pp_city_id', '' ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="hpk-pp-phone-preview__logo-wrap">
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="PanneauPocket" class="hpk-pp-phone-preview__logo-img" width="44" height="44" />
								<span class="hpk-pp-phone-preview__logo-badge" aria-hidden="true">1</span>
							</div>
						</div>
						<div class="hpk-pp-phone-preview__body">
							<div class="hpk-pp-phone-preview__badge hpk-pp-preview-type-badge"><?php esc_html_e( 'Information', 'hpk-panneaupocket' ); ?></div>
							<div class="hpk-pp-phone-preview__title hpk-pp-preview-title-display"><?php echo $title ? esc_html( $title ) : esc_html__( 'Écrivez le titre', 'hpk-panneaupocket' ); ?></div>
							<div class="hpk-pp-phone-preview__scroll">
								<div class="hpk-pp-phone-preview__content hpk-pp-preview-content-display">
									<?php if ( $content ) : ?>
										<?php echo wp_kses_post( $content ); ?>
									<?php else : ?>
										<p class="hpk-pp-phone-preview__placeholder"><?php esc_html_e( 'Votre message apparaîtra ici…', 'hpk-panneaupocket' ); ?></p>
									<?php endif; ?>
								</div>
								<div class="hpk-pp-phone-preview__docs hpk-pp-preview-docs-display"></div>
							</div>
						</div>
					</div>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Render logs page.
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['purged'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Logs purgés.', 'hpk-panneaupocket' ) . '</p></div>';
		}

		$page    = absint( $_GET['paged'] ?? 1 );
		$post_id = absint( $_GET['post_id'] ?? 0 );
		$result  = HPK_PP_Logger::get_logs(
			array(
				'post_id'  => $post_id,
				'per_page' => 20,
				'page'     => $page,
			)
		);
		?>
		<div class="wrap hpk-pp-admin">
			<h1><?php esc_html_e( 'PanneauPocket — Logs', 'hpk-panneaupocket' ); ?></h1>

			<p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hpk_pp_export_logs' . ( $post_id ? '&post_id=' . $post_id : '' ) ), 'hpk_pp_export_logs' ) ); ?>" class="button"><?php esc_html_e( 'Exporter CSV', 'hpk-panneaupocket' ); ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=hpk_pp_purge_logs' ), 'hpk_pp_purge_logs' ) ); ?>" class="button" onclick="return confirm('<?php echo esc_js( __( 'Purger les anciens logs ?', 'hpk-panneaupocket' ) ); ?>');"><?php esc_html_e( 'Purger les anciens logs', 'hpk-panneaupocket' ); ?></a>
			</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Article', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Action', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Code HTTP', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Statut', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Message API', 'hpk-panneaupocket' ); ?></th>
						<th><?php esc_html_e( 'Utilisateur', 'hpk-panneaupocket' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $result['items'] ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'Aucun log.', 'hpk-panneaupocket' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $result['items'] as $log ) : ?>
							<?php
							$user      = $log->user_id ? get_userdata( $log->user_id ) : null;
							$post_title = $log->post_id ? get_the_title( $log->post_id ) : '-';
							?>
							<tr>
								<td><?php echo esc_html( $log->created_at ); ?></td>
								<td><?php echo esc_html( $post_title ); ?></td>
								<td><?php echo esc_html( $log->action ); ?></td>
								<td><?php echo esc_html( $log->http_code ); ?></td>
								<td><?php echo esc_html( $log->status ); ?></td>
								<td><?php echo esc_html( HPK_PP_Logger::extract_message( $log->api_response ) ); ?></td>
								<td><?php echo esc_html( $user ? $user->display_name : '-' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $result['pages'] > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $page,
								'total'   => $result['pages'],
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
