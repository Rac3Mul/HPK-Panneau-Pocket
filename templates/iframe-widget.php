<?php
/**
 * Iframe widget template.
 *
 * @package HPK_PanneauPocket
 * @var string $url Iframe URL.
 * @var string $style Inline style.
 * @var string $iframe_mode widget|widgetTv.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrap_class = 'hpk-pp-iframe-wrap hpk-pp-iframe-wrap--' . esc_attr( $iframe_mode ?? 'widget' );
?>
<div class="<?php echo esc_attr( $wrap_class ); ?>">
	<iframe
		src="<?php echo esc_url( $url ); ?>"
		style="<?php echo esc_attr( $style ); ?>"
		title="<?php esc_attr_e( 'PanneauPocket', 'hpk-panneaupocket' ); ?>"
		loading="lazy"
		allow="fullscreen"
	></iframe>
</div>
