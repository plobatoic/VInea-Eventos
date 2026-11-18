<?php
/**
 * Páginas. Variantes: servicio (hija de «servicios»), contacto y servicios.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$pagina   = get_post();
	$padre    = $pagina->post_parent ? get_post( $pagina->post_parent ) : null;
	$es_serv  = vinea_es_servicio( $pagina );
	$cab      = vinea_separar_cabecera( $pagina->post_content );

	$migas = array();
	if ( $padre ) {
		$migas[] = array( get_the_title( $padre ), get_permalink( $padre ) );
	}
	$etiqueta = '' !== $cab['etiqueta'] ? $cab['etiqueta'] : ( $padre ? get_the_title( $padre ) : '' );

	vinea_parte_cabecera(
		array(
			'titulo'     => $cab['titulo'],
			'etiqueta'   => $etiqueta,
			'entradilla' => $cab['entradilla'],
			'imagen'     => $cab['imagen'],
			'migas'      => $migas,
		)
	);
	?>
<div class="contenido seccion">
	<div class="contenedor">
		<?php echo apply_filters( 'the_content', $cab['resto'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
	<?php

	// Contacto: formulario + datos.
	if ( 'contacto' === $pagina->post_name ) :
		$email = vinea_ajuste( 'email_publico' );
		?>
<section class="seccion contacto-form">
	<div class="contenedor contacto__grid">
		<?php vinea_formulario_contacto(); ?>
		<aside class="datos">
			<div class="datos__tarjeta">
				<p class="datos__etiqueta">Email</p>
				<p><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></p>
			</div>
			<div class="datos__tarjeta">
				<p class="datos__etiqueta">Teléfono</p>
				<?php foreach ( array( vinea_ajuste( 'telefono_1' ), vinea_ajuste( 'telefono_2' ) ) as $tel ) : ?>
					<?php if ( '' !== $tel ) : ?>
						<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^+\d]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a></p>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<div class="datos__tarjeta">
				<p class="datos__etiqueta">Redes</p>
				<?php if ( '' !== vinea_ajuste( 'instagram' ) ) : ?>
					<p><a href="<?php echo esc_url( vinea_ajuste( 'instagram' ) ); ?>" target="_blank" rel="noopener">Instagram</a></p>
				<?php endif; ?>
				<?php if ( '' !== vinea_ajuste( 'linkedin' ) ) : ?>
					<p><a href="<?php echo esc_url( vinea_ajuste( 'linkedin' ) ); ?>" target="_blank" rel="noopener">LinkedIn</a></p>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</section>
		<?php
	endif;

	// Servicios: rejilla de páginas hijas.
	if ( 'servicios' === $pagina->post_name ) :
		$hijas = get_pages(
			array(
				'parent'      => $pagina->ID,
				'sort_column' => 'menu_order,post_title',
				'sort_order'  => 'ASC',
			)
		);
		// "Ferias y congresos" duplica a "Congresos" y no estaba en el menú original: no se repite en la rejilla.
		$hijas = array_values( array_filter( $hijas, function ( $h ) { return 'ferias-y-congresos' !== $h->post_name; } ) );
		if ( $hijas ) :
			?>
<ul class="contenedor lista__grid">
			<?php
			foreach ( $hijas as $i => $hija ) :
				$img = vinea_img( vinea_primera_imagen( $hija->post_content ), 'medium_large', array( 'sizes' => '(min-width: 1024px) 33vw, (min-width: 600px) 50vw, 100vw' ) );
				?>
	<li data-reveal><a class="tarjeta-s" href="<?php echo esc_url( get_permalink( $hija ) ); ?>" data-tilt data-cursor="Ver"><div class="tarjeta-s__foto"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><div class="tarjeta-s__texto"><p class="tarjeta-s__meta"><span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span> Servicio</p><h3 class="tarjeta-s__titulo"><?php echo esc_html( get_the_title( $hija ) ); ?></h3><p class="tarjeta-s__resumen"><?php echo esc_html( vinea_resumen( $hija, 22 ) ); ?></p><span class="tarjeta-s__ir" aria-hidden="true">→</span></div></a></li>
			<?php endforeach; ?>
</ul>
			<?php
		endif;
	endif;

	// Servicio: banda y navegación entre servicios.
	if ( $es_serv ) :
		vinea_banda_cta( 'cuéntanos tu idea' );
		$hermanas = get_pages(
			array(
				'parent'      => $pagina->post_parent,
				'exclude'     => (string) $pagina->ID,
				'sort_column' => 'menu_order,post_title',
				'sort_order'  => 'ASC',
			)
		);
		if ( $hermanas ) :
			?>
<nav class="seccion otros" aria-label="Otros servicios">
	<div class="contenedor">
		<p class="etiqueta">Servicios</p>
		<ul class="otros__lista">
			<?php foreach ( $hermanas as $h ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $h ) ); ?>" data-preview="<?php echo esc_url( vinea_url_imagen( vinea_primera_imagen( $h->post_content ) ) ); ?>"><span><?php echo esc_html( get_the_title( $h ) ); ?></span><span class="otros__flecha" aria-hidden="true">→</span></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
			<?php
		endif;
	endif;
endwhile;

get_footer();
