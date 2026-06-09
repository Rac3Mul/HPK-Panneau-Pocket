<?php
/**
 * Floating widget template.
 *
 * @package HPK_PanneauPocket
 * @var string $url Iframe URL.
 * @var string $logo Logo URL.
 * @var string $position Position class suffix.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$position_class = 'bottom-left' === $position ? 'hpk-pp-floating--bottom-left' : 'hpk-pp-floating--bottom-right';
?>
<div class="hpk-pp-floating <?php echo esc_attr( $position_class ); ?>" id="hpk-pp-floating">
	<button type="button" class="hpk-pp-toggle" aria-expanded="false" aria-controls="hpk-pp-panel" aria-label="<?php esc_attr_e( 'Ouvrir PanneauPocket', 'hpk-panneaupocket' ); ?>">
		<img src="<?php echo esc_url( $logo ); ?>" alt="PanneauPocket" width="32" height="32" />
	</button>
	<div class="hpk-pp-panel" id="hpk-pp-panel" hidden>
		<button type="button" class="hpk-pp-close" aria-label="<?php esc_attr_e( 'Fermer', 'hpk-panneaupocket' ); ?>">&times;</button>
		<iframe
			src="<?php echo esc_url( $url ); ?>"
			title="<?php esc_attr_e( 'PanneauPocket', 'hpk-panneaupocket' ); ?>"
			loading="lazy"
			allow="fullscreen"
		></iframe>
	</div>
</div>
