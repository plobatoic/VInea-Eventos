<?php
/**
 * Tienda de entradas (como en la web original: Eventin + WooCommerce).
 * - Cada evento tiene sus tipos de entrada (nombre, descripción, precio, plazas) en importar/eventos.json.
 * - Carrito en el navegador (localStorage) con botón en la cabecera: total + número de entradas.
 * - Páginas /carrito-compra/ y /finalizar-compra/ (mismas URL que el original).
 * - El pedido se valida en el servidor con los precios del JSON (nunca los del navegador),
 *   se guarda en «Pedidos» y se envía por email a Vinea y al cliente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Datos de tienda de todos los eventos, por slug. */
function vinea_tienda_datos(): array {
	static $datos = null;
	if ( null === $datos ) {
		$datos = array();
		$json  = get_template_directory() . '/importar/eventos.json';
		$lista = file_exists( $json ) ? json_decode( (string) file_get_contents( $json ), true ) : array(); // phpcs:ignore
		foreach ( (array) $lista as $e ) {
			if ( ! empty( $e['slug'] ) ) {
				$datos[ $e['slug'] ] = $e;
			}
		}
	}
	return $datos;
}

/** Datos de tienda de un evento (o null). */
function vinea_tienda_evento( $post ): ?array {
	$post = get_post( $post );
	if ( ! $post || 'etn' !== $post->post_type ) {
		return null;
	}
	$d = vinea_tienda_datos()[ $post->post_name ] ?? null;
	return $d && ! empty( $d['entradas'] ) ? $d : null;
}

/** ¿Se puede comprar esta entrada? */
function vinea_entrada_a_la_venta( array $t ): bool {
	return null !== ( $t['precio'] ?? null ) && (int) ( $t['plazas'] ?? 0 ) > 0 && empty( $t['estado'] );
}

/** Precio con formato español: 1.550,00 € */
function vinea_precio( float $p ): string {
	return number_format( $p, 2, ',', '.' ) . ' €';
}

/** Precio «desde» para el listado. */
function vinea_precio_desde( $post ): ?float {
	$d = vinea_tienda_evento( $post );
	if ( ! $d ) {
		return null;
	}
	$min = null;
	foreach ( $d['entradas'] as $t ) {
		if ( null !== ( $t['precio'] ?? null ) && ( null === $min || $t['precio'] < $min ) ) {
			$min = (float) $t['precio'];
		}
	}
	return $min;
}

function vinea_url_carrito(): string {
	return home_url( '/carrito-compra/' );
}
function vinea_url_pago(): string {
	return home_url( '/finalizar-compra/' );
}

/* ------------------------------------------------------------------
 * Páginas del carrito y del pago (se crean una vez) y tipo «Pedidos»
 * ---------------------------------------------------------------- */
add_action(
	'init',
	static function (): void {
		register_post_type(
			'vinea_pedido',
			array(
				'labels'              => array(
					'name'          => 'Pedidos',
					'singular_name' => 'Pedido',
					'menu_name'     => 'Pedidos',
					'all_items'     => 'Todos los pedidos',
					'edit_item'     => 'Ver pedido',
					'not_found'     => 'No hay pedidos.',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_rest'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor' ),
				'menu_icon'           => 'dashicons-cart',
				'menu_position'       => 24,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			)
		);

		if ( 1 !== (int) get_option( 'vinea_tienda_paginas' ) ) {
			foreach ( array( 'carrito-compra' => 'Carrito', 'finalizar-compra' => 'Finalizar compra' ) as $slug => $titulo ) {
				if ( ! get_page_by_path( $slug ) ) {
					wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $titulo, 'post_content' => '' ) );
				}
			}
			update_option( 'vinea_tienda_paginas', 1, false );
		}
	}
);

// Sin caché para el carrito y el pago.
add_action(
	'template_redirect',
	static function (): void {
		if ( is_page( array( 'carrito-compra', 'finalizar-compra' ) ) ) {
			nocache_headers();
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		}
	}
);

// Columnas del listado de pedidos.
add_filter(
	'manage_vinea_pedido_posts_columns',
	static function ( array $c ): array {
		return array( 'cb' => $c['cb'] ?? '', 'title' => 'Pedido', 'cliente' => 'Cliente', 'email' => 'Email', 'total' => 'Total', 'date' => 'Fecha' );
	}
);
add_action(
	'manage_vinea_pedido_posts_custom_column',
	static function ( string $col, int $id ): void {
		if ( 'total' === $col ) {
			echo esc_html( vinea_precio( (float) get_post_meta( $id, '_vinea_total', true ) ) );
		} elseif ( in_array( $col, array( 'cliente', 'email' ), true ) ) {
			echo esc_html( (string) get_post_meta( $id, '_vinea_' . $col, true ) );
		}
	},
	10,
	2
);

/* ------------------------------------------------------------------
 * Datos para el JS del carrito (precios y nombres de todas las entradas)
 * ---------------------------------------------------------------- */
function vinea_tienda_catalogo(): array {
	$cat = array();
	$posts = get_posts( array( 'post_type' => 'etn', 'posts_per_page' => 50, 'post_status' => 'publish' ) );
	foreach ( $posts as $p ) {
		$d = vinea_tienda_evento( $p );
		if ( ! $d ) {
			continue;
		}
		$img = vinea_url_imagen( (int) get_post_thumbnail_id( $p ), 'thumbnail' );
		foreach ( $d['entradas'] as $i => $t ) {
			if ( ! vinea_entrada_a_la_venta( $t ) ) {
				continue;
			}
			$cat[ $p->post_name . ':' . $i ] = array(
				'evento' => get_the_title( $p ),
				'nombre' => $t['nombre'],
				'precio' => (float) $t['precio'],
				'max'    => (int) $t['plazas'],
				'url'    => get_permalink( $p ),
				'img'    => $img,
				'fecha'  => $d['cuando'] ?? '',
			);
		}
	}
	return $cat;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$ruta = VINEA_DIR . '/assets/js/tienda.js';
		wp_enqueue_script( 'vinea-tienda', VINEA_URL . '/assets/js/tienda.js', array(), file_exists( $ruta ) ? (string) filemtime( $ruta ) : VINEA_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script(
			'vinea-tienda',
			'window.VINEA_TIENDA=' . wp_json_encode(
				array(
					'carrito'  => vinea_url_carrito(),
					'pago'     => vinea_url_pago(),
					'entradas' => home_url( '/eventos-2/' ),
					'catalogo' => vinea_tienda_catalogo(),
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			) . ';',
			'before'
		);
	},
	25
);

/* ------------------------------------------------------------------
 * Marcado
 * ---------------------------------------------------------------- */

/** Botón del carrito de la cabecera (como el original: total y número). */
function vinea_boton_carrito(): void {
	?>
	<a class="carrito-btn" href="<?php echo esc_url( vinea_url_carrito() ); ?>" data-carrito-boton aria-label="Carrito: 0 entradas, 0,00 €">
		<svg class="carrito-btn__icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
		<span class="carrito-btn__total" data-carrito-total>0,00 €</span>
		<span class="carrito-btn__n" data-carrito-n>0</span>
	</a>
	<?php
}

/** Panel lateral «Añadido al carrito» (se rellena desde JS). */
add_action(
	'wp_footer',
	static function (): void {
		?>
		<div class="panel-carrito" data-panel-carrito hidden>
			<div class="panel-carrito__velo" data-panel-cerrar></div>
			<aside class="panel-carrito__caja" role="dialog" aria-modal="true" aria-labelledby="panel-carrito-t" tabindex="-1">
				<header class="panel-carrito__cab">
					<p class="panel-carrito__titulo" id="panel-carrito-t">Carrito</p>
					<button class="panel-carrito__cerrar" type="button" data-panel-cerrar aria-label="Cerrar el carrito">×</button>
				</header>
				<div class="panel-carrito__lista" data-panel-lista></div>
				<footer class="panel-carrito__pie">
					<p class="panel-carrito__sub"><span>Subtotal</span> <strong data-panel-total>0,00 €</strong></p>
					<a class="boton boton--claro panel-carrito__ver" href="<?php echo esc_url( vinea_url_carrito() ); ?>">Ver carrito</a>
					<a class="boton boton--naranja panel-carrito__pagar" href="<?php echo esc_url( vinea_url_pago() ); ?>">Finalizar compra <span class="flecha" aria-hidden="true">→</span></a>
				</footer>
			</aside>
		</div>
		<?php
	},
	5
);

/** Caja lateral de un evento: fecha, hora, lugar, entradas y «Add to calendar». */
function vinea_caja_tickets( WP_Post $post ): void {
	$d = vinea_tienda_evento( $post );
	if ( ! $d ) {
		return;
	}
	$slug = $post->post_name;
	$titulo = get_the_title( $post );
	?>
	<aside class="ev-lado" aria-label="Entradas">
		<dl class="ev-datos tarjeta-ev">
			<?php if ( ! empty( $d['cuando'] ) ) : ?><div><dt>Fecha</dt><dd><?php echo esc_html( $d['cuando'] ); ?></dd></div><?php endif; ?>
			<?php if ( ! empty( $d['hora'] ) ) : ?><div><dt>Hora</dt><dd><?php echo esc_html( $d['hora'] ); ?></dd></div><?php endif; ?>
			<?php if ( ! empty( $d['lugar_completo'] ) ) : ?><div><dt>Lugar</dt><dd><?php echo esc_html( $d['lugar_completo'] ); ?></dd></div><?php endif; ?>
		</dl>

		<form class="tickets tarjeta-ev" data-tickets data-evento="<?php echo esc_attr( $slug ); ?>">
			<p class="tickets__titulo">Tickets</p>
			<ul class="tickets__lista" role="list">
				<?php
				foreach ( $d['entradas'] as $i => $t ) :
					$venta = vinea_entrada_a_la_venta( $t );
					$id    = $slug . ':' . $i;
					?>
					<li class="ticket<?php echo $venta ? '' : ' ticket--agotado'; ?>" data-ticket="<?php echo esc_attr( $id ); ?>" data-precio="<?php echo esc_attr( (string) ( $t['precio'] ?? 0 ) ); ?>">
						<div class="ticket__cab">
							<p class="ticket__nombre"><?php echo esc_html( $t['nombre'] ); ?></p>
							<?php if ( $venta ) : ?><p class="ticket__plazas"><?php echo (int) $t['plazas']; ?> <?php echo 1 === (int) $t['plazas'] ? 'plaza disponible' : 'plazas disponibles'; ?></p><?php endif; ?>
						</div>
						<?php if ( '' !== ( $t['descripcion'] ?? '' ) ) : ?><p class="ticket__desc"><?php echo esc_html( $t['descripcion'] ); ?></p><?php endif; ?>
						<?php if ( $venta ) : ?>
							<div class="ticket__fila">
								<p class="ticket__precio"><span>Precio</span> <strong><?php echo esc_html( vinea_precio( (float) $t['precio'] ) ); ?></strong></p>
								<div class="cantidad" data-cantidad>
									<button type="button" data-menos aria-label="Quitar una entrada de <?php echo esc_attr( $t['nombre'] ); ?>">−</button>
									<input type="number" inputmode="numeric" min="0" max="<?php echo (int) $t['plazas']; ?>" value="0" aria-label="Cantidad de <?php echo esc_attr( $t['nombre'] ); ?>" data-num>
									<button type="button" data-mas aria-label="Añadir una entrada de <?php echo esc_attr( $t['nombre'] ); ?>">+</button>
								</div>
							</div>
							<p class="ticket__sub">Subtotal <strong data-sub>0,00 €</strong></p>
						<?php else : ?>
							<?php if ( null !== ( $t['precio'] ?? null ) ) : ?><p class="ticket__precio"><span>Precio</span> <strong><?php echo esc_html( vinea_precio( (float) $t['precio'] ) ); ?></strong></p><?php endif; ?>
							<p class="ticket__estado"><?php echo esc_html( $t['estado'] ?? 'Agotado' ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php $alguna = (bool) array_filter( $d['entradas'], 'vinea_entrada_a_la_venta' ); ?>
			<?php if ( $alguna ) : ?>
				<div class="tickets__total"><span>Cantidad <strong data-cant-total>0</strong></span><span>Total <strong data-total>0,00 €</strong></span></div>
				<button class="boton boton--naranja tickets__comprar" type="submit" disabled data-comprar>Comprar entradas <span class="flecha" aria-hidden="true">→</span></button>
				<p class="tickets__aviso" role="status" aria-live="polite" data-aviso></p>
			<?php else : ?>
				<a class="boton boton--claro tickets__comprar" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">cuéntanos tu idea <span class="flecha" aria-hidden="true">→</span></a>
			<?php endif; ?>
		</form>

		<?php
		if ( ! empty( $d['inicio'] ) ) :
			$tz    = new DateTimeZone( 'Europe/Madrid' );
			$ini   = new DateTime( $d['inicio'], $tz );
			$fin   = new DateTime( $d['fin'] ?? $d['inicio'], $tz );
			$utc   = new DateTimeZone( 'UTC' );
			$iu    = ( clone $ini )->setTimezone( $utc )->format( 'Ymd\THis\Z' );
			$fu    = ( clone $fin )->setTimezone( $utc )->format( 'Ymd\THis\Z' );
			$lugar = $d['lugar_completo'] ?? '';
			$det   = wp_strip_all_tags( $post->post_excerpt ) . ' ' . get_permalink( $post );
			$google  = add_query_arg( array( 'action' => 'TEMPLATE', 'text' => rawurlencode( $titulo ), 'dates' => $iu . '/' . $fu, 'details' => rawurlencode( $det ), 'location' => rawurlencode( $lugar ) ), 'https://calendar.google.com/calendar/render' );
			$yahoo   = add_query_arg( array( 'v' => '60', 'title' => rawurlencode( $titulo ), 'st' => $iu, 'et' => $fu, 'desc' => rawurlencode( $det ), 'in_loc' => rawurlencode( $lugar ) ), 'https://calendar.yahoo.com/' );
			$outlook = add_query_arg( array( 'path' => '/calendar/action/compose', 'rru' => 'addevent', 'subject' => rawurlencode( $titulo ), 'startdt' => $ini->format( 'c' ), 'enddt' => $fin->format( 'c' ), 'body' => rawurlencode( $det ), 'location' => rawurlencode( $lugar ) ), 'https://outlook.live.com/calendar/0/deeplink/compose' );
			$ics     = add_query_arg( array( 'vinea_ics' => $slug ), home_url( '/' ) );
			?>
			<div class="calendario tarjeta-ev">
				<p class="calendario__titulo">Añadir al calendario</p>
				<ul class="calendario__lista" role="list">
					<li><a href="<?php echo esc_url( $google ); ?>" target="_blank" rel="noopener"><span class="calendario__ico calendario__ico--g" aria-hidden="true">G</span>Google<span class="sr-only"> (se abre en otra pestaña)</span></a></li>
					<li><a href="<?php echo esc_url( $yahoo ); ?>" target="_blank" rel="noopener"><span class="calendario__ico calendario__ico--y" aria-hidden="true">Y!</span>Yahoo<span class="sr-only"> (se abre en otra pestaña)</span></a></li>
					<li><a href="<?php echo esc_url( $ics ); ?>" download><span class="calendario__ico calendario__ico--a" aria-hidden="true"></span>Apple</a></li>
					<li><a href="<?php echo esc_url( $outlook ); ?>" target="_blank" rel="noopener"><span class="calendario__ico calendario__ico--o" aria-hidden="true">O</span>Outlook<span class="sr-only"> (se abre en otra pestaña)</span></a></li>
				</ul>
			</div>
		<?php endif; ?>
	</aside>
	<?php
}

/* Archivo .ics para Apple Calendar */
add_action(
	'template_redirect',
	static function (): void {
		$slug = isset( $_GET['vinea_ics'] ) ? sanitize_title( wp_unslash( $_GET['vinea_ics'] ) ) : ''; // phpcs:ignore
		if ( '' === $slug ) {
			return;
		}
		$post = get_page_by_path( $slug, OBJECT, 'etn' );
		$d    = $post ? vinea_tienda_evento( $post ) : null;
		if ( ! $d || empty( $d['inicio'] ) ) {
			return;
		}
		$tz  = new DateTimeZone( 'Europe/Madrid' );
		$utc = new DateTimeZone( 'UTC' );
		$f   = static fn( string $s ): string => ( new DateTime( $s, $tz ) )->setTimezone( $utc )->format( 'Ymd\THis\Z' );
		$esc = static fn( string $s ): string => str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\;', '\,', '\n' ), $s );
		$ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Vinea Eventos//ES\r\nBEGIN:VEVENT\r\n"
			. 'UID:' . $slug . '@' . wp_parse_url( home_url(), PHP_URL_HOST ) . "\r\n"
			. 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ) . "\r\n"
			. 'DTSTART:' . $f( $d['inicio'] ) . "\r\n"
			. 'DTEND:' . $f( $d['fin'] ?? $d['inicio'] ) . "\r\n"
			. 'SUMMARY:' . $esc( get_the_title( $post ) ) . "\r\n"
			. 'LOCATION:' . $esc( $d['lugar_completo'] ?? '' ) . "\r\n"
			. 'URL:' . get_permalink( $post ) . "\r\n"
			. "END:VEVENT\r\nEND:VCALENDAR\r\n";
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $slug . '.ics"' );
		echo $ics; // phpcs:ignore
		exit;
	}
);

/* ------------------------------------------------------------------
 * Pedido
 * ---------------------------------------------------------------- */
add_action( 'admin_post_nopriv_vinea_pedido', 'vinea_procesar_pedido' );
add_action( 'admin_post_vinea_pedido', 'vinea_procesar_pedido' );
function vinea_procesar_pedido(): void {
	$volver = static function ( array $args ): void {
		wp_safe_redirect( add_query_arg( $args, vinea_url_pago() ), 303 );
		exit;
	};
	if ( ! vinea_firma_valida( 'vinea_pedido' ) ) {
		$volver( array( 'error' => 'seguridad' ) );
	}
	if ( ! empty( $_POST['web'] ) ) {
		$volver( array( 'error' => 'envio' ) );
	}
	$campo = static fn( string $k ): string => vinea_limpiar_cabecera( sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
	$nombre    = $campo( 'nombre' );
	$apellidos = $campo( 'apellidos' );
	$telefono  = $campo( 'telefono' );
	$empresa   = $campo( 'empresa' );
	$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$notas     = sanitize_textarea_field( wp_unslash( $_POST['notas'] ?? '' ) );
	$carrito   = json_decode( wp_unslash( (string) ( $_POST['carrito'] ?? '' ) ), true ); // phpcs:ignore

	if ( '' === $nombre || '' === $apellidos || '' === $telefono || ! is_email( $email ) || empty( $_POST['privacidad'] ) ) {
		$volver( array( 'error' => 'campos' ) );
	}

	// Líneas con precios del servidor.
	$cat    = vinea_tienda_catalogo();
	$lineas = array();
	$total  = 0.0;
	foreach ( (array) $carrito as $id => $cant ) {
		$id   = (string) $id;
		$cant = (int) $cant;
		if ( ! isset( $cat[ $id ] ) || $cant < 1 ) {
			continue;
		}
		$cant     = min( $cant, $cat[ $id ]['max'] );
		$sub      = $cant * $cat[ $id ]['precio'];
		$total   += $sub;
		$lineas[] = array( 'id' => $id, 'evento' => $cat[ $id ]['evento'], 'nombre' => $cat[ $id ]['nombre'], 'cant' => $cant, 'precio' => $cat[ $id ]['precio'], 'sub' => $sub );
	}
	if ( ! $lineas ) {
		$volver( array( 'error' => 'vacio' ) );
	}

	$texto = '';
	foreach ( $lineas as $l ) {
		$texto .= sprintf( "%s — %s × %d = %s\n", $l['evento'], $l['nombre'], $l['cant'], vinea_precio( $l['sub'] ) );
	}
	$texto .= "\nTotal: " . vinea_precio( $total ) . "\n";

	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'vinea_pedido',
				'post_status'  => 'publish',
				'post_title'   => 'Pedido de ' . $nombre . ' ' . $apellidos,
				'post_content' => $texto . ( '' !== $notas ? "\nNotas:\n" . $notas : '' ),
				'meta_input'   => array(
					'_vinea_cliente'  => $nombre . ' ' . $apellidos,
					'_vinea_email'    => $email,
					'_vinea_telefono' => $telefono,
					'_vinea_empresa'  => $empresa,
					'_vinea_total'    => $total,
					'_vinea_lineas'   => wp_json_encode( $lineas, JSON_UNESCAPED_UNICODE ),
				),
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		$volver( array( 'error' => 'envio' ) );
	}
	$num = (string) $id;
	wp_update_post( array( 'ID' => $id, 'post_title' => 'Pedido nº ' . $num . ' · ' . $nombre . ' ' . $apellidos ) );
	$clave = wp_generate_password( 16, false );
	update_post_meta( $id, '_vinea_clave', $clave );

	$datos = "Nombre: $nombre $apellidos\nEmail: $email\nTeléfono: $telefono\n" . ( '' !== $empresa ? "Empresa: $empresa\n" : '' );
	$cab   = array( 'Content-Type: text/plain; charset=UTF-8' );
	wp_mail( vinea_ajuste( 'email_formularios' ), '[Web] Nuevo pedido nº ' . $num . ' — ' . vinea_precio( $total ), "Nuevo pedido de entradas desde la web.\n\n" . $datos . "\n" . $texto . ( '' !== $notas ? "\nNotas:\n$notas\n" : '' ), array_merge( $cab, array( 'Reply-To: ' . $nombre . ' <' . $email . '>' ) ) );
	wp_mail( $email, 'Hemos recibido tu pedido nº ' . $num . ' — Vinea Eventos', "Hola $nombre,\n\nGracias por tu pedido. Estos son los detalles:\n\n" . $texto . "\nTe confirmaremos plazas y pago por email.\n\nVinea Eventos\n" . vinea_ajuste( 'telefono_1' ) . ' / ' . vinea_ajuste( 'telefono_2' ) . "\n" . vinea_ajuste( 'email_publico' ) . "\n", array_merge( $cab, array( 'Reply-To: Vinea Eventos <' . vinea_ajuste( 'email_publico' ) . '>' ) ) );

	$volver( array( 'pedido' => $num, 'clave' => $clave ) );
}

/** Pedido recibido (lo ve solo quien tiene la clave). */
function vinea_pedido_recibido(): ?array {
	$num   = isset( $_GET['pedido'] ) ? absint( $_GET['pedido'] ) : 0; // phpcs:ignore
	$clave = isset( $_GET['clave'] ) ? sanitize_text_field( wp_unslash( $_GET['clave'] ) ) : ''; // phpcs:ignore
	if ( ! $num || '' === $clave ) {
		return null;
	}
	$p = get_post( $num );
	if ( ! $p || 'vinea_pedido' !== $p->post_type || ! hash_equals( (string) get_post_meta( $num, '_vinea_clave', true ), $clave ) ) {
		return null;
	}
	return array(
		'num'    => $num,
		'fecha'  => get_the_date( 'j \d\e F \d\e Y', $p ),
		'email'  => (string) get_post_meta( $num, '_vinea_email', true ),
		'total'  => (float) get_post_meta( $num, '_vinea_total', true ),
		'lineas' => (array) json_decode( (string) get_post_meta( $num, '_vinea_lineas', true ), true ),
	);
}
