<?php
/**
 * Configuración básica del tema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'vinea_setup' );
function vinea_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus(
		array(
			'principal' => 'Menú principal',
			'pie'       => 'Menú del pie',
		)
	);

	set_post_thumbnail_size( 1200, 800, true );
	add_image_size( 'vinea-panoramica', 1600, 900, true );
}

// Tamaños de imagen razonables (los valores por defecto de WordPress son pequeños).
add_filter( 'big_image_size_threshold', static fn() => 2200 );
add_filter(
	'intermediate_image_sizes_advanced',
	static function ( array $tamanos ): array {
		unset( $tamanos['1536x1536'], $tamanos['2048x2048'] );
		return $tamanos;
	}
);
add_action(
	'init',
	static function (): void {
		// Solo si la opción aún tiene el valor de fábrica.
		if ( (int) get_option( 'large_size_w' ) === 1024 ) {
			update_option( 'large_size_w', 1600 );
			update_option( 'large_size_h', 1600 );
		}
		if ( (int) get_option( 'medium_large_size_w' ) === 768 ) {
			update_option( 'medium_large_size_w', 900 );
		}
	}
);

// Guardar JPEG y PNG como WebP al generar los tamaños de imagen.
add_filter(
	'image_editor_output_format',
	static function ( array $formatos ): array {
		$formatos['image/jpeg'] = 'image/webp';
		$formatos['image/png']  = 'image/webp';
		return $formatos;
	}
);

// Quitar emojis de WordPress.
add_action(
	'init',
	static function (): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
);
add_filter(
	'tiny_mce_plugins',
	static fn( $plugins ) => is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array()
);
add_filter(
	'wp_resource_hints',
	static function ( array $urls, string $relation ): array {
		if ( 'dns-prefetch' === $relation ) {
			$urls = array_filter(
				$urls,
				static fn( $u ) => ! is_string( $u ) || false === strpos( $u, 's.w.org' )
			);
		}
		return $urls;
	},
	10,
	2
);

// Es el padre de la página la de slug «servicios»?
function vinea_es_servicio( $post = null ): bool {
	$post = get_post( $post );
	if ( ! $post || 'page' !== $post->post_type || ! $post->post_parent ) {
		return false;
	}
	$padre = get_post( $post->post_parent );
	return $padre && 'servicios' === $padre->post_name;
}

// Clase de página en <body>.
add_filter( 'body_class', 'vinea_body_class' );
function vinea_body_class( array $clases ): array {
	if ( is_page_template( 'template-landing.php' ) ) {
		$clases[] = 'pg-landing';
	}

	if ( is_front_page() ) {
		$clases[] = 'pg-inicio';
	} elseif ( is_404() ) {
		$clases[] = 'pg-404';
	} elseif ( is_singular( 'post' ) ) {
		$clases[] = 'pg-proyecto';
	} elseif ( is_home() || is_category() || is_tag() || is_author() || is_date() || is_archive() || is_search() ) {
		$clases[] = 'pg-proyectos';
	} elseif ( is_page() ) {
		if ( vinea_es_servicio() ) {
			$clases[] = 'pg-servicio';
		} elseif ( ! is_page_template( 'template-landing.php' ) ) {
			$clases[] = 'pg-pagina';
			if ( is_page( 'contacto' ) ) {
				$clases[] = 'pg-contacto';
			}
		}
	}
	return $clases;
}

// 12 proyectos por página en el listado.
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ): void {
		if ( ! is_admin() && $q->is_main_query() && ( $q->is_home() || $q->is_archive() ) ) {
			$q->set( 'posts_per_page', 12 );
		}
	}
);
