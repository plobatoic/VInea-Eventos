<?php
/**
 * Eventos (las «Entradas» de la web original, que allí eran del plugin Eventin con URL /etn/…).
 * - Tipo de contenido «Eventos» editable en el panel, con la misma URL que la web original.
 * - La primera vez se crean solos a partir de importar/eventos.json (textos tal cual del original).
 * - La página «Entradas» los muestra como tarjetas (imagen, lugar, título, resumen, fecha y «Attend»).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		register_post_type(
			'etn',
			array(
				'labels'       => array(
					'name'          => 'Eventos',
					'singular_name' => 'Evento',
					'add_new_item'  => 'Añadir evento',
					'edit_item'     => 'Editar evento',
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-calendar-alt',
				'menu_position' => 6,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
				'rewrite'      => array( 'slug' => 'etn', 'with_front' => false ),
				'has_archive'  => false,
			)
		);
		foreach ( array( 'vinea_lugar', 'vinea_fecha_txt' ) as $clave ) {
			register_post_meta( 'etn', $clave, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
		}
		if ( 2 !== (int) get_option( 'vinea_eventos_creados' ) ) {
			vinea_crear_eventos();
		}
	}
);

/** Crea los eventos de la web original (una sola vez). */
function vinea_crear_eventos(): void {
	if ( get_transient( 'vinea_eventos_creando' ) ) {
		return;
	}
	set_transient( 'vinea_eventos_creando', 1, 60 );
	$json = get_template_directory() . '/importar/eventos.json';
	$lista = file_exists( $json ) ? json_decode( (string) file_get_contents( $json ), true ) : array(); // phpcs:ignore
	foreach ( (array) $lista as $e ) {
		if ( get_page_by_path( $e['slug'], OBJECT, 'etn' ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'etn',
				'post_status'  => 'publish',
				'post_name'    => $e['slug'],
				'post_title'   => $e['titulo'],
				'post_content' => $e['contenido'],
				'post_excerpt' => $e['resumen'],
				'post_date'    => str_replace( 'T', ' ', $e['fecha'] ),
				'menu_order'   => (int) $e['orden'],
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, 'vinea_lugar', $e['lugar'] );
		update_post_meta( $id, 'vinea_fecha_txt', $e['fecha_txt'] );
		update_post_meta( $id, 'vinea_en_listado', $e['en_listado'] ? '1' : '0' );
		if ( ! empty( $e['img'] ) ) {
			$adj = attachment_url_to_postid( $e['img'] );
			if ( $adj ) {
				set_post_thumbnail( $id, $adj );
			}
		}
	}
	update_option( 'vinea_eventos_creados', 2, false );
	flush_rewrite_rules( false );
	delete_transient( 'vinea_eventos_creando' );
}

/** Tarjetas de eventos para la página «Entradas». */
function vinea_tarjetas_eventos(): string {
	$q = new WP_Query(
		array(
			'post_type'      => 'etn',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => 'vinea_en_listado', 'value' => '1' ),
			),
			'no_found_rows'  => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return '';
	}
	ob_start();
	echo '<ul class="eventos" role="list">';
	$i = 0;
	foreach ( $q->posts as $p ) {
		$img   = vinea_img( (int) get_post_thumbnail_id( $p ), 'medium_large', array( 'sizes' => '(min-width: 1100px) 380px, (min-width: 700px) 45vw, 92vw', 'alt' => '' ) );
		$lugar = (string) get_post_meta( $p->ID, 'vinea_lugar', true );
		$fecha = (string) get_post_meta( $p->ID, 'vinea_fecha_txt', true );
		$url   = get_permalink( $p );
		?>
		<li class="evento" data-reveal style="--d:<?php echo (int) ( ( $i++ % 3 ) * 90 ); ?>">
			<a class="evento__foto" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true"><?php echo $img ? $img : vinea_ilustracion( get_the_title( $p ) ); // phpcs:ignore ?></a>
			<div class="evento__texto">
				<?php if ( '' !== $lugar ) : ?><p class="evento__lugar"><?php echo esc_html( $lugar ); ?></p><?php endif; ?>
				<h3 class="evento__titulo"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></h3>
				<p class="evento__resumen"><?php echo esc_html( $p->post_excerpt ); ?></p>
				<p class="evento__pie">
					<?php if ( '' !== $fecha ) : ?><span class="evento__fecha"><?php echo esc_html( $fecha ); ?></span><?php endif; ?>
					<a class="boton boton--naranja evento__boton" href="<?php echo esc_url( $url ); ?>">Attend <span class="flecha" aria-hidden="true">→</span></a>
				</p>
			</div>
		</li>
		<?php
	}
	echo '</ul>';
	return (string) ob_get_clean();
}
