# Vinea Eventos — web nueva

Rediseño completo de [vineaeventos.com](https://vineaeventos.com): mismo contenido, estética nueva, elementos 3D y transiciones, y carga rápida.

```
vinea-eventos/
├── web/             ← la web (Astro). Es lo que se publica.
└── investigacion/   ← material extraído de la web antigua, el audio e Instagram (no se publica)
```

## Ver la web en tu ordenador

```bash
cd web
npm install
npm run dev        # abre http://localhost:4321
```

Para generar la versión final: `npm run build`. El resultado queda en `web/dist/`.

## Publicar

La carpeta `web/dist/` es una web estática: funciona en cualquier hosting.

| Dónde | Cómo |
|---|---|
| **Netlify** (recomendado para enseñarla) | Conecta el repositorio. *Base directory*: `web` · *Build command*: `npm run build` · *Publish directory*: `web/dist`. El formulario de contacto funciona solo con Netlify Forms. |
| **Vercel** | *Root directory*: `web`. Usa el `vercel.json` que se genera en `dist/`. |
| **Hostinger / Apache** | Sube el contenido de `web/dist/` a `public_html`. El `.htaccess` generado incluye las redirecciones 301 y la caché. |

Al cambiar el dominio: **no toques los registros MX**, porque si no se pierde el correo.

## Cómo se edita el contenido

Todo el texto está en `web/src/content/`, en archivos Markdown que se pueden editar sin saber programar.

- `servicios/` — un archivo por servicio o experiencia. **Para crear una landing nueva** (por ejemplo, "Verano en Vinea"), duplica `navidad.md`, cambia los textos y las fotos, y pon `tipo: experiencia`. La página aparece sola en `/servicios/<nombre-del-archivo>/`, en el menú del pie y en el listado.
- `proyectos/` — los casos de éxito (el antiguo blog).
- `eventos/` — la agenda con entradas.
- `paginas/` — Nuestra esencia, Accesibilidad e inclusión, Contacto y textos legales.
- `home.json` — textos de la portada (claim, cifras, clientes y testimonio).

Las fotos van en `web/src/assets/img/` y se citan por su nombre de archivo. La web las optimiza sola: WebP en varios tamaños.

## Qué lleva la web

- **Gota 3D del logo** (WebGL propio, unos 6 KB, sin librerías). Al hacer scroll se convierte en una ventana que se abre sobre la foto del Liceu. Se adapta a la potencia del dispositivo y solo arranca cuando la persona interactúa, para no frenar la carga.
- **Transiciones entre páginas** con la View Transitions API del navegador, sin JavaScript. La foto de un servicio "viaja" de la tarjeta a la cabecera.
- **Servicios en scroll horizontal** con tarjetas que entran girando en 3D, **collage con profundidad**, contadores, textos que aparecen palabra a palabra e inclinación 3D al pasar el ratón.
- **Accesible:** respeta "reducir movimiento" del sistema, tiene un botón propio "Reducir animaciones" en el pie, navegación con teclado, contraste AA y textos alternativos.
- **SEO:** sitemap, datos estructurados, Open Graph y **40 redirecciones 301** desde las URL antiguas.

Lighthouse (local): portada 94 en móvil y 100 en ordenador; páginas de servicio entre 96 y 100. Accesibilidad, buenas prácticas y SEO: 100.

## Pendiente de revisar con Vinea

Hay comentarios `<!-- REVISAR -->` en los textos legales.

- **Aviso legal:** NIF de las titulares (ahora pone "[NIF pendiente]").
- **Cookies y privacidad:** el texto antiguo menciona pagos, Mailchimp y Calendly. Hay que confirmar qué herramientas usan de verdad.
- **Precios:** la cata sensorial aparece con dos precios distintos en sus carteles (110 € y 100 €), así que no se ha publicado ninguno. Los de Padel Experience están como "orientativos 2026".
- **Testimonios:** se muestran con el nombre que aparecía en la web antigua. Hay que confirmar que se pueden seguir publicando así.
- **Logos de clientes** (Amazon, BMW, SEAT…): estaban en la web antigua pero no se han puesto hasta que lo confirmen.
- **Fotos de la landing de Navidad:** parecen ilustraciones generadas con IA. Si tienen fotos reales, mejor.
- **Logo:** se ha vectorizado a partir de la imagen de su web. Si tienen el original en SVG o AI, conviene sustituirlo en `web/public/img/`.
