<?php
/**
 * Pulido de las páginas interiores. Ningún texto se cambia: solo se ordena y se presenta mejor
 * el contenido importado de la web original.
 * - Quita restos técnicos del plugin de eventos («thumbnail start», «content end»…).
 * - Títulos larguísimos (párrafos que en el original son H2) se muestran a tamaño de texto.
 * - Entradas: tarjetas de eventos. Nuestra esencia: valores en rejilla y fichas del equipo.
 * - Ilustración de la gota para tarjetas sin foto (fotos rotas también en el original).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Ilustración 2D de Vinea (gotas y órbitas) para cuando no hay foto. */
function vinea_ilustracion( string $titulo = '' ): string {
	$n = abs( crc32( $titulo ) ) % 4;
	$giro = array( 0, 90, 180, 270 )[ $n ];
	return '<span class="ilus" style="--g:' . (int) $giro . 'deg" aria-hidden="true"><svg viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice">'
		. '<defs><linearGradient id="ilus-' . (int) $n . '" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#ff8a66"/><stop offset="1" stop-color="#ff5611"/></linearGradient></defs>'
		. '<rect width="400" height="300" fill="#ece2d1"/>'
		. '<circle cx="200" cy="150" r="118" fill="none" stroke="#141210" stroke-opacity=".14" stroke-dasharray="3 7"/>'
		. '<circle cx="200" cy="150" r="78" fill="none" stroke="#b83a00" stroke-opacity=".35"/>'
		. '<g class="ilus__gota"><path d="M168 96h52a54 54 0 1 1-54 54v-50q0-4 2-4z" fill="url(#ilus-' . (int) $n . ')"/></g>'
		. '<path d="M300 60h16a16 16 0 1 1-16 16V61z" fill="#141210" opacity=".85"/>'
		. '<path d="M84 214h10a10 10 0 1 1-10 10v-10z" fill="#ff5611"/>'
		. '</svg></span>';
}

add_filter(
	'the_content',
	static function ( $html ) {
		if ( is_admin() || ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		// Restos técnicos del plugin de eventos del original.
		$html = preg_replace( '~<p>\s*(?:thumbnail(?: start| end)?|content (?:start|end)|etn event item end)\s*</p>\s*~i', '', $html );

		// Títulos que en realidad son párrafos muy largos.
		$html = preg_replace_callback(
			'~<h2(\s[^>]*)?>(.*?)</h2>~is',
			static function ( $m ) {
				return mb_strlen( wp_strip_all_tags( $m[2] ) ) > 220 ? '<h2 class="h-largo"' . ( $m[1] ?? '' ) . '>' . $m[2] . '</h2>' : $m[0];
			},
			$html
		);

		if ( is_page( 'eventos-2' ) && function_exists( 'vinea_tarjetas_eventos' ) ) {
			$tarjetas = vinea_tarjetas_eventos();
			if ( '' !== $tarjetas ) {
				// Las figuras sueltas + datos de cada evento se sustituyen por las tarjetas.
				$html = preg_replace( '~(<section class="bloque[^"]*">).*?(</section>)~s', '$1' . str_replace( '$', '\$', $tarjetas ) . '$2', $html, 1 );
			}
		}

		if ( is_page( 'descubre-esencia-vinea-eventos' ) ) {
			$html = vinea_pulir_esencia( $html );
		}
		return $html;
	},
	30
);

/** Nuestra esencia: «figura + título + texto» → tarjetas de valores; equipo → fichas con foto. */
function vinea_pulir_esencia( string $html ): string {
	$doc = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="utf-8"?><div id="vr">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	$raiz = $doc->getElementById( 'vr' );
	if ( ! $raiz ) {
		return $html;
	}
	$hijos = static function ( DOMNode $n ): array {
		$o = array();
		foreach ( $n->childNodes as $c ) {
			if ( XML_ELEMENT_NODE === $c->nodeType ) {
				$o[] = $c;
			}
		}
		return $o;
	};

	// 0) En la web original cada foto va ANTES del nombre de la persona (Lara Roca, Mirian Hevia).
	//    Al importar, la foto quedó al final del bloque anterior: se devuelve a su sitio.
	$secciones = $hijos( $raiz );
	for ( $i = 0; $i < count( $secciones ) - 1; $i++ ) {
		$els = $hijos( $secciones[ $i ] );
		$sig = $hijos( $secciones[ $i + 1 ] );
		$ult = end( $els );
		if ( $ult && 'figure' === $ult->nodeName && $sig && 'h2' === $sig[0]->nodeName && false === strpos( $secciones[ $i ]->textContent, 'El equipo Vinea' ) ) {
			$secciones[ $i + 1 ]->insertBefore( $ult, $sig[0] );
		}
	}
	// 0b) Departamento de Laia Ginés (en la web original: «Departamento de producción y comercialización»).
	foreach ( $raiz->getElementsByTagName( 'p' ) as $pp ) {
		if ( 'Laia Ginés' === trim( $pp->textContent ) ) {
			$n = $pp->nextSibling;
			while ( $n && XML_ELEMENT_NODE !== $n->nodeType ) {
				$n = $n->nextSibling;
			}
			if ( ! $n || 0 !== strpos( trim( $n->textContent ), 'Departamento' ) ) {
				$pp->parentNode->insertBefore( $doc->createElement( 'p', 'Departamento de producción y comercialización' ), $n );
			}
			break;
		}
	}

	foreach ( $hijos( $raiz ) as $seccion ) {
		$els = $hijos( $seccion );
		// 1) Valores: figure, h3, p repetidos.
		$i = 0;
		$grupos = array();
		while ( $i < count( $els ) - 2 ) {
			if ( 'figure' === $els[ $i ]->nodeName && 'h3' === $els[ $i + 1 ]->nodeName && 'p' === $els[ $i + 2 ]->nodeName ) {
				$grupos[] = array( $els[ $i ], $els[ $i + 1 ], $els[ $i + 2 ] );
				$i += 3;
				continue;
			}
			++$i;
		}
		if ( count( $grupos ) >= 3 ) {
			$rejilla = $doc->createElement( 'div' );
			$rejilla->setAttribute( 'class', 'valores' );
			$seccion->insertBefore( $rejilla, $grupos[0][0] );
			foreach ( $grupos as $k => $g ) {
				$t = $doc->createElement( 'article' );
				$t->setAttribute( 'class', 'valor' );
				$t->setAttribute( 'data-reveal', '' );
				$t->setAttribute( 'style', '--d:' . ( $k * 90 ) );
				$num = $doc->createElement( 'span', str_pad( (string) ( $k + 1 ), 2, '0', STR_PAD_LEFT ) );
				$num->setAttribute( 'class', 'valor__num' );
				$num->setAttribute( 'aria-hidden', 'true' );
				$t->appendChild( $g[0] );
				$t->appendChild( $num );
				$t->appendChild( $g[1] );
				$t->appendChild( $g[2] );
				$rejilla->appendChild( $t );
			}
			$sig = $rejilla->nextSibling;
			while ( $sig && XML_ELEMENT_NODE !== $sig->nodeType ) {
				$sig = $sig->nextSibling;
			}
			if ( $sig && 'figure' === $sig->nodeName ) {
				$sig->setAttribute( 'class', 'valores__foto' );
			}
		}

		// 2) Equipo: tras «El equipo Vinea», fotos y nombres → fichas.
		$els = $hijos( $seccion );
		foreach ( $els as $k => $el ) {
			if ( 'p' !== $el->nodeName || 'El equipo Vinea' !== trim( $el->textContent ) ) {
				continue;
			}
			$el->setAttribute( 'class', 'equipo__titulo' );
			$resto  = array_slice( $els, $k + 2 );
			$fotos  = array();
			$nombres = array();
			foreach ( $resto as $j => $r ) {
				if ( 'figure' === $r->nodeName ) {
					$fotos[] = $r;
				} elseif ( 'p' === $r->nodeName ) {
					$txt = trim( $r->textContent );
					if ( 0 === strpos( $txt, 'Departamento' ) ) {
						if ( $nombres && ! $nombres[ count( $nombres ) - 1 ]['dep'] ) {
							$nombres[ count( $nombres ) - 1 ]['dep'] = $r; // cada departamento va con el nombre que lo precede
						}
						$r->parentNode->removeChild( $r );
					} else {
						$nombres[] = array( 'nombre' => $r, 'dep' => null );
					}
				}
			}
			if ( count( $nombres ) < 2 || count( $fotos ) < count( $nombres ) ) {
				break;
			}
			$lista = $doc->createElement( 'ul' );
			$lista->setAttribute( 'class', 'equipo' );
			$seccion->insertBefore( $lista, $fotos[0] );
			foreach ( $nombres as $j => $n ) {
				$li = $doc->createElement( 'li' );
				$li->setAttribute( 'class', 'miembro' );
				$li->setAttribute( 'data-reveal', '' );
				$li->setAttribute( 'style', '--d:' . ( $j * 90 ) );
				$li->appendChild( $fotos[ $j ] );
				$n['nombre']->setAttribute( 'class', 'miembro__nombre' );
				$li->appendChild( $n['nombre'] );
				if ( $n['dep'] ) {
					$dep = $n['dep']->cloneNode( true );
					$dep->setAttribute( 'class', 'miembro__dep' );
					$li->appendChild( $dep );
				}
				$lista->appendChild( $li );
			}
			// El equipo pasa a su propio bloque, detrás del de Mirian
			$nuevo = $doc->createElement( 'section' );
			$nuevo->setAttribute( 'class', 'bloque bloque--equipo' );
			$intro = $el->nextSibling;
			while ( $intro && XML_ELEMENT_NODE !== $intro->nodeType ) {
				$intro = $intro->nextSibling;
			}
			$nuevo->appendChild( $el );
			if ( $intro && 'p' === $intro->nodeName ) {
				$nuevo->appendChild( $intro );
			}
			$nuevo->appendChild( $lista );
			$seccion->parentNode->insertBefore( $nuevo, $seccion->nextSibling );
			break;
		}
	}
	$out = '';
	foreach ( $raiz->childNodes as $c ) {
		$out .= $doc->saveHTML( $c );
	}
	return $out;
}
