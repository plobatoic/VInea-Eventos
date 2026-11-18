<?php
/**
 * Página no encontrada.
 */
get_header();
?>
<section class="error contenedor">
	<p class="error__num" aria-hidden="true">4<span class="error__gota"></span>4</p>
	<h1 class="titulo-1" data-split>Página no encontrada</h1>
	<div class="error__acciones">
		<a class="boton" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir a Home <span class="flecha" aria-hidden="true">→</span></a>
		<a class="boton boton--linea" href="<?php echo esc_url( home_url( '/servicios/' ) ); ?>">Servicios</a>
		<a class="boton boton--linea" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contacto</a>
	</div>
</section>
<?php
get_footer();
