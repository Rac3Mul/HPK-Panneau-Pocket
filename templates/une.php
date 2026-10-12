<?php
/**
 * À la une — list or single PanneauPocket news, inside the editor's page.
 *
 * @package HPK_PanneauPocket
 *
 * @var array|null $item Current news or null on the list.
 * @var array      $filtered News matching the search.
 * @var array      $recent Sidebar items.
 * @var string     $query Search text.
 * @var string     $base Page permalink.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$more_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M10.0035 3.4083L1.41176 12L0 10.5882L8.59171 1.99654H1.01905V0H12V10.981H10.0035V3.4083Z" fill="currentColor"/></svg>';
?>
<div class="hpk-pp-une">
	<div class="hpk-pp-une__main">
		<?php if ( $item ) : ?>
			<article class="hpk-pp-une__article">
				<a class="hpk-pp-une__back" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Toutes les actualités', 'hpk-panneaupocket' ); ?></a>
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
				<h2 class="hpk-pp-une__title"><?php echo esc_html( $item['title'] ); ?></h2>
				<?php if ( ! empty( $item['excerpt'] ) ) : ?>
					<p class="hpk-pp-une__lead"><?php echo esc_html( HPK_PP_Agenda::trim_text( preg_replace( '/\s+/u', ' ', $item['excerpt'] ), 180 ) ); ?></p>
				<?php endif; ?>
				<div class="hpk-pp-une__text"><?php echo wp_kses_post( $item['html'] ?? '' ); ?></div>
			</article>
		<?php else : ?>
			<?php if ( '' !== $query ) : ?>
				<h2 class="hpk-pp-une__title"><?php echo esc_html( sprintf( __( 'Recherche : %s', 'hpk-panneaupocket' ), $query ) ); ?></h2>
			<?php endif; ?>
			<?php if ( empty( $filtered ) ) : ?>
				<p class="hpk-pp-une__empty"><?php esc_html_e( 'Aucune actualité ne correspond.', 'hpk-panneaupocket' ); ?></p>
			<?php else : ?>
				<div class="hpk-pp-une__list">
					<?php foreach ( $filtered as $row ) : ?>
						<?php $link = add_query_arg( 'actu', rawurlencode( (string) $row['id'] ), $base ); ?>
						<article class="hpk-pp-une__card">
							<a class="hpk-pp-une__thumb" href="<?php echo esc_url( $link ); ?>">
								<?php if ( ! empty( $row['image'] ) ) : ?>
									<img src="<?php echo esc_url( $row['image'] ); ?>" alt="" />
								<?php endif; ?>
							</a>
							<div class="hpk-pp-une__copy">
								<?php if ( ! empty( $row['date'] ) ) : ?>
									<p class="hpk-pp-une__date"><?php echo esc_html( HPK_PP_Agenda::format_long_date( $row['date'] ) ); ?></p>
								<?php endif; ?>
								<h3 class="hpk-pp-une__card-title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $row['title'] ); ?></a></h3>
								<?php if ( ! empty( $row['excerpt'] ) ) : ?>
									<p class="hpk-pp-une__excerpt"><?php echo esc_html( HPK_PP_Agenda::trim_text( preg_replace( '/\s+/u', ' ', $row['excerpt'] ), 160 ) ); ?></p>
								<?php endif; ?>
								<a class="hpk-pp-une__more" href="<?php echo esc_url( $link ); ?>">
									<?php esc_html_e( 'En savoir plus', 'hpk-panneaupocket' ); ?>
									<?php echo $more_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<aside class="hpk-pp-une__side">
		<form class="hpk-pp-une__search" method="get" action="<?php echo esc_url( $base ); ?>" role="search">
			<label class="screen-reader-text" for="hpk-pp-une-q"><?php esc_html_e( 'Rechercher une actualité', 'hpk-panneaupocket' ); ?></label>
			<input id="hpk-pp-une-q" type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'Rechercher...', 'hpk-panneaupocket' ); ?>" />
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
							<a href="<?php echo esc_url( add_query_arg( 'actu', rawurlencode( (string) $row['id'] ), $base ) ); ?>">
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
