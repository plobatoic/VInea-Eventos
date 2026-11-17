// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = path.dirname(fileURLToPath(import.meta.url));

/**
 * Genera las redirecciones 301 de las URL de la web antigua a las nuevas,
 * leyendo el campo `urlAntigua` de cada archivo de contenido.
 * Escribe `_redirects` (Netlify), `vercel.json` (Vercel) y `.htaccess` (Apache / Hostinger).
 */
function redirecciones() {
  const colecciones = {
    servicios: (slug) => `/servicios/${slug}/`,
    proyectos: (slug) => `/proyectos/${slug}/`,
    paginas: (slug) => mapaPaginas[slug] ?? `/${slug}/`,
  };
  const mapaPaginas = {
    esencia: '/nuestra-esencia/',
    'accesibilidad-inclusion': '/accesibilidad-e-inclusion/',
    'aviso-legal': '/aviso-legal/',
    privacidad: '/politica-privacidad/',
    cookies: '/politica-cookies/',
    'accesibilidad-web': '/accesibilidad-web/',
  };
  // Redirecciones fijas que no salen de un archivo de contenido
  const fijas = [
    ['/blog/', '/proyectos/'],
    ['/articulo/', '/proyectos/'],
    ['/eventos-2/', '/eventos/'],
    ['/nuestra-esencia', '/nuestra-esencia/'],
    ['/descubre-esencia-vinea-eventos/', '/nuestra-esencia/'],
    ['/accesibilidad-web-vinea-eventos/', '/accesibilidad-web/'],
    ['/accesibilidad-web-real-decreto-1112-2018/', '/accesibilidad-web/'],
    ['/servicios/ferias-y-congresos/', '/servicios/congresos/'],
    ['/servicios/congresos-2-2/', '/servicios/congresos/'],
    ['/servicios/proveedor-de-freelance/', '/servicios/personal-eventos/'],
    ['/servicios', '/servicios/'],
  ];
  return {
    name: 'vinea-redirecciones',
    hooks: {
      'astro:build:done': ({ dir }) => {
        const pares = [...fijas];
        for (const [col, destino] of Object.entries(colecciones)) {
          const carpeta = path.join(raiz, 'src/content', col);
          if (!fs.existsSync(carpeta)) continue;
          for (const f of fs.readdirSync(carpeta).filter((f) => f.endsWith('.md'))) {
            const txt = fs.readFileSync(path.join(carpeta, f), 'utf8');
            const nueva = destino(f.replace(/\.md$/, ''));
            const antiguas = [];
            const m = txt.match(/^urlAntigua:\s*["']?([^"'\n]+)["']?\s*$/m);
            if (m) antiguas.push(m[1].trim());
            // Lista `urlsAntiguas` (contenidos fusionados), en formato [a, b] o en líneas "- a"
            const bloque = txt.match(/^urlsAntiguas:\s*(\[[^\]]*\]|\n(?:\s+-\s*.+\n?)+)/m);
            if (bloque) antiguas.push(...[...bloque[1].matchAll(/["']?(\/[^"',\]\s]+)["']?/g)].map((x) => x[1]));
            for (const a of antiguas) if (a !== nueva) pares.push([a, nueva]);
          }
        }
        // Fuera las parejas que solo difieren en la barra final (el servidor ya lo resuelve y causarían bucles)
        const sinBarra = (u) => u.replace(/\/+$/, '');
        const unicos = [...new Map(pares.filter(([a, b]) => sinBarra(a) !== sinBarra(b)).map((p) => [p[0], p])).values()];
        const re = (u) => '^' + sinBarra(u).replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '/?$';
        const out = fileURLToPath(dir);
        fs.writeFileSync(path.join(out, '_redirects'), unicos.map(([a, b]) => `${a} ${b} 301`).join('\n') + '\n');
        fs.writeFileSync(
          path.join(out, 'vercel.json'),
          JSON.stringify({ trailingSlash: true, redirects: unicos.map(([a, b]) => ({ source: a, destination: b, permanent: true })) }, null, 2),
        );
        const ht = [
          '# Redirecciones 301 desde la web antigua (generado automáticamente)',
          'RewriteEngine On',
          // RedirectMatch con ^...$ para que cada regla afecte solo a esa URL exacta (no a las que empiezan igual)
          ...unicos.map(([a, b]) => `RedirectMatch 301 ${re(a)} ${b}`),
          '',
          'ErrorDocument 404 /404.html',
          '<IfModule mod_expires.c>',
          '  ExpiresActive On',
          '  ExpiresByType image/webp "access plus 1 year"',
          '  ExpiresByType image/jpeg "access plus 1 year"',
          '  ExpiresByType image/png "access plus 1 year"',
          '  ExpiresByType image/svg+xml "access plus 1 year"',
          '  ExpiresByType font/woff2 "access plus 1 year"',
          '  ExpiresByType text/css "access plus 1 year"',
          '  ExpiresByType application/javascript "access plus 1 year"',
          '</IfModule>',
        ];
        fs.writeFileSync(path.join(out, '.htaccess'), ht.join('\n') + '\n');
        console.log(`[vinea] ${unicos.length} redirecciones 301 generadas`);
      },
    },
  };
}

export default defineConfig({
  site: 'https://vineaeventos.com',
  trailingSlash: 'always',
  build: { format: 'directory', inlineStylesheets: 'auto' },
  prefetch: { prefetchAll: false, defaultStrategy: 'hover' },
  image: { responsiveStyles: false },
  integrations: [
    sitemap({ filter: (page) => !page.includes('/gracias/') }),
    redirecciones(),
  ],
});
