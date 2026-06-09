<?php
/**
 * Plugin Name:       HPK PanneauPocket Connect
 * Plugin URI:        https://panneaupocket.com
 * Description:       Intégration PanneauPocket : widget flottant, shortcodes, publication d'actualités WordPress vers l'API officielle.
 * Version:           1.2.4
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            HPK
 * License:           GPL-2.0-or-later
 * Text Domain:       hpk-panneaupocket
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HPK_PP_LOADED_FROM', __FILE__ );
define( 'HPK_PP_VERSION', '1.2.4' );
define( 'HPK_PP_CANONICAL_BASENAME', 'hpk-panneaupocket/hpk-panneaupocket.php' );
define( 'HPK_PP_PATH', plugin_dir_path( __FILE__ ) );
define( 'HPK_PP_URL', plugin_dir_url( __FILE__ ) );
define( 'HPK_PP_BASENAME', plugin_basename( __FILE__ ) );

/**
 * GitHub repository for automatic updates (override in wp-config.php if needed).
 *
 * @see docs/GITHUB-RELEASES.md
 */
if ( ! defined( 'HPK_PP_GITHUB_REPO' ) ) {
	define( 'HPK_PP_GITHUB_REPO', 'https://github.com/Rac3Mul/HPK-Panneau-Pocket/' );
}

/**
 * Required plugin files (detect incomplete FTP uploads).
 *
 * @return string[]
 */
function hpk_pp_missing_required_files() {
	$required = array(
		'includes/class-logger.php',
		'includes/class-sanitizer.php',
		'includes/class-api-client.php',
		'includes/class-publisher.php',
		'includes/class-metabox.php',
		'includes/class-admin.php',
		'includes/class-shortcodes.php',
		'includes/class-floating-widget.php',
		'includes/class-widgets.php',
		'includes/class-blocks.php',
		'includes/class-elementor.php',
	);

	$missing = array();
	foreach ( $required as $relative ) {
		if ( ! is_readable( HPK_PP_PATH . $relative ) ) {
			$missing[] = $relative;
		}
	}

	return $missing;
}

$hpk_pp_missing_files = hpk_pp_missing_required_files();
if ( ! empty( $hpk_pp_missing_files ) ) {
	add_action(
		'admin_notices',
		static function () use ( $hpk_pp_missing_files ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p><strong>HPK PanneauPocket Connect :</strong> ';
			echo esc_html__( 'Installation incomplète — des fichiers du plugin sont manquants sur le serveur.', 'hpk-panneaupocket' );
			echo '</p><p><code>' . esc_html( implode( '</code>, <code>', $hpk_pp_missing_files ) ) . '</code></p>';
			echo '<p>' . esc_html__( 'Supprimez le dossier hpk-panneaupocket/ puis réinstallez le zip hpk-panneaupocket.zip (version 1.1.0) via Extensions → Ajouter.', 'hpk-panneaupocket' ) . '</p></div>';
		}
	);

	add_action(
		'admin_menu',
		static function () {
			add_menu_page(
				__( 'PanneauPocket', 'hpk-panneaupocket' ),
				__( 'PanneauPocket', 'hpk-panneaupocket' ),
				'manage_options',
				'hpk-pp-settings',
				static function () {
					echo '<div class="wrap"><h1>PanneauPocket</h1>';
					echo '<div class="notice notice-error inline"><p>';
					echo esc_html__( 'Installation incomplète. Réinstallez hpk-panneaupocket.zip depuis votre ordinateur (version 1.1.0).', 'hpk-panneaupocket' );
					echo '</p></div></div>';
				},
				'dashicons-megaphone',
				30
			);
		}
	);

	return;
}

require_once HPK_PP_PATH . 'includes/class-logger.php';
require_once HPK_PP_PATH . 'includes/class-sanitizer.php';
require_once HPK_PP_PATH . 'includes/class-api-client.php';
require_once HPK_PP_PATH . 'includes/class-publisher.php';
require_once HPK_PP_PATH . 'includes/class-metabox.php';
require_once HPK_PP_PATH . 'includes/class-admin.php';
require_once HPK_PP_PATH . 'includes/class-shortcodes.php';
require_once HPK_PP_PATH . 'includes/class-floating-widget.php';
require_once HPK_PP_PATH . 'includes/class-widgets.php';
require_once HPK_PP_PATH . 'includes/class-blocks.php';
require_once HPK_PP_PATH . 'includes/class-elementor.php';
require_once HPK_PP_PATH . 'includes/class-updater.php';

/**
 * Main plugin bootstrap.
 */
final class HPK_PanneauPocket {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PanneauPocket|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PanneauPocket
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
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_role' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_action( 'admin_init', array( $this, 'deactivate_duplicate_copies' ), 5 );
		add_action( 'hpk_pp_purge_logs', array( 'HPK_PP_Logger', 'purge_old_cron' ) );

		// Admin menu must register even if plugins_loaded fails later.
		HPK_PP_Admin::instance();

		add_filter(
			'plugin_action_links_' . HPK_PP_BASENAME,
			array( $this, 'plugin_action_links' )
		);

		if ( is_admin() ) {
			register_shutdown_function( array( $this, 'catch_fatal_error' ) );
		}
	}

	/**
	 * Settings link on plugins list.
	 *
	 * @param string[] $links Action links.
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=hpk-pp-settings' ) ) . '">' . esc_html__( 'Réglages', 'hpk-panneaupocket' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Surface fatal PHP errors that stop the plugin from booting.
	 */
	public function catch_fatal_error() {
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			return;
		}

		if ( false === strpos( $error['file'], 'hpk-panneaupocket' ) ) {
			return;
		}

		set_transient(
			'hpk_pp_fatal_notice',
			array(
				'message' => $error['message'],
				'file'    => $error['file'],
				'line'    => $error['line'],
			),
			300
		);
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		HPK_PP_Logger::create_table();
		$this->set_default_options();
		$this->register_post_type();
		$this->register_role();
		$this->deactivate_duplicate_copies( true );
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( 'hpk_pp_purge_logs' ) ) {
			wp_schedule_event( time(), 'weekly', 'hpk_pp_purge_logs' );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'hpk_pp_purge_logs' );
		flush_rewrite_rules();
	}

	/**
	 * Set default options on activation.
	 */
	private function set_default_options() {
		$defaults = array(
			'hpk_pp_environment'              => 'production',
			'hpk_pp_api_url'                  => 'https://gestion.panneaupocket.com',
			'hpk_pp_embed_url'                => 'https://app.panneaupocket.com',
			'hpk_pp_api_token'                => '',
			'hpk_pp_city_id'                  => '',
			'hpk_pp_last_test_success'        => '',
			'hpk_pp_last_test_error'          => '',
			'hpk_pp_color_primary'            => '#0066cc',
			'hpk_pp_color_secondary'          => '#004499',
			'hpk_pp_color_text'               => '#333333',
			'hpk_pp_color_button'             => '#0066cc',
			'hpk_pp_custom_logo'              => '',
			'hpk_pp_use_custom_logo'          => '0',
			'hpk_pp_animations'               => '1',
			'hpk_pp_responsive_mobile'        => '1',
			'hpk_pp_floating_enabled'         => '0',
			'hpk_pp_floating_position'        => 'bottom-right',
			'hpk_pp_floating_mode'            => 'widget',
			'hpk_pp_floating_width'           => '330',
			'hpk_pp_floating_height'          => '518',
			'hpk_pp_floating_width_mobile'    => '92vw',
			'hpk_pp_floating_height_mobile'   => '75vh',
			'hpk_pp_floating_auto_nav'        => '0',
			'hpk_pp_floating_bg_color'        => 'ffffff',
			'hpk_pp_floating_mobile'          => '1',
			'hpk_pp_floating_excluded_pages'  => '',
			'hpk_pp_floating_remember_closed' => '1',
			'hpk_pp_auto_send_on_publish'     => '0',
			'hpk_pp_auto_update_on_save'      => '1',
			'hpk_pp_log_retention_days'       => '90',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Register internal CPT for standalone PanneauPocket publications.
	 */
	public function register_post_type() {
		register_post_type(
			'hpk_pp_sign',
			array(
				'labels'              => array(
					'name'          => __( 'Publications PanneauPocket', 'hpk-panneaupocket' ),
					'singular_name' => __( 'Publication PanneauPocket', 'hpk-panneaupocket' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor' ),
				'has_archive'         => false,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Register custom role for PanneauPocket managers.
	 */
	public function register_role() {
		if ( ! get_role( 'hpk_panneaupocket_manager' ) ) {
			add_role(
				'hpk_panneaupocket_manager',
				__( 'Gestion PanneauPocket', 'hpk-panneaupocket' ),
				array(
					'read'                   => true,
					'edit_posts'             => true,
					'publish_posts'          => true,
					'upload_files'           => true,
					'hpk_pp_publish_signs'   => true,
				)
			);
		}

		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( 'hpk_pp_publish_signs' ) ) {
			$admin->add_cap( 'hpk_pp_publish_signs' );
		}
	}

	/**
	 * Keep only the canonical plugin copy active.
	 *
	 * @param bool $on_activation Run during plugin activation.
	 */
	public function deactivate_duplicate_copies( $on_activation = false ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! $on_activation && ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) ) {
			return;
		}

		$plugin_name = 'HPK PanneauPocket Connect';
		$copies      = array();

		foreach ( get_plugins() as $basename => $header ) {
			if ( ( $header['Name'] ?? '' ) !== $plugin_name ) {
				continue;
			}
			$copies[ $basename ] = $header['Version'] ?? '0.0.0';
		}

		if ( count( $copies ) <= 1 ) {
			return;
		}

		$keep_basename = HPK_PP_CANONICAL_BASENAME;

		if ( ! isset( $copies[ $keep_basename ] ) ) {
			$best_version = '0.0.0';
			foreach ( $copies as $basename => $version ) {
				if ( version_compare( $version, $best_version, '>' ) ) {
					$best_version  = $version;
					$keep_basename = $basename;
				}
			}
		}

		$deactivated = array();
		foreach ( array_keys( $copies ) as $basename ) {
			if ( $basename === $keep_basename ) {
				continue;
			}
			if ( is_plugin_active( $basename ) ) {
				deactivate_plugins( $basename, true );
				$deactivated[] = $basename;
			}
		}

		if ( ! empty( $deactivated ) ) {
			set_transient(
				'hpk_pp_duplicates_notice',
				array(
					'keep'        => $keep_basename,
					'deactivated' => $deactivated,
					'all'         => array_keys( $copies ),
				),
				120
			);
		}
	}

	/**
	 * Initialize plugin modules.
	 */
	public function init() {
		load_plugin_textdomain( 'hpk-panneaupocket', false, dirname( HPK_PP_BASENAME ) . '/languages' );

		$this->maybe_migrate_embed_url();
		$this->maybe_show_duplicate_notice();
		$this->maybe_show_fatal_notice();

		HPK_PP_Metabox::instance();
		HPK_PP_Publisher::instance();
		HPK_PP_Shortcodes::instance();
		HPK_PP_Floating_Widget::instance();
		HPK_PP_Widgets::instance();
		HPK_PP_Blocks::instance();
		$this->init_elementor();
	}

	/**
	 * Admin notice after duplicate copies were auto-deactivated.
	 */
	private function maybe_show_duplicate_notice() {
		if ( ! is_admin() ) {
			return;
		}

		$data = get_transient( 'hpk_pp_duplicates_notice' );
		if ( ! $data || ! is_array( $data ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $data ) {
				$folders = array_unique(
					array_map(
						static function ( $basename ) {
							return dirname( $basename );
						},
						$data['all']
					)
				);
				echo '<div class="notice notice-warning is-dismissible"><p><strong>HPK PanneauPocket Connect :</strong> ';
				echo esc_html__( 'Plusieurs copies du plugin étaient installées. Seule la version la plus récente reste active.', 'hpk-panneaupocket' );
				echo '</p><p>';
				echo esc_html__( 'Supprimez définitivement les dossiers en trop via cPanel → Gestionnaire de fichiers → wp-content/plugins/ :', 'hpk-panneaupocket' );
				echo ' <code>' . esc_html( implode( '</code>, <code>', $folders ) ) . '</code>';
				echo '</p><p>';
				echo esc_html__( 'Ne gardez que le dossier hpk-panneaupocket/ (version 1.1.0).', 'hpk-panneaupocket' );
				echo '</p></div>';
			}
		);
	}

	/**
	 * Admin notice after a fatal error in plugin code.
	 */
	private function maybe_show_fatal_notice() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$data = get_transient( 'hpk_pp_fatal_notice' );
		if ( ! $data || ! is_array( $data ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $data ) {
				echo '<div class="notice notice-error"><p><strong>HPK PanneauPocket Connect :</strong> ';
				echo esc_html__( 'Erreur PHP fatale lors du chargement du plugin.', 'hpk-panneaupocket' );
				echo '</p><p><code>' . esc_html( $data['message'] ) . '</code><br>';
				echo esc_html( $data['file'] ) . ':' . esc_html( (string) $data['line'] );
				echo '</p></div>';
			}
		);
	}

	/**
	 * Set/fix embed URL for existing installs.
	 */
	private function maybe_migrate_embed_url() {
		$embed = get_option( 'hpk_pp_embed_url', false );

		if (
			false === $embed
			|| '' === $embed
			|| false !== strpos( (string) $embed, 'gestion.panneaupocket.com' )
		) {
			update_option( 'hpk_pp_embed_url', 'https://app.panneaupocket.com' );
		}
	}

	/**
	 * Initialize Elementor integration when plugin is active.
	 */
	private function init_elementor() {
		if ( did_action( 'elementor/loaded' ) ) {
			HPK_PP_Elementor::instance();
			return;
		}

		add_action(
			'elementor/loaded',
			function () {
				HPK_PP_Elementor::instance();
			}
		);
	}
}

HPK_PP_Updater::init();
HPK_PanneauPocket::instance();
