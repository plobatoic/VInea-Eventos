<?php
/**
 * Finalizar compra (URL /finalizar-compra/ como en la web original): datos de facturación + resumen.
 * Tras enviar: «Pedido recibido» con el detalle (precios calculados en el servidor).
 */
get_header();
$pedido = function_exists( 'vinea_pedido_recibido' ) ? vinea_pedido_recibido() : null;
$error  = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore
$errores = array(
	'campos'    => 'Revisa los campos obligatorios e inténtalo de nuevo.',
	'vacio'     => 'Tu carrito está vacío.',
	'seguridad' => 'No hemos podido enviarlo. Inténtalo de nuevo en unos minutos.',
	'envio'     => 'No hemos podido enviarlo. Inténtalo de nuevo en unos minutos.',
);
?>
<section class="tienda seccion"<?php echo $pedido ? ' data-pedido-ok' : ' data-pagina-pago'; ?>>
	<div class="contenedor">
		<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><li><a href="<?php echo esc_url( vinea_url_carrito() ); ?>">Carrito</a></li><li><span aria-current="page">Finalizar compra</span></li></ol></nav>
		<ol class="pasos" aria-label="Pasos de la compra">
			<li class="pasos__paso pasos__paso--hecho"><span>1</span> Carrito</li>
			<li class="pasos__paso<?php echo $pedido ? ' pasos__paso--hecho' : ' pasos__paso--activo'; ?>"<?php echo $pedido ? '' : ' aria-current="step"'; ?>><span>2</span> Finalizar compra</li>
			<li class="pasos__paso<?php echo $pedido ? ' pasos__paso--activo' : ''; ?>"<?php echo $pedido ? ' aria-current="step"' : ''; ?>><span>3</span> Pedido recibido</li>
		</ol>

		<?php if ( $pedido ) : ?>
			<div class="pedido-ok">
				<span class="pedido-ok__sello" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M14 25l7 7 14-15" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
				<h1 class="titulo-1 tienda__titulo">Gracias. Hemos recibido tu pedido.</h1>
				<ul class="pedido-ok__datos" role="list">
					<li><span>Número de pedido</span><strong><?php echo (int) $pedido['num']; ?></strong></li>
					<li><span>Fecha</span><strong><?php echo esc_html( $pedido['fecha'] ); ?></strong></li>
					<li><span>Email</span><strong><?php echo esc_html( $pedido['email'] ); ?></strong></li>
					<li><span>Total</span><strong><?php echo esc_html( vinea_precio( $pedido['total'] ) ); ?></strong></li>
				</ul>
				<div class="tienda__caja">
					<p class="tienda__resumen-titulo">Detalles del pedido</p>
					<ul class="lineas" role="list">
						<?php foreach ( $pedido['lineas'] as $l ) : ?>
							<li class="linea linea--sin-foto"><div class="linea__texto"><p class="linea__evento"><?php echo esc_html( $l['evento'] ); ?></p><p class="linea__nombre"><?php echo esc_html( $l['nombre'] ); ?></p><p class="linea__cant">× <?php echo (int) $l['cant']; ?></p></div><div class="linea__fin"><strong class="linea__precio"><?php echo esc_html( vinea_precio( (float) $l['sub'] ) ); ?></strong></div></li>
						<?php endforeach; ?>
					</ul>
					<dl class="tienda__sumas"><div class="tienda__total"><dt>Total</dt><dd><?php echo esc_html( vinea_precio( $pedido['total'] ) ); ?></dd></div></dl>
				</div>
				<p class="pedido-ok__nota">Te confirmaremos plazas y pago por email. Si tienes cualquier duda: <a href="tel:<?php echo esc_attr( preg_replace( '/[^+\d]/', '', vinea_ajuste( 'telefono_1' ) ) ); ?>"><?php echo esc_html( vinea_ajuste( 'telefono_1' ) ); ?></a> · <a href="mailto:<?php echo esc_attr( antispambot( vinea_ajuste( 'email_publico' ) ) ); ?>"><?php echo esc_html( antispambot( vinea_ajuste( 'email_publico' ) ) ); ?></a></p>
				<a class="boton boton--naranja" href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">Volver a la tienda <span class="flecha" aria-hidden="true">→</span></a>
			</div>
		<?php else : ?>
			<h1 class="titulo-1 tienda__titulo">Finalizar compra</h1>
			<?php if ( isset( $errores[ $error ] ) ) : ?><p class="formulario__estado formulario__estado--error tienda__error" role="alert"><?php echo esc_html( $errores[ $error ] ); ?></p><?php endif; ?>

			<form class="tienda__grid formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pago-lleno hidden data-formulario novalidate>
				<input type="hidden" name="action" value="vinea_pedido">
				<input type="hidden" name="carrito" value="" data-pago-carrito>
				<?php vinea_campo_firma( 'vinea_pedido' ); ?>
				<div class="tienda__caja">
					<p class="tienda__resumen-titulo">Detalles de facturación</p>
					<div class="campo__fila">
						<div class="campo"><label for="p-nombre">Nombre *</label><input type="text" id="p-nombre" name="nombre" autocomplete="given-name" required aria-describedby="e-p-nombre"><p class="campo__error" id="e-p-nombre" hidden>Escribe tu nombre.</p></div>
						<div class="campo"><label for="p-apellidos">Apellidos *</label><input type="text" id="p-apellidos" name="apellidos" autocomplete="family-name" required aria-describedby="e-p-apellidos"><p class="campo__error" id="e-p-apellidos" hidden>Escribe tus apellidos.</p></div>
					</div>
					<div class="campo"><label for="p-empresa">Nombre de la empresa (opcional)</label><input type="text" id="p-empresa" name="empresa" autocomplete="organization"></div>
					<div class="campo__fila">
						<div class="campo"><label for="p-telefono">Teléfono *</label><input type="tel" id="p-telefono" name="telefono" autocomplete="tel" required aria-describedby="e-p-telefono"><p class="campo__error" id="e-p-telefono" hidden>Escribe tu teléfono.</p></div>
						<div class="campo"><label for="p-email">Dirección de correo electrónico *</label><input type="email" id="p-email" name="email" autocomplete="email" required aria-describedby="e-p-email"><p class="campo__error" id="e-p-email" hidden>Escribe un e-mail válido.</p></div>
					</div>
					<div class="campo"><label for="p-notas">Notas del pedido (opcional)</label><textarea id="p-notas" name="notas" rows="4"></textarea></div>
					<div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label for="p-web">No rellenar este campo</label><input type="text" id="p-web" name="web" tabindex="-1" autocomplete="off"></div>
				</div>
				<aside class="tienda__resumen tienda__caja">
					<p class="tienda__resumen-titulo">Tu pedido</p>
					<ul class="lineas lineas--resumen" role="list" data-pago-lista></ul>
					<dl class="tienda__sumas">
						<div><dt>Subtotal</dt><dd data-carrito-suma>0,00 €</dd></div>
						<div class="tienda__total"><dt>Total</dt><dd data-carrito-suma>0,00 €</dd></div>
					</dl>
					<div class="campo campo--check">
						<input type="checkbox" id="p-privacidad" name="privacidad" value="1" required aria-describedby="e-p-privacidad">
						<label for="p-privacidad">He leído y acepto la <a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">política de privacidad</a></label>
						<p class="campo__error" id="e-p-privacidad" hidden>Debes aceptar la política de privacidad.</p>
					</div>
					<button class="boton boton--naranja tienda__seguir" type="submit">Realizar el pedido <span class="flecha" aria-hidden="true">→</span></button>
					<p class="formulario__estado" role="status" aria-live="polite" data-estado></p>
					<a class="tienda__volver" href="<?php echo esc_url( vinea_url_carrito() ); ?>">← Volver al carrito</a>
				</aside>
			</form>

			<div class="carrito-vacio" data-carrito-vacio>
				<span class="carrito-vacio__gota" aria-hidden="true"></span>
				<p class="carrito-vacio__texto">Tu carrito está vacío.</p>
				<a class="boton boton--naranja" href="<?php echo esc_url( home_url( '/eventos-2/' ) ); ?>">Volver a la tienda <span class="flecha" aria-hidden="true">→</span></a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
