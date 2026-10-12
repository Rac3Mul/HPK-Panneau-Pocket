<?php
/**
 * PanneauPocket news carousel.
 *
 * @package HPK_PanneauPocket
 *
 * @var array  $news News items.
 * @var string $button_text Button label.
 * @var int    $interval Autoplay delay in ms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $news ) ) {
	echo '<p class="hpk-pp-actus__empty">' . esc_html__( 'Aucune actualité PanneauPocket pour le moment.', 'hpk-panneaupocket' ) . '</p>';
	return;
}

$interval = isset( $interval ) ? absint( $interval ) : 3000;
?>
<div class="hpk-pp-actus" data-autoplay="<?php echo esc_attr( (string) $interval ); ?>">
	<div class="hpk-pp-actus__viewport">
		<div class="hpk-pp-actus__track">
			<?php foreach ( $news as $item ) : ?>
				<article class="hpk-pp-actus__slide">
					<div class="hpk-pp-actus__card" role="button" tabindex="0">
						<div class="hpk-pp-actus__media">
							<?php if ( ! empty( $item['image'] ) ) : ?>
								<img src="<?php echo esc_url( $item['image'] ); ?>" alt="" loading="lazy" />
							<?php endif; ?>
						</div>
						<div class="hpk-pp-actus__body">
							<?php if ( ! empty( $item['day'] ) ) : ?>
								<div class="hpk-pp-actus__date">
									<span class="hpk-pp-actus__day"><?php echo esc_html( $item['day'] ); ?></span>
									<span class="hpk-pp-actus__month"><?php echo esc_html( $item['month'] ); ?></span>
								</div>
							<?php endif; ?>
							<p class="hpk-pp-actus__meta"><?php esc_html_e( 'Actualité', 'hpk-panneaupocket' ); ?></p>
							<h3 class="hpk-pp-actus__title"><?php echo esc_html( $item['title'] ); ?></h3>
							<span class="hpk-pp-actus__more">
								<?php echo esc_html( $button_text ); ?>
								<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M10.0035 3.4083L1.41176 12L0 10.5882L8.59171 1.99654H1.01905V0H12V10.981H10.0035V3.4083Z" fill="currentColor"/></svg>
							</span>
						</div>
					</div>
					<template class="hpk-pp-actus__source">
						<div class="hpk-pp-agenda__full">
							<?php if ( ! empty( $item['image'] ) ) : ?>
								<img class="hpk-pp-agenda__full-image" src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" />
							<?php endif; ?>
							<div class="hpk-pp-agenda__full-copy">
								<?php if ( ! empty( $item['date_label'] ) ) : ?>
									<p class="hpk-pp-agenda__full-date"><?php echo esc_html( $item['date_label'] ); ?></p>
								<?php endif; ?>
								<h3 class="hpk-pp-agenda__full-title"><?php echo esc_html( $item['title'] ); ?></h3>
								<div class="hpk-pp-agenda__full-text"><?php echo wp_kses_post( $item['html'] ?? '' ); ?></div>
							</div>
						</div>
					</template>
				</article>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( count( $news ) > 1 ) : ?>
		<div class="hpk-pp-actus__dots" role="tablist" aria-label="<?php esc_attr_e( 'Actualités', 'hpk-panneaupocket' ); ?>"></div>
	<?php endif; ?>

	<div class="hpk-pp-agenda-modal" hidden>
		<div class="hpk-pp-agenda-modal__backdrop" data-close="1"></div>
		<div class="hpk-pp-agenda-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Actualité', 'hpk-panneaupocket' ); ?>">
			<button type="button" class="hpk-pp-agenda-modal__close" data-close="1" aria-label="<?php esc_attr_e( 'Fermer', 'hpk-panneaupocket' ); ?>">&times;</button>
			<div class="hpk-pp-agenda-modal__content"></div>
		</div>
	</div>
</div>
