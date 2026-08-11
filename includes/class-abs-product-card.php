<?php
/** Product card renderer. @package AttarBrasilStorefront */

defined( 'ABSPATH' ) || exit;

class ABS_Product_Card {
	/** Render one card. */
	public function render( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		ob_start();
		include ABS_PATH . 'templates/product-card.php';
		return (string) ob_get_clean();
	}

	/** Read ACF first, post meta second. */
	public static function field( $name, $product_id ) {
		$value = function_exists( 'get_field' ) ? get_field( $name, $product_id ) : null;
		return ( null !== $value && false !== $value && '' !== $value ) ? $value : get_post_meta( $product_id, $name, true );
	}

	/** Normalize ACF, meta or WooCommerce attribute values into readable labels. */
	private static function labels( $value ) {
		if ( $value instanceof WP_Term ) return array( $value->name );
		if ( is_array( $value ) ) {
			$labels = array();
			foreach ( $value as $item ) $labels = array_merge( $labels, self::labels( $item ) );
			return array_values( array_filter( array_unique( $labels ) ) );
		}
		if ( ! is_scalar( $value ) ) return array();
		return array_values( array_filter( array_map( 'trim', preg_split( '/[,|]+/', wp_strip_all_tags( (string) $value ) ) ) ) );
	}

	/** First populated value from ACF/meta fields or WooCommerce attributes. */
	public static function product_labels( $product, $fields, $attributes ) {
		foreach ( $fields as $field ) {
			$labels = self::labels( self::field( $field, $product->get_id() ) );
			if ( $labels ) return $labels;
		}
		foreach ( $attributes as $attribute ) {
			$value = $product->get_attribute( $attribute );
			$labels = self::labels( $value );
			if ( $labels ) return $labels;
			if ( taxonomy_exists( $attribute ) ) {
				$terms = get_the_terms( $product->get_id(), $attribute );
				if ( is_array( $terms ) ) {
					$labels = self::labels( $terms );
					if ( $labels ) return $labels;
				}
			}
		}
		return array();
	}

	/** Two olfactory tags used by the reference card. */
	public static function card_tags( $product ) {
		$primary = self::product_labels(
			$product,
			array( 'familia_olfativa', 'familia-olfativa', 'grupo_olfativo' ),
			array( 'pa_familia-olfativa', 'pa_familia_olfativa', 'pa_grupo-olfativo', 'pa_grupo_olfativo' )
		);
		$secondary = self::product_labels(
			$product,
			array( 'subfamilia_olfativa', 'subfamilia-olfativa', 'acorde_principal' ),
			array( 'pa_subfamilia-olfativa', 'pa_subfamilia_olfativa', 'pa_acorde-principal', 'pa_acorde_principal' )
		);
		$tags = array_merge( $primary, $secondary );
		return array_slice( array_values( array_filter( array_unique( $tags ) ) ), 0, 2 );
	}

	/** Extract a readable brand. */
	public static function brand( $product_id ) {
		foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_the_terms( $product_id, $taxonomy );
				if ( is_array( $terms ) && $terms ) {
					$name = $terms[0]->name;
					return trim( preg_split( '/\s+[–—]\s+/', $name )[0] );
				}
			}
		}

		$value = self::field( 'marca', $product_id );
		if ( is_scalar( $value ) ) return trim( preg_split( '/\s+[–—]\s+/', (string) $value )[0] );
		return '';
	}

	/** Minimum display prices for simple/variable products. */
	public static function prices( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$current = (float) $product->get_variation_price( 'min', true );
			$regular = (float) $product->get_variation_regular_price( 'min', true );
		} else {
			$current = (float) wc_get_price_to_display( $product );
			$regular = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		}

		return array( $current, $regular );
	}
}
