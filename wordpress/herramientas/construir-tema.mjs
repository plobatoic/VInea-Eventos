/**
 * Construye los recursos del tema WordPress "vinea" a partir del proyecto web:
 *  - assets/css/vinea.css: estilos globales + componentes + páginas (con ámbito por clase de body) + wordpress/estilos/wp.css
 *  - assets/js/sitio.js e inicio.js (GSAP, Lenis, gota 3D de cristal, partículas)
 *  - assets/fonts: tipografías Gelasio y Barlow
 * Uso: node ../wordpress/herramientas/construir-tema.mjs   (desde web/)
 */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
const esbuild = createRequire(path.resolve('package.json'))('esbuild');

const WEB = path.resolve('.');
const WP = path.resolve('../wordpress');
const TEMA = path.join(WP, 'vinea');
fs.mkdirSync(path.join(TEMA, 'assets/css'), { recursive: true });
fs.mkdirSync(path.join(TEMA, 'assets/js'), { recursive: true });
fs.mkdirSync(path.join(TEMA, 'assets/fonts'), { recursive: true });

// --- Fuentes
const fuentes = [
  ['gelasio', 'gelasio-latin-400-normal', 400, 'normal'], ['gelasio', 'gelasio-latin-400-italic', 400, 'italic'],
  ['gelasio', 'gelasio-latin-600-normal', 600, 'normal'], ['barlow', 'barlow-latin-300-normal', 300, 'normal'],
  ['barlow', 'barlow-latin-400-normal', 400, 'normal'], ['barlow', 'barlow-latin-500-normal', 500, 'normal'],
  ['barlow', 'barlow-latin-600-normal', 600, 'normal'],
];
let fontFace = '';
for (const [fam, archivo, peso, estilo] of fuentes) {
  fs.copyFileSync(path.join(WEB, `node_modules/@fontsource/${fam}/files/${archivo}.woff2`), path.join(TEMA, `assets/fonts/${archivo}.woff2`));
  const nombre = fam === 'gelasio' ? 'Gelasio' : 'Barlow';
  fontFace += `@font-face{font-family:'${nombre}';font-style:${estilo};font-weight:${peso};font-display:swap;src:url(../fonts/${archivo}.woff2) format('woff2');}\n`;
}

// --- Estilos de un .astro: quita :global(...) y saca los @keyframes al nivel superior
const quitarGlobal = (css) => {
  let out = '', i = 0;
  while (i < css.length) {
    const k = css.indexOf(':global(', i);
    if (k < 0) { out += css.slice(i); break; }
    out += css.slice(i, k);
    let j = k + 8, nivel = 1;
    while (j < css.length && nivel) { if (css[j] === '(') nivel++; else if (css[j] === ')') nivel--; j++; }
    out += css.slice(k + 8, j - 1);
    i = j;
  }
  return out;
};
const sacarKeyframes = (css) => {
  const kf = [];
  let out = '', i = 0;
  while (true) {
    const k = css.indexOf('@keyframes', i);
    if (k < 0) { out += css.slice(i); break; }
    out += css.slice(i, k);
    let j = css.indexOf('{', k) + 1, nivel = 1;
    while (j < css.length && nivel) { if (css[j] === '{') nivel++; else if (css[j] === '}') nivel--; j++; }
    kf.push(css.slice(k, j));
    i = j;
  }
  return [out, kf.join('\n')];
};
const estilosDe = (archivo) => {
  const src = fs.readFileSync(path.join(WEB, archivo), 'utf8');
  return [...src.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].map((m) => m[1]).join('\n');
};
const bloque = (archivo, ambito) => {
  const [css, kf] = sacarKeyframes(quitarGlobal(estilosDe(archivo)));
  // html/body/.movimiento... van fuera del ámbito: las reglas que empiezan por ellos se dejan tal cual
  return `${kf}\n${ambito ? `${ambito} {\n${css}\n}` : css}\n`;
};

let globalCss = fs.readFileSync(path.join(WEB, 'src/styles/global.css'), 'utf8').replace(/@import[^;]+;\n?/g, '');
let css = fontFace + globalCss;
css += bloque('src/components/CabeceraPagina.astro');
css += bloque('src/components/BandaCTA.astro');
css += bloque('src/components/TarjetaServicio.astro');
css += bloque('src/components/TarjetaProyecto.astro');
css += bloque('src/components/Cabecera.astro');
css += bloque('src/components/Pie.astro');
css += bloque('src/pages/index.astro', '.pg-inicio');
css += bloque('src/pages/servicios/index.astro', '.pg-pagina');
css += bloque('src/pages/servicios/[slug].astro', '.pg-servicio');
css += bloque('src/pages/proyectos/index.astro', '.pg-proyectos');
css += bloque('src/pages/proyectos/[slug].astro', '.pg-proyecto');
css += bloque('src/pages/contacto.astro', '.pg-contacto');
css += bloque('src/pages/404.astro', '.pg-404');
css += fs.readFileSync(path.join(WP, 'estilos/wp.css'), 'utf8');
// Dentro de un ámbito, los selectores que empiezan por .movimiento/.portal-animado/html deben ir delante del ámbito
css = css.replace(/(\.pg-[a-z0-9-]+) \{\n([\s\S]*?)\n\}\n/g, (todo, ambito, cuerpo) =>
  `${ambito} {\n${cuerpo.replace(/(^|\n|,)\s*(\.movimiento|\.sin-movimiento|\.portal-animado|\.gota-3d|\.pista-horizontal|\.menu-abierto|html(?:[:.\[][^\s,{]*)?)\s+/g, (m, sep, raiz) => `${sep} ${raiz} & `)}\n}\n`);
fs.writeFileSync(path.join(WP, 'estilos/.vinea.src.css'), css);

await esbuild.build({
  entryPoints: [path.join(WP, 'estilos/.vinea.src.css')],
  outfile: path.join(TEMA, 'assets/css/vinea.css'),
  bundle: true, minify: true, external: ['../fonts/*', '../img/*'],
  target: ['chrome100', 'safari15', 'firefox100', 'edge100'],
  loader: { '.woff2': 'file' }, logLevel: 'warning',
});

// --- JavaScript
await esbuild.build({
  entryPoints: { sitio: path.join(WEB, 'src/scripts/sitio.ts'), inicio: path.join(WP, 'fuentes/inicio-wp.ts') },
  outdir: path.join(TEMA, 'assets/js'),
  bundle: true, minify: true, format: 'iife', nodePaths: [path.join(WEB, 'node_modules')], target: ['es2020'], logLevel: 'warning',
});

for (const f of ['assets/css/vinea.css', 'assets/js/sitio.js', 'assets/js/inicio.js']) {
  console.log(f, (fs.statSync(path.join(TEMA, f)).size / 1024).toFixed(1) + ' KB');
}
