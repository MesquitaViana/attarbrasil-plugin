<?php
/** SEO protection for catalog filter URLs. @package AttarBrasilStorefront */

defined( 'ABSPATH' ) || exit;

final class ABS_SEO {
	/** @var ABS_SEO|null */
	private static $instance = null;

	/** Singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Constructor. */
	private function __construct() {
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'wpseo_robots', array( $this, 'yoast_robots' ) );
		add_filter( 'wpseo_canonical', array( $this, 'yoast_canonical' ) );
		add_action( 'wp_head', array( $this, 'fallback_canonical' ), 1 );
	}

	/** Whether a technical catalog parameter is active. */
	public function is_filtered_request() {
		foreach ( array_keys( ABS_Query::request_keys() ) as $key ) {
			// Pagination is real catalog navigation, not a filter combination.
			if ( 'abs_pagina' === $key ) {
				continue;
			}
			if ( isset( $_GET[ $key ] ) && '' !== (string) wp_unslash( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}
		}
		return false;
	}

	/** WordPress robots array. */
	public function robots( $robots ) {
		if ( $this->is_filtered_request() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['nofollow'] );
		}
		return $robots;
	}

	/** Yoast robots fallback. */
	public function yoast_robots( $robots ) {
		return $this->is_filtered_request() ? 'noindex, follow' : $robots;
	}

	/** Canonical to the clean archive URL. */
	public function yoast_canonical( $canonical ) {
		return $this->is_filtered_request() ? ABS_Query::current_base_url() : $canonical;
	}

	/** Print canonical only when Yoast is not active. */
	public function fallback_canonical() {
		if ( $this->is_filtered_request() && ! defined( 'WPSEO_VERSION' ) ) {
			echo '<link rel="canonical" href="' . esc_url( ABS_Query::current_base_url() ) . '">' . "\n";
		}
	}
}
