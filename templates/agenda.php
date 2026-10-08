<?php
/**
 * Agenda event list.
 *
 * @package HPK_PanneauPocket
 *
 * @var array $events Events.
 * @var string $layout Layout slug.
 * @var bool $show_image Show image.
 * @var bool $show_excerpt Show excerpt.
 * @var int $excerpt_length Excerpt length.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $events ) ) {
	echo '<p class="hpk-pp-agenda__empty">' . esc_html__( 'Aucun événement à venir sur PanneauPocket.', 'hpk-panneaupocket' ) . '</p>';
	return;
}
?>
<div class="hpk-pp-agenda hpk-pp-agenda--<?php echo esc_attr( $layout ); ?>">
	<?php foreach ( $events as $event ) : ?>
		<article class="hpk-pp-agenda__item">
			<a class="hpk-pp-agenda__link" href="<?php echo esc_url( $event['url'] ); ?>" target="_blank" rel="noopener noreferrer">
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
						<span class="hpk-pp-agenda__excerpt"><?php echo esc_html( wp_html_excerpt( $event['excerpt'], $excerpt_length, '…' ) ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		</article>
	<?php endforeach; ?>
</div>
