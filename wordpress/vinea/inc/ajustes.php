<?php
/**
 * Ajustes → Vinea: datos de contacto y destino de los formularios.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VINEA_OPCION_AJUSTES = 'vinea_ajustes';

function vinea_ajustes_por_defecto(): array {
	return array(
		'email_formularios' => get_option( 'admin_email' ),
		'telefono_1'        => '+34 611 88 40 83',
		'telefono_2'        => '+34 623 00 43 81',
		'email_publico'     => 'info@vineaeventos.com',
		'instagram'         => 'https://www.instagram.com/vinea_eventos_turismo/',
		'linkedin'          => 'https://www.linkedin.com/company/vinea-eventos-y-turismo',
	);
}

/**
 * Devuelve un ajuste del tema (o su valor por defecto).
 */
function vinea_ajuste( string $clave ): string {
	static $cache = null;
	if ( null === $cache ) {
		$guardado = get_option( VINEA_OPCION_AJUSTES, array() );
		$cache    = array_merge( vinea_ajustes_por_defecto(), array_filter( is_array( $guardado ) ? $guardado : array(), static fn( $v ) => '' !== $v && null !== $v ) );
	}
	return isset( $cache[ $clave ] ) ? (string) $cache[ $clave ] : '';
}


add_action( 'admin_menu', 'vinea_ajustes_menu' );
function vinea_ajustes_menu(): void {
	add_options_page( 'Ajustes de Vinea', 'Vinea', 'manage_options', 'vinea-ajustes', 'vinea_ajustes_pagina' );
}

add_action( 'admin_init', 'vinea_ajustes_registrar' );
function vinea_ajustes_registrar(): void {
	register_setting(
		'vinea_ajustes_grupo',
		VINEA_OPCION_AJUSTES,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'vinea_ajustes_sanear',
			'default'           => array(),
		)
	);
}

function vinea_ajustes_sanear( $entrada ): array {
	$entrada = is_array( $entrada ) ? $entrada : array();
	$limpio  = array();

	$limpio['email_formularios'] = sanitize_email( $entrada['email_formularios'] ?? '' );
	if ( ! is_email( $limpio['email_formularios'] ) ) {
		$limpio['email_formularios'] = '';
	}
	$limpio['email_publico'] = sanitize_email( $entrada['email_publico'] ?? '' );
	if ( ! is_email( $limpio['email_publico'] ) ) {
		$limpio['email_publico'] = '';
	}
	$limpio['telefono_1'] = sanitize_text_field( $entrada['telefono_1'] ?? '' );
	$limpio['telefono_2'] = sanitize_text_field( $entrada['telefono_2'] ?? '' );
	$limpio['instagram']  = esc_url_raw( $entrada['instagram'] ?? '' );
	$limpio['linkedin']   = esc_url_raw( $entrada['linkedin'] ?? '' );
	return $limpio;
}

function vinea_ajustes_pagina(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$campos = array(
		'email_formularios' => array( 'Email que recibe los formularios', 'email', 'Aquí llegan los mensajes de contacto y las altas de la newsletter.' ),
		'telefono_1'        => array( 'Teléfono 1', 'text', '' ),
		'telefono_2'        => array( 'Teléfono 2', 'text', '' ),
		'email_publico'     => array( 'Email público', 'email', 'El que se muestra en la web (página de contacto y datos estructurados).' ),
		'instagram'         => array( 'URL de Instagram', 'url', '' ),
		'linkedin'          => array( 'URL de LinkedIn', 'url', '' ),
	);
	?>
	<div class="wrap">
		<h1>Ajustes de Vinea</h1>
		<?php if ( 'info@vineaeventos.com' !== strtolower( vinea_ajuste( 'email_formularios' ) ) ) : ?>
			<div class="notice notice-warning inline"><p><strong>Atención:</strong> en producción el email que recibe los formularios debe ser <code>info@vineaeventos.com</code>.</p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'vinea_ajustes_grupo' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $campos as $clave => $c ) : ?>
					<tr>
						<th scope="row"><label for="vinea-<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $c[0] ); ?></label></th>
						<td>
							<input type="<?php echo esc_attr( $c[1] ); ?>" class="regular-text" id="vinea-<?php echo esc_attr( $clave ); ?>"
								name="<?php echo esc_attr( VINEA_OPCION_AJUSTES . '[' . $clave . ']' ); ?>"
								value="<?php echo esc_attr( vinea_ajuste( $clave ) ); ?>">
							<?php if ( $c[2] ) : ?>
								<p class="description"><?php echo esc_html( $c[2] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
