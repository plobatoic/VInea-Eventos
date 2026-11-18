<?php
/**
 * CSS, JS, precargas y script de movimiento.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'vinea_encolar', 20 );
function vinea_encolar(): void {
	$css = VINEA_DIR . '/assets/css/vinea.css';
	wp_enqueue_style(
		'vinea',
		VINEA_URL . '/assets/css/vinea.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : VINEA_VERSION
	);

	$sitio = VINEA_DIR . '/assets/js/sitio.js';
	wp_enqueue_script(
		'vinea-sitio',
		VINEA_URL . '/assets/js/sitio.js',
		array(),
		file_exists( $sitio ) ? (string) filemtime( $sitio ) : VINEA_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// Portada: estilos y animación de la portada (gota 3D, portal, servicios en horizontal…) y entrada con el logo.
	if ( is_front_page() ) {
		foreach ( array( 'portada' => 'portada.css' ) as $id => $f ) {
			$ruta = VINEA_DIR . '/assets/css/' . $f;
			wp_enqueue_style( 'vinea-' . $id, VINEA_URL . '/assets/css/' . $f, array( 'vinea' ), file_exists( $ruta ) ? (string) filemtime( $ruta ) : VINEA_VERSION );
		}
		foreach ( array( 'entrada' => 'entrada.js', 'portada' => 'portada.js', 'original' => 'original.js' ) as $id => $f ) {
			$ruta = VINEA_DIR . '/assets/js/' . $f;
			wp_enqueue_script(
				'vinea-' . $id,
				VINEA_URL . '/assets/js/' . $f,
				array( 'vinea-sitio' ),
				file_exists( $ruta ) ? (string) filemtime( $ruta ) : VINEA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	// El tema no usa los estilos de bloques de WordPress en el front.
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}

// Sin jQuery en el front (salvo que lo pida un plugin explícitamente: se respeta si ya está en cola).
add_action(
	'wp_default_scripts',
	static function ( WP_Scripts $scripts ): void {
		if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
);

add_action( 'wp_head', 'vinea_cabecera_inicial', 1 );
function vinea_cabecera_inicial(): void {
	$fuentes = array(
		'gelasio-latin-400-normal.woff2',
		'gelasio-latin-400-italic.woff2',
		'barlow-latin-400-normal.woff2',
	);
	foreach ( $fuentes as $f ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( VINEA_URL . '/assets/fonts/' . $f )
		);
	}

	// Preferencia de movimiento (script en línea, antes del primer pintado).
	echo '<script>(function(){var d=document.documentElement,p=null;try{p=localStorage.getItem(\'vinea-movimiento\')}catch(e){}var r=window.matchMedia(\'(prefers-reduced-motion: reduce)\').matches;d.classList.add(p===\'off\'||(r&&p!==\'on\')?\'sin-movimiento\':\'movimiento\');if(d.classList.contains(\'movimiento\'))d.classList.add(\'portal-animado\');})();</script>' . "\n";

	// Datos para JS.
	echo '<script>window.VINEA = ' . wp_json_encode( array( 'tema' => VINEA_URL ), JSON_UNESCAPED_SLASHES ) . ';</script>' . "\n";
}
