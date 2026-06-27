<?php
/**
 * News grid template.
 *
 * @package HPK_PanneauPocket
 * @var WP_Query $query Query object.
 * @var bool     $show_date Show date.
 * @var bool     $show_image Show image.
 * @var bool     $show_type Show type.
 * @var int      $excerpt_length Excerpt length.
 * @var bool     $pagination Enable pagination.
 * @var int      $paged Current page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $query->have_posts() ) {
	echo '<p class="hpk-pp-no-news">' . esc_html__( 'Aucune actualité PanneauPocket.', 'hpk-panneaupocket' ) . '</p>';
	return;
}
?>
<div class="hpk-pp-news hpk-pp-news--grid">
	<?php while ( $query->have_posts() ) : $query->the_post(); ?>
		<?php
		$type     = get_post_meta( get_the_ID(), '_panneaupocket_type', true ) ?: 'info';
		$pp_title = get_post_meta( get_the_ID(), '_panneaupocket_title', true ) ?: get_the_title();
		$link     = 'hpk_pp_sign' === get_post_type() ? '' : get_permalink();
		$doc_image = '';
		if ( $show_image && ! has_post_thumbnail() ) {
			$doc_image = HPK_PP_Document_Display::get_first_image_url(
				HPK_PP_Document_Display::get_post_documents( get_the_ID() )
			);
		}
		?>
		<article class="hpk-pp-news-item hpk-pp-news-item--<?php echo esc_attr( $type ); ?>">
			<?php if ( $show_image && has_post_thumbnail() ) : ?>
				<?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>" class="hpk-pp-news-thumb"><?php the_post_thumbnail( 'medium' ); ?></a><?php else : ?><div class="hpk-pp-news-thumb"><?php the_post_thumbnail( 'medium' ); ?></div><?php endif; ?>
			<?php elseif ( $show_image && $doc_image ) : ?>
				<?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>" class="hpk-pp-news-thumb"><img src="<?php echo esc_url( $doc_image ); ?>" alt="" loading="lazy" class="hpk-pp-news-doc-image" /></a><?php else : ?><div class="hpk-pp-news-thumb"><img src="<?php echo esc_url( $doc_image ); ?>" alt="" loading="lazy" class="hpk-pp-news-doc-image" /></div><?php endif; ?>
			<?php endif; ?>
			<div class="hpk-pp-news-body">
				<?php if ( $show_type ) : ?>
					<span class="hpk-pp-news-type hpk-pp-news-type--<?php echo esc_attr( $type ); ?>"><?php echo esc_html( strtoupper( $type ) ); ?></span>
				<?php endif; ?>
				<h3 class="hpk-pp-news-title"><?php if ( $link ) : ?><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $pp_title ); ?></a><?php else : ?><?php echo esc_html( $pp_title ); ?><?php endif; ?></h3>
				<?php if ( $show_date ) : ?>
					<time class="hpk-pp-news-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<?php endif; ?>
				<div class="hpk-pp-news-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $excerpt_length / 6 ) ); ?></div>
			</div>
		</article>
	<?php endwhile; ?>
</div>
<?php if ( $pagination && $query->max_num_pages > 1 ) : ?>
	<nav class="hpk-pp-pagination">
		<?php
		echo paginate_links(
			array(
				'total'   => $query->max_num_pages,
				'current' => $paged,
				'format'  => '?hpk_pp_page=%#%',
			)
		);
		?>
	</nav>
<?php endif; ?>
