<?php
/** Catalog template. @package AttarBrasilStorefront */
defined( 'ABSPATH' ) || exit;

$show_content = 'nao' !== sanitize_key( $atts['mostrar_conteudo'] );
$show_categories = 'nao' !== sanitize_key( $atts['mostrar_categorias'] );
$selected_query = array();
foreach ( ABS_Query::request_keys() as $request_key => $unused ) {
	if ( isset( $_GET[ $request_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_query[ $request_key ] = wp_unslash( $_GET[ $request_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
$active_count = 0;
foreach ( array( 'price', 'brand', 'category', 'family', 'volume', 'concentration', 'gender' ) as $state_key ) {
	$active_count += is_array( $state[ $state_key ] ) ? count( $state[ $state_key ] ) : ( $state[ $state_key ] ? 1 : 0 );
}
$active_count += $state['stock'] ? 1 : 0;
$active_count += $state['sale'] ? 1 : 0;
?>
<section class="attar-catalog" aria-label="<?php echo esc_attr( $content['title'] ); ?>">
	<?php if ( 'nao' !== sanitize_key( $atts['mostrar_breadcrumb'] ) && function_exists( 'woocommerce_breadcrumb' ) ) : ?><div class="attar-catalog__breadcrumb"><?php woocommerce_breadcrumb(); ?></div><?php endif; ?>
	<?php if ( 'sim' === sanitize_key( $atts['mostrar_titulo'] ) ) : ?>
		<h1 class="attar-catalog__title"><?php echo esc_html( $content['title'] ); ?></h1>
	<?php endif; ?>

	<?php if ( $show_content && $content['top'] ) : ?>
		<div class="attar-catalog__intro-wrap" data-abs-description>
			<div class="attar-catalog__intro is-collapsed" data-abs-description-content><?php echo wp_kses_post( wpautop( do_shortcode( $content['top'] ) ) ); ?></div>
			<button type="button" class="attar-catalog__intro-toggle" data-abs-description-toggle aria-expanded="false">Saiba mais <span aria-hidden="true">⌄</span></button>
		</div>
	<?php endif; ?>

	<div class="attar-catalog__toolbar">
		<p class="attar-catalog__count"><?php echo esc_html( sprintf( _n( '%s resultado', '%s resultados', $result['found'], 'attar-brasil-storefront' ), number_format_i18n( $result['found'] ) ) ); ?></p>
		<button class="attar-catalog__filter-toggle" type="button" data-abs-filter-toggle aria-expanded="false" aria-controls="attar-catalog-filters"><span class="attar-filter-icon" aria-hidden="true"></span>Filtrar<?php if ( $active_count ) : ?><b><?php echo esc_html( $active_count ); ?></b><?php endif; ?></button>
		<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="attar-catalog__sort">
			<?php foreach ( $selected_query as $key => $value ) :
				if ( 'abs_ordem' === $key || 'abs_pagina' === $key ) continue;
				foreach ( (array) $value as $hidden_value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key . ( is_array( $value ) ? '[]' : '' ) ); ?>" value="<?php echo esc_attr( $hidden_value ); ?>">
				<?php endforeach;
			endforeach; ?>
			<label class="screen-reader-text" for="abs-order">Ordenar produtos</label>
			<select id="abs-order" name="abs_ordem" onchange="this.form.submit()">
				<option value="popularity" <?php selected( $state['order'], 'popularity' ); ?>>Mais vendidos</option>
				<option value="date" <?php selected( $state['order'], 'date' ); ?>>Mais recentes</option>
				<option value="price" <?php selected( $state['order'], 'price' ); ?>>Menor preço</option>
				<option value="price-desc" <?php selected( $state['order'], 'price-desc' ); ?>>Maior preço</option>
				<option value="rating" <?php selected( $state['order'], 'rating' ); ?>>Melhor avaliados</option>
			</select>
		</form>
	</div>
	<?php if ( $active_count ) : ?>
		<div class="attar-active-filters"><span>Filtros ativos:</span><a href="<?php echo esc_url( $base_url ); ?>">Limpar todos <i aria-hidden="true">×</i></a></div>
	<?php endif; ?>

	<div class="attar-catalog__layout">
		<aside class="attar-catalog__filters" id="attar-catalog-filters" data-abs-filters>
			<div class="attar-filters__mobile-head"><strong>Filtrar produtos</strong><button type="button" data-abs-filter-close aria-label="Fechar filtros">×</button></div>
			<form method="get" action="<?php echo esc_url( $base_url ); ?>">
				<?php if ( 'popularity' !== $state['order'] ) : ?><input type="hidden" name="abs_ordem" value="<?php echo esc_attr( $state['order'] ); ?>"><?php endif; ?>
				<details class="attar-filter"<?php echo $state['price'] ? ' open' : ''; ?>><summary><span>Preço</span><i aria-hidden="true"></i></summary><div class="attar-filter__options">
					<?php foreach ( apply_filters( 'abs_storefront_price_ranges', array( '0-200' => 'Até R$ 200', '200-350' => 'R$ 200 – R$ 350', '350-500' => 'R$ 350 – R$ 500', '500-' => 'Acima de R$ 500' ) ) as $value => $label ) : ?>
						<label><input type="radio" name="abs_preco" value="<?php echo esc_attr( $value ); ?>" <?php checked( $state['price'], $value ); ?>> <span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</div></details>

				<?php
				$groups = array(
					array( 'Marcas', 'abs_marca', $filters['brands'], $state['brand'] ),
					array( 'Categorias', 'abs_categoria', $filters['categories'], $state['category'] ),
					array( 'Família olfativa', 'abs_familia', $filters['families'], $state['family'] ),
					array( 'Quantidade (ml)', 'abs_volume', $filters['volumes'], $state['volume'] ),
					array( 'Concentração', 'abs_concentracao', $filters['concentrations'], $state['concentration'] ),
					array( 'Gênero', 'abs_genero', $filters['genders'], $state['gender'] ),
				);
				foreach ( $groups as $group ) :
					if ( empty( $group[2] ) || ( 'Categorias' === $group[0] && ! $show_categories ) ) continue;
				?>
					<details class="attar-filter"<?php echo ! empty( $group[3] ) ? ' open' : ''; ?>><summary><span><?php echo esc_html( $group[0] ); ?></span><i aria-hidden="true"></i></summary>
						<div class="attar-filter__options">
						<?php foreach ( $group[2] as $term ) : ?>
							<label><input type="checkbox" name="<?php echo esc_attr( $group[1] ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $group[3], true ) ); ?>> <span><?php echo esc_html( $term->name ); ?></span></label>
						<?php endforeach; ?>
						</div>
					</details>
				<?php endforeach; ?>

				<details class="attar-filter"<?php echo ( $state['stock'] || $state['sale'] ) ? ' open' : ''; ?>><summary><span>Disponibilidade</span><i aria-hidden="true"></i></summary><div class="attar-filter__options">
					<label><input type="checkbox" name="abs_estoque" value="1" <?php checked( $state['stock'] ); ?>> <span>Em estoque</span></label>
					<label><input type="checkbox" name="abs_oferta" value="1" <?php checked( $state['sale'] ); ?>> <span>Em oferta</span></label>
				</div></details>
				<div class="attar-filter__actions">
					<button type="submit">Aplicar filtros</button>
					<a href="<?php echo esc_url( $base_url ); ?>">Limpar</a>
				</div>
			</form>
		</aside>
		<div class="attar-filter-backdrop" data-abs-filter-close aria-hidden="true"></div>

		<div class="attar-catalog__products" data-abs-catalog-products>
			<?php echo $this->render_catalog_grid( $result['products'], $columns ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $result['max_pages'] > 1 ) : ?>
				<?php $pagination_query = $selected_query; unset( $pagination_query['abs_pagina'] );
				$next_url = $result['page'] < $result['max_pages'] ? add_query_arg( array_merge( $pagination_query, array( 'abs_pagina' => $result['page'] + 1 ) ), $base_url ) : ''; ?>
				<?php if ( $next_url ) : ?><div class="attar-load-more-wrap"><a class="attar-load-more" href="<?php echo esc_url( $next_url ); ?>" rel="next" data-abs-load-more data-current="<?php echo esc_attr( $result['page'] ); ?>" data-total="<?php echo esc_attr( $result['max_pages'] ); ?>"><span>Carregar mais produtos</span><i aria-hidden="true"></i></a><button type="button" class="attar-show-less" data-abs-show-less hidden>Ver menos</button><p class="attar-load-more__status" aria-live="polite"></p></div><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $show_content && $content['bottom'] ) : ?>
		<div class="attar-catalog__seo-content"><?php echo wp_kses_post( wpautop( do_shortcode( $content['bottom'] ) ) ); ?></div>
	<?php endif; ?>
</section>
