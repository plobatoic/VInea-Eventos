# Importador del contenido original

Importa páginas, entradas, categorías, imágenes, portada y menús desde `contenido.json` (y `portada.json`).

## Requisitos
1. Tema `vinea` activo.
2. Crear `importar/token.php` con una clave larga: `<?php return 'CLAVE_LARGA';` (sin este archivo el importador está desactivado).

## Cómo lanzarlo
Abre o llama, en orden, a:

    curl "https://DOMINIO/?vinea_importar=TOKEN&paso=0"

La respuesta es JSON: `{ok, paso, siguiente, total_estimado, mensaje, detalles}`.
Repite con `paso=<siguiente>` hasta que `siguiente` sea `null`.

Bucle de ejemplo:

    P=0; while [ "$P" != "null" ]; do R=$(curl -s "https://DOMINIO/?vinea_importar=TOKEN&paso=$P"); echo "$R"; P=$(echo "$R" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo ($j["ok"]??false) ? ($j["siguiente"]??"null") : "null";'); done

## Pasos
- 0: ajustes del sitio, borra contenido de ejemplo, crea categorías.
- 1: páginas. 2: entradas.
- 3: prepara la cola de imágenes. 4…: descarga de imágenes por lotes (5 por paso).
- Penúltimo: reemplaza URLs antiguas por las nuevas, añade `wp-image-ID`, imágenes destacadas y guarda la portada.
- Último: portada y página de entradas, menús «Principal» y «Pie», permalinks.

## Notas
- Es idempotente: se puede repetir. Al terminar queda `vinea_importado=1`; para repetir añade `&forzar=1`.
- Las imágenes que fallen aparecen en `detalles.fallos_lista` del último paso.
- Borra `importar/token.php` cuando termines.
