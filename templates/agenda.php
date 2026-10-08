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
 * @var int    $autoplay Autoplay delay in ms, 0 to disable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $events ) ) {
	echo '<p class="hpk-pp-agenda__empty">' . esc_html__( 'Aucun événement à venir sur PanneauPocket.', 'hpk-panneaupocket' ) . '</p>';
	return;
}

$dates    = array();
$is_slider = ( 'slider' === $layout );
$autoplay  = isset( $autoplay ) ? absint( $autoplay ) : 0;
foreach ( $events as $event ) {
	if ( ! empty( $event['date'] ) && ! isset( $dates[ $event['date'] ] ) ) {
		$dates[ $event['date'] ] = ! empty( $event['date_label'] ) ? $event['date_label'] : mysql2date( 'd/m/Y', $event['date'] );
	}
}
?>
<div class="hpk-pp-agenda-wrap<?php echo $is_slider ? ' hpk-pp-agenda-wrap--slider' : ''; ?>">
	<?php if ( ! $is_slider && count( $dates ) > 1 ) : ?>
		<div class="hpk-pp-agenda__filters" role="tablist">
			<button type="button" class="hpk-pp-agenda__filter is-active" data-date=""><?php esc_html_e( 'Toutes les dates', 'hpk-panneaupocket' ); ?></button>
			<?php foreach ( $dates as $date => $label ) : ?>
				<button type="button" class="hpk-pp-agenda__filter" data-date="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $is_slider ) : ?>
		<div class="hpk-pp-slider" data-autoplay="<?php echo esc_attr( (string) $autoplay ); ?>">
			<?php if ( count( $events ) > 1 ) : ?>
				<button type="button" class="hpk-pp-slider__nav hpk-pp-slider__nav--prev" aria-label="<?php esc_attr_e( 'Événement précédent', 'hpk-panneaupocket' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.5 5.5 L7.5 12 L14.5 18.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
			<?php endif; ?>
			<div class="hpk-pp-slider__viewport">
	<?php endif; ?>

	<div class="hpk-pp-agenda hpk-pp-agenda--<?php echo esc_attr( $layout ); ?><?php echo $is_slider ? ' hpk-pp-slider__track' : ''; ?>">
		<?php foreach ( $events as $index => $event ) : ?>
			<article class="hpk-pp-agenda__item<?php echo $is_slider ? ' hpk-pp-slider__slide' : ''; ?>" data-date="<?php echo esc_attr( $event['date'] ); ?>">
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

	<?php if ( $is_slider ) : ?>
			</div>
			<?php if ( count( $events ) > 1 ) : ?>
				<button type="button" class="hpk-pp-slider__nav hpk-pp-slider__nav--next" aria-label="<?php esc_attr_e( 'Événement suivant', 'hpk-panneaupocket' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.5 5.5 L16.5 12 L9.5 18.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
				<div class="hpk-pp-slider__dots" role="tablist" aria-label="<?php esc_attr_e( 'Événements', 'hpk-panneaupocket' ); ?>">
					<?php foreach ( $events as $index => $event ) : ?>
						<button type="button" class="hpk-pp-slider__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr( (string) $index ); ?>" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $event['title'] ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! $is_slider ) : ?>
		<p class="hpk-pp-agenda__none" hidden><?php esc_html_e( 'Aucun événement à cette date.', 'hpk-panneaupocket' ); ?></p>
	<?php endif; ?>

	<div class="hpk-pp-agenda-modal" hidden>
		<div class="hpk-pp-agenda-modal__backdrop" data-close="1"></div>
		<div class="hpk-pp-agenda-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Détail de l\'événement', 'hpk-panneaupocket' ); ?>">
			<button type="button" class="hpk-pp-agenda-modal__close" data-close="1" aria-label="<?php esc_attr_e( 'Fermer', 'hpk-panneaupocket' ); ?>">&times;</button>
			<div class="hpk-pp-agenda-modal__content"></div>
		</div>
	</div>
</div>
