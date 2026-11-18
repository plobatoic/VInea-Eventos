<?php
/**
 * Pie del tema Vinea con los textos de la web original:
 * "Contacta con vinea", teléfonos, email, menú del pie y "apúntate a la newsletter".
 */
$tema   = get_template_directory_uri();
$ajuste = function ( $clave, $defecto ) {
	return function_exists( 'vinea_ajuste' ) && vinea_ajuste( $clave ) ? vinea_ajuste( $clave ) : $defecto;
};
$tel1  = $ajuste( 'telefono_1', '+34 611 88 40 83' );
$tel2  = $ajuste( 'telefono_2', '+34 623 00 43 81' );
$email = $ajuste( 'email_publico', 'info@vineaeventos.com' );
$tel   = function ( $t ) { return 'tel:' . preg_replace( '/[^0-9+]/', '', $t ); };
?>
</main>

<footer class="pie seccion--oscura">
	<div class="contenedor">
		<div class="pie__cta">
			<h2 class="pie__titulo" data-split>Contacta con <em>vinea</em></h2>
			<div class="pie__acciones" data-reveal>
				<p class="pie__telefonos"><a href="<?php echo esc_attr( $tel( $tel1 ) ); ?>"><?php echo esc_html( $tel1 ); ?></a> / <a href="<?php echo esc_attr( $tel( $tel2 ) ); ?>"><?php echo esc_html( $tel2 ); ?></a></p>
				<a class="pie__email" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			</div>
			<div class="gota-deco pie__gota" aria-hidden="true"></div>
		</div>

		<div class="pie__columnas">
			<div>
				<img class="pie__logo" src="<?php echo esc_url( $tema . '/assets/img/logo-vinea-claro.svg' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="1353" height="435" loading="lazy">
				<?php
				if ( function_exists( 'vinea_formulario_newsletter' ) ) {
					vinea_formulario_newsletter();
				}
				?>
			</div>
			<nav aria-label="Pie" class="pie__nav">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'pie',
						'container'      => false,
						'menu_class'     => 'menu-pie',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
		</div>

		<div class="pie__base">
			<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<ul class="pie__redes">
				<?php if ( $ajuste( 'instagram', '' ) ) : ?><li><a href="<?php echo esc_url( $ajuste( 'instagram', '' ) ); ?>" target="_blank" rel="noopener">Instagram<span class="sr-only"> (se abre en otra pestaña)</span></a></li><?php endif; ?>
				<?php if ( $ajuste( 'linkedin', '' ) ) : ?><li><a href="<?php echo esc_url( $ajuste( 'linkedin', '' ) ); ?>" target="_blank" rel="noopener">LinkedIn<span class="sr-only"> (se abre en otra pestaña)</span></a></li><?php endif; ?>
			</ul>
			<button class="pie__movimiento" type="button" data-movimiento aria-pressed="false">
				<span class="pie__interruptor" aria-hidden="true"></span>
				<span>Reducir animaciones</span>
			</button>
		</div>
	</div>
	<img class="pie__marca" src="<?php echo esc_url( $tema . '/assets/img/logo-vinea-claro.svg' ); ?>" alt="" aria-hidden="true" width="1353" height="435" loading="lazy" data-marca>
</footer>

<?php wp_footer(); ?>
</body>
</html>
