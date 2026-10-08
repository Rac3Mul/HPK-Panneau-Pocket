<?php
/**
 * Agenda event list.
 *
 * @package HPK_PanneauPocket
 *
 * @var array  $events Events.
 * @var string $layout Layout slug.
 * @var bool   $show_image Show image.
 * @var bool   $show_excerpt Show excerpt.
 * @var int    $excerpt_length Excerpt length.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $events ) ) {
	echo '<p class="hpk-pp-agenda__empty">' . esc_html__( 'Aucun événement à venir sur PanneauPocket.', 'hpk-panneaupocket' ) . '</p>';
	return;
}

$dates = array();
foreach ( $events as $event ) {
	if ( ! empty( $event['date'] ) && ! isset( $dates[ $event['date'] ] ) ) {
		$dates[ $event['date'] ] = ! empty( $event['date_label'] ) ? $event['date_label'] : mysql2date( 'd/m/Y', $event['date'] );
	}
}
?>
<div class="hpk-pp-agenda-wrap">
	<?php if ( count( $dates ) > 1 ) : ?>
		<div class="hpk-pp-agenda__filters" role="tablist">
			<button type="button" class="hpk-pp-agenda__filter is-active" data-date=""><?php esc_html_e( 'Toutes les dates', 'hpk-panneaupocket' ); ?></button>
			<?php foreach ( $dates as $date => $label ) : ?>
				<button type="button" class="hpk-pp-agenda__filter" data-date="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="hpk-pp-agenda hpk-pp-agenda--<?php echo esc_attr( $layout ); ?>">
		<?php foreach ( $events as $event ) : ?>
			<article class="hpk-pp-agenda__item" data-date="<?php echo esc_attr( $event['date'] ); ?>">
				<div class="hpk-pp-agenda__open" role="button" tabindex="0">
					<?php if ( $show_image && ! empty( $event['image'] ) ) : ?>
						<span class="hpk-pp-agenda__media">
							<img src="<?php echo esc_url( $event['image'] ); ?>" alt="" loading="lazy" />
						</span>
					<?php endif; ?>
					<span class="hpk-pp-agenda__body">
						<?php if ( ! empty( $event['date_label'] ) ) : ?>
							<time class="hpk-pp-agenda__date"<?php echo ! empty( $event['date'] ) ? ' datetime="' . esc_attr( $event['date'] ) . '"' : ''; ?>>
								<?php echo esc_html( $event['date_label'] ); ?>
							</time>
						<?php endif; ?>
						<span class="hpk-pp-agenda__title"><?php echo esc_html( $event['title'] ); ?></span>
						<?php if ( $show_excerpt && ! empty( $event['excerpt'] ) ) : ?>
							<span class="hpk-pp-agenda__excerpt"><?php echo esc_html( HPK_PP_Agenda::trim_text( $event['excerpt'], $excerpt_length ) ); ?></span>
						<?php endif; ?>
						<span class="hpk-pp-agenda__more"><?php esc_html_e( 'Voir le détail', 'hpk-panneaupocket' ); ?></span>
					</span>
				</div>
				<template class="hpk-pp-agenda__source">
					<div class="hpk-pp-agenda__full">
						<?php if ( ! empty( $event['image'] ) ) : ?>
							<img class="hpk-pp-agenda__full-image" src="<?php echo esc_url( $event['image'] ); ?>" alt="<?php echo esc_attr( $event['title'] ); ?>" />
						<?php endif; ?>
						<div class="hpk-pp-agenda__full-copy">
							<?php if ( ! empty( $event['date_label'] ) ) : ?>
								<p class="hpk-pp-agenda__full-date"><?php echo esc_html( $event['date_label'] ); ?></p>
							<?php endif; ?>
							<h3 class="hpk-pp-agenda__full-title"><?php echo esc_html( $event['title'] ); ?></h3>
							<div class="hpk-pp-agenda__full-text"><?php echo wp_kses_post( $event['html'] ?? '' ); ?></div>
						</div>
					</div>
				</template>
			</article>
		<?php endforeach; ?>
	</div>
	<p class="hpk-pp-agenda__none" hidden><?php esc_html_e( 'Aucun événement à cette date.', 'hpk-panneaupocket' ); ?></p>

	<div class="hpk-pp-agenda-modal" hidden>
		<div class="hpk-pp-agenda-modal__backdrop" data-close="1"></div>
		<div class="hpk-pp-agenda-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Détail de l\'événement', 'hpk-panneaupocket' ); ?>">
			<button type="button" class="hpk-pp-agenda-modal__close" data-close="1" aria-label="<?php esc_attr_e( 'Fermer', 'hpk-panneaupocket' ); ?>">&times;</button>
			<div class="hpk-pp-agenda-modal__content"></div>
		</div>
	</div>
</div>
