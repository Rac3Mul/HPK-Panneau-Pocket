<?php
/**
 * À la une — archive or single PanneauPocket news.
 *
 * @package HPK_PanneauPocket
 *
 * @var array|null $item Current news or null on the archive.
 * @var array      $news All news.
 * @var array      $filtered News matching the search.
 * @var array      $recent Sidebar items.
 * @var string     $query Search text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div id="pxl-main" class="hpk-pp-une-page">
	<div class="hpk-pp-une">
		<div class="hpk-pp-une__main">
			<?php if ( $item ) : ?>
				<article class="hpk-pp-une__article">
					<?php if ( ! empty( $item['image'] ) ) : ?>
						<img class="hpk-pp-une__hero" src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" />
					<?php endif; ?>
					<p class="hpk-pp-une__meta">
						<?php if ( ! empty( $item['date'] ) ) : ?>
							<span><?php echo esc_html( HPK_PP_Agenda::format_long_date( $item['date'] ) ); ?></span>
							<span class="hpk-pp-une__sep" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="hpk-pp-une__label"><?php esc_html_e( 'Actualité', 'hpk-panneaupocket' ); ?></span>
					</p>
					<h1 class="hpk-pp-une__title"><?php echo esc_html( $item['title'] ); ?></h1>
					<div class="hpk-pp-une__text"><?php echo wp_kses_post( $item['html'] ?? '' ); ?></div>
				</article>
			<?php else : ?>
				<h1 class="hpk-pp-une__title"><?php echo '' !== $query ? esc_html( sprintf( __( 'Recherche : %s', 'hpk-panneaupocket' ), $query ) ) : esc_html__( 'À la une', 'hpk-panneaupocket' ); ?></h1>
				<?php if ( empty( $filtered ) ) : ?>
					<p class="hpk-pp-une__empty"><?php esc_html_e( 'Aucune actualité ne correspond.', 'hpk-panneaupocket' ); ?></p>
				<?php else : ?>
					<div class="hpk-pp-une__list">
						<?php foreach ( $filtered as $row ) : ?>
							<a class="hpk-pp-une__result" href="<?php echo esc_url( HPK_PP_Agenda::news_url( $row['id'] ) ); ?>">
								<?php if ( ! empty( $row['image'] ) ) : ?>
									<img src="<?php echo esc_url( $row['image'] ); ?>" alt="" />
								<?php endif; ?>
								<span>
									<?php if ( ! empty( $row['date'] ) ) : ?>
										<small><?php echo esc_html( HPK_PP_Agenda::format_long_date( $row['date'] ) ); ?></small>
									<?php endif; ?>
									<strong><?php echo esc_html( $row['title'] ); ?></strong>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<aside class="hpk-pp-une__side">
			<form class="hpk-pp-une__search" method="get" action="<?php echo esc_url( HPK_PP_Agenda::archive_url() ); ?>">
				<input type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'Rechercher...', 'hpk-panneaupocket' ); ?>" />
				<button type="submit" aria-label="<?php esc_attr_e( 'Rechercher', 'hpk-panneaupocket' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 16 L20.5 20.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>
			</form>
			<section class="hpk-pp-une__recent">
				<h2><?php esc_html_e( 'News récentes', 'hpk-panneaupocket' ); ?></h2>
				<?php if ( empty( $recent ) ) : ?>
					<p class="hpk-pp-une__empty"><?php esc_html_e( 'Aucune autre actualité.', 'hpk-panneaupocket' ); ?></p>
				<?php else : ?>
					<ul>
						<?php foreach ( $recent as $row ) : ?>
							<li>
								<a href="<?php echo esc_url( HPK_PP_Agenda::news_url( $row['id'] ) ); ?>">
									<?php if ( ! empty( $row['image'] ) ) : ?>
										<img src="<?php echo esc_url( $row['image'] ); ?>" alt="" />
									<?php endif; ?>
									<span>
										<?php if ( ! empty( $row['date'] ) ) : ?>
											<small><?php echo esc_html( HPK_PP_Agenda::format_long_date( $row['date'] ) ); ?></small>
										<?php endif; ?>
										<strong><?php echo esc_html( $row['title'] ); ?></strong>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		</aside>
	</div>
</div>
<?php
get_footer();
