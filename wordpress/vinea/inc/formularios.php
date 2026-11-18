<?php
/**
 * Formularios de contacto y newsletter: CPT privados, gestores admin-post y marcado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * CPT privados
 * ---------------------------------------------------------------- */
add_action( 'init', 'vinea_registrar_cpt_formularios' );
function vinea_registrar_cpt_formularios(): void {
	$comunes = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_rest'        => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'supports'            => array( 'title', 'editor' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
	);

	register_post_type(
		'vinea_mensaje',
		array_merge(
			$comunes,
			array(
				'labels'    => array(
					'name'          => 'Mensajes',
					'singular_name' => 'Mensaje',
					'menu_name'     => 'Mensajes',
					'all_items'     => 'Todos los mensajes',
					'edit_item'     => 'Ver mensaje',
					'not_found'     => 'No hay mensajes.',
				),
				'menu_icon' => 'dashicons-email-alt',
				'menu_position' => 25,
			)
		)
	);

	register_post_type(
		'vinea_suscriptor',
		array_merge(
			$comunes,
			array(
				'labels'    => array(
					'name'          => 'Suscriptores',
					'singular_name' => 'Suscriptor',
					'menu_name'     => 'Suscriptores',
					'all_items'     => 'Todos los suscriptores',
					'edit_item'     => 'Ver suscriptor',
					'not_found'     => 'No hay suscriptores.',
				),
				'menu_icon' => 'dashicons-groups',
				'menu_position' => 26,
				'supports'  => array( 'title' ),
			)
		)
	);
}

// Columnas en los listados.
add_filter(
	'manage_vinea_mensaje_posts_columns',
	static function ( array $c ): array {
		return array(
			'cb'      => $c['cb'] ?? '',
			'title'   => 'Nombre',
			'email'   => 'Email',
			'movil'   => 'Móvil',
			'interes' => 'Interés',
			'date'    => 'Fecha',
		);
	}
);
add_filter(
	'manage_vinea_suscriptor_posts_columns',
	static function ( array $c ): array {
		return array(
			'cb'    => $c['cb'] ?? '',
			'title' => 'Nombre',
			'email' => 'Email',
			'date'  => 'Fecha',
		);
	}
);
add_action( 'manage_vinea_mensaje_posts_custom_column', 'vinea_columna_formularios', 10, 2 );
add_action( 'manage_vinea_suscriptor_posts_custom_column', 'vinea_columna_formularios', 10, 2 );
function vinea_columna_formularios( string $columna, int $id ): void {
	if ( in_array( $columna, array( 'email', 'movil', 'interes' ), true ) ) {
		echo esc_html( (string) get_post_meta( $id, '_vinea_' . $columna, true ) );
	}
}

// Datos del mensaje al editar (solo lectura).
add_action(
	'add_meta_boxes',
	static function (): void {
		add_meta_box(
			'vinea_datos_envio',
			'Datos del envío',
			static function ( WP_Post $post ): void {
				$filas = array(
					'email'   => 'Email',
					'movil'   => 'Móvil',
					'interes' => 'Interés',
					'origen'  => 'Página de origen',
				);
				echo '<table class="form-table" role="presentation">';
				foreach ( $filas as $k => $etiqueta ) {
					$v = (string) get_post_meta( $post->ID, '_vinea_' . $k, true );
					if ( '' === $v ) {
						continue;
					}
					printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $etiqueta ), esc_html( $v ) );
				}
				echo '</table>';
			},
			array( 'vinea_mensaje', 'vinea_suscriptor' ),
			'side'
		);
	}
);

/* ------------------------------------------------------------------
 * Utilidades
 * ---------------------------------------------------------------- */
function vinea_opciones_interes(): array {
	return array( 'Información general', 'Eventos auténticos', 'Espacios alucinantes', 'Consultorías y formación' );
}

// Página a la que se vuelve tras enviar: la de origen (misma web) o la portada.
function vinea_url_origen(): string {
	$ref = wp_get_referer();
	$url = $ref ? $ref : home_url( '/' );
	return remove_query_arg( array( 'enviado', 'error', 'suscrito', 'error_nl' ), $url );
}

function vinea_redirigir( array $args, string $ancla ): void {
	$url = add_query_arg( $args, vinea_url_origen() ) . '#' . $ancla;
	wp_safe_redirect( $url, 303 );
	exit;
}

function vinea_limpiar_cabecera( string $s ): string {
	return trim( str_replace( array( "\r", "\n", '%0a', '%0d' ), ' ', $s ) );
}

/* ------------------------------------------------------------------
 * Contacto
 * ---------------------------------------------------------------- */
add_action( 'admin_post_nopriv_vinea_contacto', 'vinea_procesar_contacto' );
add_action( 'admin_post_vinea_contacto', 'vinea_procesar_contacto' );
function vinea_procesar_contacto(): void {
	if ( ! isset( $_POST['vinea_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vinea_nonce'] ) ), 'vinea_contacto' ) ) {
		vinea_redirigir( array( 'error' => 'seguridad' ), 'formulario' );
	}

	// Honeypot: un bot rellena «web». Se simula éxito sin guardar nada.
	if ( ! empty( $_POST['web'] ) ) {
		vinea_redirigir( array( 'enviado' => '1' ), 'formulario' );
	}

	$nombre     = vinea_limpiar_cabecera( sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) ) );
	$movil      = vinea_limpiar_cabecera( sanitize_text_field( wp_unslash( $_POST['movil'] ?? '' ) ) );
	$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$interes    = sanitize_text_field( wp_unslash( $_POST['interes'] ?? '' ) );
	$mensaje    = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );
	$privacidad = ! empty( $_POST['privacidad'] );

	if ( '' === $nombre || ! is_email( $email ) || '' === trim( $mensaje ) || ! $privacidad ) {
		vinea_redirigir( array( 'error' => 'campos' ), 'formulario' );
	}
	if ( ! in_array( $interes, vinea_opciones_interes(), true ) ) {
		$interes = '';
	}

	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'vinea_mensaje',
				'post_status'  => 'publish',
				'post_title'   => $nombre,
				'post_content' => $mensaje,
				'meta_input'   => array(
					'_vinea_email'   => $email,
					'_vinea_movil'   => $movil,
					'_vinea_interes' => $interes,
					'_vinea_origen'  => esc_url_raw( vinea_url_origen() ),
				),
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		vinea_redirigir( array( 'error' => 'envio' ), 'formulario' );
	}

	$cuerpo  = "Nuevo mensaje desde el formulario de contacto de la web.\n\n";
	$cuerpo .= "Nombre: $nombre\nMóvil: $movil\nEmail: $email\nInterés: $interes\n\nMensaje:\n$mensaje\n";
	$cabeceras = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $nombre . ' <' . $email . '>',
	);
	wp_mail( vinea_ajuste( 'email_formularios' ), '[Web] Nuevo mensaje de ' . $nombre, $cuerpo, $cabeceras );

	vinea_redirigir( array( 'enviado' => '1' ), 'formulario' );
}

/* ------------------------------------------------------------------
 * Newsletter
 * ---------------------------------------------------------------- */
add_action( 'admin_post_nopriv_vinea_newsletter', 'vinea_procesar_newsletter' );
add_action( 'admin_post_vinea_newsletter', 'vinea_procesar_newsletter' );
function vinea_procesar_newsletter(): void {
	if ( ! isset( $_POST['vinea_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vinea_nonce'] ) ), 'vinea_newsletter' ) ) {
		vinea_redirigir( array( 'error_nl' => 'seguridad' ), 'newsletter' );
	}
	if ( ! empty( $_POST['web'] ) ) {
		vinea_redirigir( array( 'suscrito' => '1' ), 'newsletter' );
	}

	$nombre = vinea_limpiar_cabecera( sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) ) );
	$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		vinea_redirigir( array( 'error_nl' => 'campos' ), 'newsletter' );
	}

	// Evitar duplicados por email.
	$existe = get_posts(
		array(
			'post_type'      => 'vinea_suscriptor',
			'post_status'    => 'any',
			'meta_key'       => '_vinea_email',
			'meta_value'     => $email,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $existe ) {
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'vinea_suscriptor',
					'post_status' => 'publish',
					'post_title'  => '' !== $nombre ? $nombre : $email,
					'meta_input'  => array(
						'_vinea_email'  => $email,
						'_vinea_origen' => esc_url_raw( vinea_url_origen() ),
					),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			vinea_redirigir( array( 'error_nl' => 'envio' ), 'newsletter' );
		}
		wp_mail(
			vinea_ajuste( 'email_formularios' ),
			'[Web] Nueva suscripción a la newsletter',
			"Nueva suscripción a la newsletter.\n\nNombre: $nombre\nEmail: $email\n",
			array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email )
		);
	}

	vinea_redirigir( array( 'suscrito' => '1' ), 'newsletter' );
}

/* ------------------------------------------------------------------
 * Marcado
 * ---------------------------------------------------------------- */
function vinea_estado_formulario( string $tipo ): array {
	// Devuelve [mensaje, clase de estado] a partir de los parámetros de la URL.
	$get = static fn( string $k ): string => isset( $_GET[ $k ] ) ? sanitize_key( wp_unslash( $_GET[ $k ] ) ) : '';
	if ( 'contacto' === $tipo && '1' === $get( 'enviado' ) ) {
		return array( 'Gracias, hemos recibido tu mensaje.', 'ok' );
	}
	if ( 'newsletter' === $tipo && '1' === $get( 'suscrito' ) ) {
		return array( 'Gracias, te has suscrito correctamente.', 'ok' );
	}
	$error = $get( 'newsletter' === $tipo ? 'error_nl' : 'error' );
	if ( 'campos' === $error ) {
		return array( 'Revisa los campos obligatorios e inténtalo de nuevo.', 'error' );
	}
	if ( '' !== $error ) {
		return array( 'No hemos podido enviarlo. Inténtalo de nuevo en unos minutos.', 'error' );
	}
	return array( '', '' );
}

function vinea_formulario_contacto(): void {
	[ $estado, $clase ] = vinea_estado_formulario( 'contacto' );
	?>
	<form class="formulario" id="formulario" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-formulario novalidate>
		<input type="hidden" name="action" value="vinea_contacto">
		<?php wp_nonce_field( 'vinea_contacto', 'vinea_nonce' ); ?>
		<div class="campo__fila">
			<div class="campo">
				<label for="f-nombre">Nombre completo</label>
				<input type="text" id="f-nombre" name="nombre" autocomplete="name" required aria-describedby="e-nombre">
				<p class="campo__error" id="e-nombre" hidden>Escribe tu nombre.</p>
			</div>
			<div class="campo">
				<label for="f-movil">Móvil</label>
				<input type="tel" id="f-movil" name="movil" autocomplete="tel" aria-describedby="e-movil">
				<p class="campo__error" id="e-movil" hidden>Revisa tu número de móvil.</p>
			</div>
		</div>
		<div class="campo">
			<label for="f-email">E-mail</label>
			<input type="email" id="f-email" name="email" autocomplete="email" required aria-describedby="e-email">
			<p class="campo__error" id="e-email" hidden>Escribe un e-mail válido.</p>
		</div>
		<div class="campo">
			<label for="f-interes">¿Que te interesa?</label>
			<select class="campo__select" id="f-interes" name="interes">
				<?php foreach ( vinea_opciones_interes() as $opcion ) : ?>
					<option value="<?php echo esc_attr( $opcion ); ?>"><?php echo esc_html( $opcion ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="campo">
			<label for="f-mensaje">Mensaje</label>
			<textarea id="f-mensaje" name="mensaje" rows="6" required aria-describedby="e-mensaje"></textarea>
			<p class="campo__error" id="e-mensaje" hidden>Escribe tu mensaje.</p>
		</div>
		<div class="campo campo--check">
			<input type="checkbox" id="f-privacidad" name="privacidad" value="1" required aria-describedby="e-privacidad">
			<label for="f-privacidad">He leído y acepto la <a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">política de privacidad</a></label>
			<p class="campo__error" id="e-privacidad" hidden>Debes aceptar la política de privacidad.</p>
		</div>
		<div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
			<label for="f-web">No rellenar este campo</label>
			<input type="text" id="f-web" name="web" tabindex="-1" autocomplete="off">
		</div>
		<button class="boton boton--naranja formulario__enviar" type="submit">Enviar <span class="flecha" aria-hidden="true">→</span></button>
		<p class="formulario__estado<?php echo '' !== $clase ? ' formulario__estado--' . esc_attr( $clase ) : ''; ?>" role="status" aria-live="polite" data-estado><?php echo esc_html( $estado ); ?></p>
	</form>
	<?php
}

function vinea_formulario_newsletter(): void {
	[ $estado, $clase ] = vinea_estado_formulario( 'newsletter' );
	?>
	<form class="newsletter" id="newsletter" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-formulario novalidate>
		<h2 class="newsletter__titulo">apúntate a la newsletter</h2>
		<input type="hidden" name="action" value="vinea_newsletter">
		<?php wp_nonce_field( 'vinea_newsletter', 'vinea_nonce' ); ?>
		<div class="newsletter__campos">
			<div class="campo">
				<label for="n-nombre">Tu nombre</label>
				<input type="text" id="n-nombre" name="nombre" autocomplete="name">
			</div>
			<div class="campo">
				<label for="n-email">Email</label>
				<input type="email" id="n-email" name="email" autocomplete="email" required aria-describedby="e-n-email">
				<p class="campo__error" id="e-n-email" hidden>Escribe un e-mail válido.</p>
			</div>
			<div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
				<label for="n-web">No rellenar este campo</label>
				<input type="text" id="n-web" name="web" tabindex="-1" autocomplete="off">
			</div>
			<button class="boton boton--claro" type="submit">Subscríbete</button>
		</div>
		<p class="formulario__estado<?php echo '' !== $clase ? ' formulario__estado--' . esc_attr( $clase ) : ''; ?>" role="status" aria-live="polite" data-estado><?php echo esc_html( $estado ); ?></p>
	</form>
	<?php
}
