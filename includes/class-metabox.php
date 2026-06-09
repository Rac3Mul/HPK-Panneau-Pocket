<?php
/**
 * Metabox for PanneauPocket in post editor.
 *
 * @package HPK_PanneauPocket
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPK_PP_Metabox
 */
class HPK_PP_Metabox {

	/**
	 * Singleton instance.
	 *
	 * @var HPK_PP_Metabox|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return HPK_PP_Metabox
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
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( 'HPK_PP_Publisher', 'save_metabox_meta' ), 10, 1 );
	}

	/**
	 * Register metabox.
	 */
	public function register() {
		$post_types = apply_filters( 'hpk_pp_metabox_post_types', array( 'post' ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'hpk_panneaupocket',
				__( 'PanneauPocket', 'hpk-panneaupocket' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render( $post ) {
		wp_nonce_field( 'hpk_pp_metabox', 'hpk_pp_metabox_nonce' );

		$enabled    = get_post_meta( $post->ID, '_panneaupocket_enabled', true );
		$title      = get_post_meta( $post->ID, '_panneaupocket_title', true );
		$type       = get_post_meta( $post->ID, '_panneaupocket_type', true ) ?: 'info';
		$start_at   = get_post_meta( $post->ID, '_panneaupocket_start_at', true );
		$end_at     = get_post_meta( $post->ID, '_panneaupocket_end_at', true );
		$content    = get_post_meta( $post->ID, '_panneaupocket_content', true );
		$use_wp     = get_post_meta( $post->ID, '_panneaupocket_use_wp_content', true );
		$use_thumb  = get_post_meta( $post->ID, '_panneaupocket_use_featured', true );
		$documents  = get_post_meta( $post->ID, '_panneaupocket_documents', true );
		$status     = get_post_meta( $post->ID, '_panneaupocket_last_status', true ) ?: 'non_envoye';
		$external   = get_post_meta( $post->ID, '_panneaupocket_external_id', true );
		$draft_mode = get_post_meta( $post->ID, '_panneaupocket_draft_mode', true );
		$last_sync  = get_post_meta( $post->ID, '_panneaupocket_last_sync', true );
		$http_code  = get_post_meta( $post->ID, '_panneaupocket_last_http_code', true );

		if ( ! is_array( $documents ) ) {
			$documents = array( '' );
		}
		if ( empty( $documents ) ) {
			$documents = array( '' );
		}

		if ( empty( $external ) ) {
			$external = HPK_PP_Sanitizer::get_external_id( $post->ID, $post->post_type );
		}

		if ( empty( $start_at ) ) {
			$start_at = gmdate( 'Y-m-d' );
		}

		if ( empty( $title ) ) {
			$title = $post->post_title;
		}

		$status_labels = array(
			'non_envoye' => __( 'Non envoyé', 'hpk-panneaupocket' ),
			'envoye'     => __( 'Envoyé', 'hpk-panneaupocket' ),
			'erreur'     => __( 'Erreur', 'hpk-panneaupocket' ),
			'modifie'    => __( 'Modifié', 'hpk-panneaupocket' ),
		);

		$logs_url = admin_url( 'admin.php?page=hpk-pp-logs&post_id=' . $post->ID );
		?>
		<div class="hpk-pp-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<p class="hpk-pp-status hpk-pp-status--<?php echo esc_attr( $status ); ?>">
				<strong><?php esc_html_e( 'Statut :', 'hpk-panneaupocket' ); ?></strong>
				<?php echo esc_html( $status_labels[ $status ] ?? $status ); ?>
				<?php if ( $last_sync ) : ?>
					<span class="hpk-pp-meta">(<?php echo esc_html( $last_sync ); ?><?php echo $http_code ? ' — HTTP ' . esc_html( $http_code ) : ''; ?>)</span>
				<?php endif; ?>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_panneaupocket_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
					<?php esc_html_e( 'Envoyer cette actualité sur PanneauPocket', 'hpk-panneaupocket' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_panneaupocket_draft_mode" value="1" <?php checked( $draft_mode, '1' ); ?> />
					<?php esc_html_e( 'Mode brouillon interne (préparer sans envoyer)', 'hpk-panneaupocket' ); ?>
				</label>
			</p>

			<p>
				<label for="hpk_pp_title"><?php esc_html_e( 'Titre court PanneauPocket (max 50)', 'hpk-panneaupocket' ); ?></label><br />
				<input type="text" id="hpk_pp_title" name="_panneaupocket_title" value="<?php echo esc_attr( $title ); ?>" maxlength="50" class="widefat hpk-pp-title-input" />
				<span class="hpk-pp-char-count"><span class="hpk-pp-char-current"><?php echo esc_html( function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title ) ); ?></span>/50</span>
			</p>

			<p>
				<label for="hpk_pp_type"><?php esc_html_e( 'Type', 'hpk-panneaupocket' ); ?></label><br />
				<select id="hpk_pp_type" name="_panneaupocket_type">
					<option value="info" <?php selected( $type, 'info' ); ?>><?php esc_html_e( 'Info', 'hpk-panneaupocket' ); ?></option>
					<option value="alert" <?php selected( $type, 'alert' ); ?>><?php esc_html_e( 'Alerte', 'hpk-panneaupocket' ); ?></option>
				</select>
			</p>

			<p class="hpk-pp-dates">
				<label for="hpk_pp_start"><?php esc_html_e( 'Date de début', 'hpk-panneaupocket' ); ?></label>
				<input type="date" id="hpk_pp_start" name="_panneaupocket_start_at" value="<?php echo esc_attr( $start_at ); ?>" required />
				<label for="hpk_pp_end"><?php esc_html_e( 'Date de fin', 'hpk-panneaupocket' ); ?></label>
				<input type="date" id="hpk_pp_end" name="_panneaupocket_end_at" value="<?php echo esc_attr( $end_at ); ?>" />
			</p>

			<p>
				<label for="hpk_pp_content"><?php esc_html_e( 'Contenu spécifique PanneauPocket (optionnel)', 'hpk-panneaupocket' ); ?></label><br />
				<textarea id="hpk_pp_content" name="_panneaupocket_content" rows="5" class="widefat"><?php echo esc_textarea( $content ); ?></textarea>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_panneaupocket_use_wp_content" value="1" <?php checked( $use_wp, '1' ); ?> />
					<?php esc_html_e( 'Utiliser le contenu WordPress nettoyé', 'hpk-panneaupocket' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_panneaupocket_use_featured" value="1" <?php checked( $use_thumb, '1' ); ?> />
					<?php esc_html_e( 'Utiliser l\'image mise en avant comme document', 'hpk-panneaupocket' ); ?>
				</label>
			</p>

			<div class="hpk-pp-documents">
				<label><?php esc_html_e( 'Documents supplémentaires (max 5, jpg/png/pdf)', 'hpk-panneaupocket' ); ?></label>
				<?php foreach ( $documents as $i => $doc ) : ?>
					<p class="hpk-pp-doc-row">
						<input type="url" name="_panneaupocket_documents[]" value="<?php echo esc_url( $doc ); ?>" class="widefat" placeholder="https://" />
						<button type="button" class="button hpk-pp-media-btn"><?php esc_html_e( 'Média', 'hpk-panneaupocket' ); ?></button>
					</p>
				<?php endforeach; ?>
				<button type="button" class="button hpk-pp-add-doc"><?php esc_html_e( 'Ajouter un document', 'hpk-panneaupocket' ); ?></button>
			</div>

			<p>
				<strong><?php esc_html_e( 'Identifiant externe :', 'hpk-panneaupocket' ); ?></strong>
				<code><?php echo esc_html( $external ); ?></code>
			</p>

			<div class="hpk-pp-metabox-actions">
				<button type="button" class="button button-primary hpk-pp-send-now"><?php esc_html_e( 'Envoyer maintenant', 'hpk-panneaupocket' ); ?></button>
				<button type="button" class="button hpk-pp-update-now"><?php esc_html_e( 'Mettre à jour sur PanneauPocket', 'hpk-panneaupocket' ); ?></button>
				<button type="button" class="button hpk-pp-preview-payload"><?php esc_html_e( 'Prévisualiser le payload', 'hpk-panneaupocket' ); ?></button>
				<a href="<?php echo esc_url( $logs_url ); ?>" class="button"><?php esc_html_e( 'Voir les logs', 'hpk-panneaupocket' ); ?></a>
			</div>

			<div class="hpk-pp-ajax-notice" style="display:none;"></div>
			<pre class="hpk-pp-payload-preview" style="display:none;"></pre>
		</div>
		<?php
	}
}
