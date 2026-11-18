<?php
/**
 * Contacto: directo. Arriba, el formulario y todas las vías de contacto (email, teléfonos,
 * Instagram y LinkedIn); debajo, las redes en grande y el texto de la página original.
 * Los textos salen de la página «Contacto» (importada de la web original).
 */
get_header();

while ( have_posts() ) :
	the_post();
	$html  = get_post()->post_content;
	$largo = '';
	if ( preg_match_all( '~<h2[^>]*>(.*?)</h2>~is', $html, $m ) ) {
		foreach ( $m[1] as $h ) {
			$t = trim( wp_strip_all_tags( $h ) );
			if ( mb_strlen( $t ) > mb_strlen( $largo ) ) {
				$largo = $t;
			}
		}
	}
	// El texto largo del original, en párrafos (mismas palabras).
	$parrafos = preg_split( '~\s+(?=Desde Vinea Eventos te acompañamos|A través de este formulario|También puedes utilizar|Si prefieres seguir|Contacta con Vinea Eventos y empecemos)~u', $largo );

	$email = vinea_ajuste( 'email_publico' );
	$tel1  = vinea_ajuste( 'telefono_1' );
	$tel2  = vinea_ajuste( 'telefono_2' );
	$ig    = vinea_ajuste( 'instagram' );
	$li    = vinea_ajuste( 'linkedin' );
	$tel   = static fn( string $t ): string => 'tel:' . preg_replace( '/[^+\d]/', '', $t );
	$ico   = array(
		'mail'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'tel'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5h2.6l1.4 4.1-2 1.4a12 12 0 0 0 6.4 6.4l1.4-2 4.1 1.4v2.6a2 2 0 0 1-2.2 2A17 17 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
		'ig'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg>',
		'li'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9.5h3V19H5zM6.5 4.8a1.8 1.8 0 1 1 0 3.6 1.8 1.8 0 0 1 0-3.6zM10.5 9.5h2.9v1.3c.5-.9 1.6-1.6 3.2-1.6 3 0 3.4 2 3.4 4.4V19h-3v-4.8c0-1.2 0-2.6-1.6-2.6s-1.9 1.2-1.9 2.5V19h-3z" fill="currentColor"/></svg>',
	);
	?>
<section class="ct">
	<span class="ct__gota ct__gota--a" aria-hidden="true"></span>
	<span class="ct__gota ct__gota--b" aria-hidden="true"></span>
	<div class="contenedor ct__grid">
		<div class="ct__texto">
			<nav class="migas" aria-label="Ruta de navegación"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li><li><span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span></li></ol></nav>
			<p class="etiqueta" data-reveal>Here to assist</p>
			<h1 class="ct__titulo" data-split>Contáctanos</h1>
			<p class="ct__lema" data-reveal style="--d:200">Contacta con Vinea Eventos y cuéntanos tu proyecto <em>estaremos encantadas de escucharte</em></p>

			<ul class="ct__vias" role="list">
				<li data-reveal style="--d:250"><a class="via" href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><span class="via__ico"><?php echo $ico['mail']; // phpcs:ignore ?></span><span class="via__txt"><span class="via__k">Email</span><span class="via__v"><?php echo esc_html( antispambot( $email ) ); ?></span></span><span class="via__flecha" aria-hidden="true">→</span></a></li>
				<li data-reveal style="--d:320"><div class="via via--tel"><span class="via__ico"><?php echo $ico['tel']; // phpcs:ignore ?></span><span class="via__txt"><span class="via__k">Teléfono</span><span class="via__v"><a href="<?php echo esc_attr( $tel( $tel2 ) ); ?>"><?php echo esc_html( $tel2 ); ?></a> <span aria-hidden="true">/</span> <a href="<?php echo esc_attr( $tel( $tel1 ) ); ?>"><?php echo esc_html( $tel1 ); ?></a></span></span></div></li>
				<?php if ( '' !== $ig ) : ?><li data-reveal style="--d:390"><a class="via via--ig" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener"><span class="via__ico"><?php echo $ico['ig']; // phpcs:ignore ?></span><span class="via__txt"><span class="via__k">Instagram</span><span class="via__v">@vinea_eventos_turismo</span></span><span class="via__flecha" aria-hidden="true">↗</span><span class="sr-only"> (se abre en otra pestaña)</span></a></li><?php endif; ?>
				<?php if ( '' !== $li ) : ?><li data-reveal style="--d:460"><a class="via via--li" href="<?php echo esc_url( $li ); ?>" target="_blank" rel="noopener"><span class="via__ico"><?php echo $ico['li']; // phpcs:ignore ?></span><span class="via__txt"><span class="via__k">LinkedIn</span><span class="via__v">Vinea Eventos y Turismo</span></span><span class="via__flecha" aria-hidden="true">↗</span><span class="sr-only"> (se abre en otra pestaña)</span></a></li><?php endif; ?>
			</ul>
		</div>
		<div class="ct__form" data-reveal="foto">
			<?php vinea_formulario_contacto(); ?>
		</div>
	</div>
</section>

<section class="ct-redes seccion--oscura" aria-label="Redes sociales">
	<div class="contenedor ct-redes__in">
		<p class="ct-redes__linea"><a href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener"><span class="ct-redes__ico"><?php echo $ico['ig']; // phpcs:ignore ?></span>No te olvides de seguirnos en <em>Instagram</em><span class="sr-only"> (se abre en otra pestaña)</span></a></p>
		<p class="ct-redes__linea"><a href="<?php echo esc_url( $li ); ?>" target="_blank" rel="noopener"><span class="ct-redes__ico"><?php echo $ico['li']; // phpcs:ignore ?></span>y <em>LinkedIn</em> para saber más sobre nuestros proyectos.<span class="sr-only"> (se abre en otra pestaña)</span></a></p>
	</div>
</section>

	<?php if ( '' !== $largo ) : ?>
<section class="seccion ct-sobre">
	<div class="contenedor ct-sobre__in">
		<?php foreach ( $parrafos as $k => $p ) : ?>
			<p<?php echo 0 === $k ? ' class="ct-sobre__primero"' : ''; ?> data-reveal style="--d:<?php echo (int) ( $k * 60 ); ?>"><?php echo esc_html( $p ); ?></p>
		<?php endforeach; ?>
	</div>
</section>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
