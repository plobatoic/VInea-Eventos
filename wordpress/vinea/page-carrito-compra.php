<?php
/**
 * Carrito (URL /carrito-compra/ como en la web original). Lo pinta tienda.js desde el navegador.
 */
get_header();
?>
<section class="tienda seccion" data-pagina-carrito>
	<div class="contenedor">
		<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><li><a href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">Entradas</a></li><li><span aria-current="page">Carrito</span></li></ol></nav>
		<ol class="pasos" aria-label="Pasos de la compra">
			<li class="pasos__paso pasos__paso--activo" aria-current="step"><span>1</span> Carrito</li>
			<li class="pasos__paso"><span>2</span> Finalizar compra</li>
			<li class="pasos__paso"><span>3</span> Pedido recibido</li>
		</ol>
		<h1 class="titulo-1 tienda__titulo">Carrito</h1>

		<div class="tienda__grid" data-carrito-lleno hidden>
			<div class="tienda__caja">
				<p class="tienda__cuenta" data-carrito-cuenta></p>
				<ul class="lineas" role="list" data-carrito-lista></ul>
			</div>
			<aside class="tienda__resumen tienda__caja">
				<p class="tienda__resumen-titulo">Total del carrito</p>
				<dl class="tienda__sumas">
					<div><dt>Subtotal</dt><dd data-carrito-suma>0,00 €</dd></div>
					<div class="tienda__total"><dt>Total</dt><dd data-carrito-suma>0,00 €</dd></div>
				</dl>
				<a class="boton boton--naranja tienda__seguir" href="<?php echo esc_url( vinea_url_pago() ); ?>">Finalizar compra <span class="flecha" aria-hidden="true">→</span></a>
				<a class="tienda__volver" href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">← Volver a la tienda</a>
			</aside>
		</div>

		<div class="carrito-vacio" data-carrito-vacio>
			<span class="carrito-vacio__gota" aria-hidden="true"></span>
			<p class="carrito-vacio__texto">Tu carrito está vacío.</p>
			<a class="boton boton--naranja" href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">Volver a la tienda <span class="flecha" aria-hidden="true">→</span></a>
		</div>
	</div>
</section>
<?php
get_footer();
