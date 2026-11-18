<?php
/**
 * Cabecera del tema Vinea: <head>, barra de progreso, cabecera fija con el menú
 * "Principal" (editable en Apariencia → Menús) y apertura de <main>.
 */
$tema = get_template_directory_uri();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f7f3ec">
	<link rel="icon" href="<?php echo esc_url( $tema . '/assets/img/favicon.svg' ); ?>" type="image/svg+xml">
	<link rel="icon" href="<?php echo esc_url( $tema . '/assets/img/favicon-32.png' ); ?>" sizes="32x32" type="image/png">
	<link rel="apple-touch-icon" href="<?php echo esc_url( $tema . '/assets/img/apple-touch-icon.png' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if ( is_front_page() ) : ?>
<script>(function(){var d=document.documentElement;try{if(sessionStorage.getItem('vinea-intro')||d.classList.contains('sin-movimiento')){d.classList.add('sin-intro')}else{sessionStorage.setItem('vinea-intro','1');d.classList.add('con-intro')}}catch(e){d.classList.add('sin-intro')}})();</script>
<div class="ilogo" aria-hidden="true" data-ilogo>
	<span class="ilogo__fondo"></span>
	<div class="ilogo__logo">
		<?php echo file_get_contents( get_template_directory() . '/partes/logo-animado.svg' ); // phpcs:ignore -- SVG propio del tema. ?>
		<span class="ilogo__gota"></span>
	</div>
	<span class="ilogo__saltar">Saltar</span>
</div>
<?php endif; ?>
<a class="saltar" href="#contenido">Saltar al contenido</a>
<div class="progreso" aria-hidden="true"></div>

<header class="cabecera" data-cabecera>
	<div class="cabecera__in">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cabecera__logo" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>, ir a Home">
			<img src="<?php echo esc_url( $tema . '/assets/img/logo-vinea.svg' ); ?>" alt="" width="1353" height="435">
		</a>

		<nav class="cabecera__nav" id="menu-principal" aria-label="Principal" data-menu data-lenis-prevent>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'principal',
					'container'      => false,
					'menu_class'     => 'menu-principal',
					'depth'          => 2,
					'fallback_cb'    => false,
				)
			);
			?>
			<p class="cabecera__solo-movil cabecera__dato">
				<a href="mailto:<?php echo esc_attr( function_exists( 'vinea_ajuste' ) ? vinea_ajuste( 'email_publico' ) : 'info@vineaeventos.com' ); ?>"><?php echo esc_html( function_exists( 'vinea_ajuste' ) ? vinea_ajuste( 'email_publico' ) : 'info@vineaeventos.com' ); ?></a>
			</p>
		</nav>

		<?php if ( function_exists( 'vinea_boton_carrito' ) ) { vinea_boton_carrito(); } ?>

		<a class="boton boton--naranja cabecera__cta" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">cuéntanos tu idea <span class="flecha" aria-hidden="true">→</span></a>

		<button class="cabecera__boton" type="button" aria-expanded="false" aria-controls="menu-principal" data-menu-boton>
			<span class="sr-only" data-menu-texto>Abrir menú</span>
			<span class="hamb" aria-hidden="true"><span></span><span></span></span>
		</button>
	</div>
</header>

<main id="contenido" tabindex="-1">
