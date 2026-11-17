/**
 * Copia a src/assets/img/ solo las imágenes que usa la web (las que aparecen
 * en src/content y src/pages), desde la carpeta de material extraído.
 * Las reduce a 2400 px como máximo para que la compilación sea rápida.
 *
 * Uso: node scripts/sincronizar-imagenes.mjs [carpeta-origen]
 */
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const raiz = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const origen = path.resolve(process.argv[2] ?? path.join(raiz, '../investigacion/web-antigua/imagenes'));
const destino = path.join(raiz, 'src/assets/img');
fs.mkdirSync(destino, { recursive: true });

const leer = (dir) =>
  fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) return leer(p);
    return /\.(md|json|astro)$/.test(e.name) ? [fs.readFileSync(p, 'utf8')] : [];
  });

const textos = [...leer(path.join(raiz, 'src/content')), ...leer(path.join(raiz, 'src/pages'))].join('\n');
const usadas = new Set([...textos.matchAll(/["'\s:]([\w.()%,+@-]+\.(?:jpe?g|png|webp))\b/gi)].map((m) => m[1]));

let copiadas = 0, faltan = [];
for (const nombre of usadas) {
  const src = path.join(origen, nombre);
  const dst = path.join(destino, nombre);
  if (!fs.existsSync(src)) { if (!fs.existsSync(dst)) faltan.push(nombre); continue; }
  if (fs.existsSync(dst)) continue;
  const img = sharp(src, { failOn: 'none' }).rotate();
  const meta = await img.metadata();
  if ((meta.width ?? 0) > 2400 || (meta.height ?? 0) > 2400) {
    await img.resize({ width: 2400, height: 2400, fit: 'inside' }).toFile(dst);
  } else {
    fs.copyFileSync(src, dst);
  }
  copiadas++;
}
// Quita imágenes que ya no se usan
let borradas = 0;
for (const f of fs.readdirSync(destino)) if (!usadas.has(f)) { fs.unlinkSync(path.join(destino, f)); borradas++; }

const mb = fs.readdirSync(destino).reduce((s, f) => s + fs.statSync(path.join(destino, f)).size, 0) / 1e6;
console.log(`Imágenes usadas: ${usadas.size} · copiadas ahora: ${copiadas} · borradas: ${borradas} · total ${mb.toFixed(1)} MB`);
if (faltan.length) console.log('No encontradas:', faltan.join(', '));
