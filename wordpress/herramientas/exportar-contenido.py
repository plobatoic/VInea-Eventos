"""
Exporta el contenido ORIGINAL de vineaeventos.com (API REST de WordPress) a
importar/contenido.json, palabra por palabra. Solo se elimina la maquetación de
Elementor; se conservan títulos, párrafos, listas, imágenes, botones y enlaces
en su orden original. Uso: python3 -I exportar-contenido.py <carpeta-json-crudo> <salida.json>
"""
import json, re, sys, html, urllib.request
from bs4 import BeautifulSoup, NavigableString, Tag

CRUDO, SALIDA = sys.argv[1], sys.argv[2]
DOMINIO = 'https://vineaeventos.com'
EXCLUIR = {'test-vinea', 'test-raul', 'tickets-order', 'tickets-checkout', 'register', 'booking-details',
           'submit-event', 'user-profile', 'venues', 'performers', 'finalizar-compra', 'carrito-compra',
           'congresos-2-2', 'articulo', 'proveedor-de-freelance'}
INLINE = {'a', 'strong', 'b', 'em', 'i', 'br', 'sup', 'sub', 'u', 'small'}
BLOQUE = {'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'blockquote', 'figure', 'img', 'table', 'iframe'}

def url_rel(u):
    if not u: return u
    u = u.strip()
    return u[len(DOMINIO):] or '/' if u.startswith(DOMINIO) else u

def img_src(img):
    for attr in ('data-lazy-src', 'data-src', 'src'):
        v = img.get(attr)
        if v and not v.startswith('data:'): src = v; break
    else:
        return None
    ss = img.get('data-lazy-srcset') or img.get('srcset')
    if ss:
        cand = [c.strip().split(' ') for c in ss.split(',') if c.strip()]
        try:
            cand = sorted(cand, key=lambda c: int(re.sub(r'\D', '', c[1]) or 0) if len(c) > 1 else 0)
            src = cand[-1][0]
        except Exception: pass
    return src

def inline(node):
    """HTML en línea saneado (solo etiquetas de texto)."""
    out = []
    for c in node.children:
        if isinstance(c, NavigableString):
            if c.__class__.__name__ in ('Comment',): continue
            out.append(html.escape(str(c), quote=False))
        elif isinstance(c, Tag):
            if c.name in ('script', 'style', 'svg', 'noscript', 'img', 'i') and not c.get_text(strip=True): continue
            if c.name == 'br': out.append('<br>')
            elif c.name == 'a':
                href = url_rel(c.get('href', ''))
                t = ' target="_blank" rel="noopener"' if c.get('target') == '_blank' else ''
                out.append(f'<a href="{html.escape(href)}"{t}>{inline(c)}</a>')
            elif c.name in INLINE:
                tag = {'b': 'strong', 'i': 'em'}.get(c.name, c.name)
                inner = inline(c)
                if inner.strip(): out.append(f'<{tag}>{inner}</{tag}>')
            else:
                out.append(inline(c))
    return ''.join(out)

def limpio(s): return re.sub(r'\s+', ' ', s).strip()

def figura(img, pie=None):
    src = img_src(img)
    if not src: return None
    alt = html.escape(img.get('alt', '') or '')
    cap = f'<figcaption>{pie}</figcaption>' if pie else ''
    return f'<figure><img src="{html.escape(src)}" alt="{alt}">{cap}</figure>'

def lista(el):
    tag = 'ol' if el.name == 'ol' else 'ul'
    items = [limpio(inline(li)) for li in el.find_all('li', recursive=False) or el.find_all('li')]
    items = [i for i in items if i]
    return f'<{tag}>' + ''.join(f'<li>{i}</li>' for i in items) + f'</{tag}>' if items else None

def bloques_html(raiz):
    """Bloques de un contenedor HTML normal (editor de texto, entradas clásicas)."""
    res = []
    for c in raiz.children:
        if isinstance(c, NavigableString):
            t = limpio(str(c))
            if t: res.append(f'<p>{html.escape(t, quote=False)}</p>')
            continue
        if not isinstance(c, Tag) or c.name in ('script', 'style', 'noscript', 'form', 'svg'): continue
        if c.name in ('p',):
            t = limpio(inline(c))
            if t: res.append(f'<p>{t}</p>')
            for img in c.find_all('img'):
                f = figura(img)
                if f: res.append(f)
        elif re.fullmatch(r'h[1-6]', c.name):
            t = limpio(inline(c))
            if t: res.append(f'<{c.name}>{t}</{c.name}>')
        elif c.name in ('ul', 'ol'):
            l = lista(c)
            if l: res.append(l)
        elif c.name == 'blockquote':
            t = limpio(inline(c))
            if t: res.append(f'<blockquote><p>{t}</p></blockquote>')
        elif c.name in ('figure',):
            img = c.find('img')
            cap = c.find('figcaption')
            if img:
                f = figura(img, limpio(inline(cap)) if cap else None)
                if f: res.append(f)
            elif c.find('iframe'):
                res.append(str(c.find('iframe')))
        elif c.name == 'img':
            f = figura(c)
            if f: res.append(f)
        elif c.name == 'iframe':
            res.append(f'<iframe src="{html.escape(c.get("src", ""))}" loading="lazy" allowfullscreen></iframe>')
        elif c.name == 'table':
            res.append(str(c))
        else:
            res.extend(bloques_html(c))
    return res

def widgets(soup):
    """Widgets de Elementor en orden, sin anidados ni duplicados de móvil."""
    todos = soup.select('.elementor-widget')
    res = []
    for w in todos:
        cls = ' '.join(w.get('class', []))
        if 'elementor-hidden-desktop' in cls: continue
        padre = w.parent
        anidado = False
        while padre is not None:
            if isinstance(padre, Tag) and 'elementor-widget' in padre.get('class', []):
                anidado = True; break
            padre = padre.parent
        if not anidado: res.append(w)
    return res

def tipo_widget(w):
    for c in w.get('class', []):
        if c.startswith('elementor-widget-') and c not in ('elementor-widget-container', 'elementor-widget-wrap', 'elementor-widget-empty'):
            return c[len('elementor-widget-'):]
    return ''

SALTAR = {'nav-menu', 'form', 'social-icons', 'spacer', 'divider', 'icon', 'posts', 'loop-grid', 'theme-site-logo',
          'image-carousel-placeholder', 'shortcode', 'html', 'search-form', 'share-buttons', 'menu-anchor', 'template',
          'post-info', 'theme-post-title', 'theme-post-content', 'theme-post-featured-image', 'post-navigation', 'archive-posts',
          'trustindex', 'reviews', 'facebook-page', 'lottie', 'sitemap'}

def bloques_elementor(soup):
    res = []
    for w in widgets(soup):
        t = tipo_widget(w)
        base = t.split('.')[0]
        if base in SALTAR: continue
        if base == 'heading':
            h = w.find(re.compile(r'^h[1-6]$'))
            if h:
                tx = limpio(inline(h))
                if tx: res.append(f'<{h.name}>{tx}</{h.name}>')
            else:
                el = w.select_one('.elementor-heading-title')
                tx = limpio(inline(el)) if el else ''
                if tx: res.append(f'<p class="destacado-texto">{tx}</p>')
        elif base in ('text-editor', 'theme-post-excerpt'):
            cont = w.select_one('.elementor-widget-container') or w
            res.extend(bloques_html(cont))
        elif base == 'image':
            img = w.find('img')
            cap = w.find('figcaption')
            if img:
                f = figura(img, limpio(inline(cap)) if cap else None)
                if f: res.append(f)
        elif base == 'button':
            a = w.find('a')
            tx = limpio(a.get_text(' ')) if a else ''
            if a and tx: res.append(f'<p class="boton-contenido"><a href="{html.escape(url_rel(a.get("href", "#")))}">{html.escape(tx)}</a></p>')
        elif base == 'icon-list':
            items = []
            for li in w.find_all('li'):
                tx = limpio(li.get_text(' '))
                a = li.find('a')
                if tx: items.append(f'<a href="{html.escape(url_rel(a.get("href")))}">{html.escape(tx)}</a>' if a and a.get('href') else html.escape(tx))
            if items: res.append('<ul>' + ''.join(f'<li>{i}</li>' for i in items) + '</ul>')
        elif base in ('image-box', 'icon-box', 'call-to-action', 'flip-box', 'price-table'):
            img = w.find('img')
            if img:
                f = figura(img)
                if f: res.append(f)
            for el in w.find_all(re.compile(r'^(h[1-6]|p)$')):
                tx = limpio(inline(el))
                if tx: res.append(f'<{el.name}>{tx}</{el.name}>')
            a = w.find('a', class_=re.compile('button'))
            if a and limpio(a.get_text(' ')): res.append(f'<p class="boton-contenido"><a href="{html.escape(url_rel(a.get("href", "#")))}">{html.escape(limpio(a.get_text(" ")))}</a></p>')
        elif base in ('image-gallery', 'gallery', 'image-carousel', 'media-carousel'):
            figs = [figura(i) for i in w.find_all('img')]
            # Galería de Elementor Pro: la foto va como fondo y el título en una capa
            for it in w.select('.e-gallery-item'):
                fondo = it.select_one('.e-gallery-image')
                url = (fondo.get('data-thumbnail') if fondo else None) or it.get('href')
                if not url: continue
                tit = it.select_one('.elementor-gallery-item__title')
                des = it.select_one('.elementor-gallery-item__description')
                t = limpio(tit.get_text(' ')) if tit else ''
                d = limpio(des.get_text(' ')) if des else ''
                pie = html.escape(t) + (f' <span>{html.escape(d)}</span>' if d else '') if (t or d) else ''
                figs.append(f'<figure><img src="{html.escape(url)}" alt="{html.escape(t)}">' + (f'<figcaption>{pie}</figcaption>' if pie else '') + '</figure>')
            figs = [f for f in figs if f]
            # quitar repetidas (los carruseles duplican diapositivas)
            vistas, unicas = set(), []
            for f in figs:
                k = re.search(r'src="([^"]+)"', f).group(1)
                if k not in vistas: vistas.add(k); unicas.append(f)
            if unicas: res.append('<div class="galeria-contenido">' + ''.join(unicas) + '</div>')
        elif base == 'counter':
            num = w.select_one('.elementor-counter-number')
            pre = w.select_one('.elementor-counter-number-prefix')
            suf = w.select_one('.elementor-counter-number-suffix')
            tit = w.select_one('.elementor-counter-title')
            valor = (pre.get_text(strip=True) if pre else '') + (num.get('data-to-value') or num.get_text(strip=True) if num else '') + (suf.get_text(strip=True) if suf else '')
            if valor: res.append(f'<p class="cifra-contenido"><strong>{html.escape(valor)}</strong> {html.escape(limpio(tit.get_text(" ")) if tit else "")}</p>')
        elif base in ('accordion', 'toggle', 'nested-accordion', 'tabs', 'nested-tabs'):
            titulos = w.select('.elementor-tab-title, .e-n-accordion-item-title, summary')
            contenidos = w.select('.elementor-tab-content, .e-n-accordion-item > div, details > div')
            for i, ti in enumerate(titulos):
                tt = limpio(ti.get_text(' '))
                cc = ''.join(bloques_html(contenidos[i])) if i < len(contenidos) else ''
                if tt: res.append(f'<details><summary>{html.escape(tt)}</summary>{cc}</details>')
        elif base in ('video',):
            fr = w.find('iframe')
            settings = w.get('data-settings', '')
            m = re.search(r'"youtube_url":"([^"]+)"', settings)
            if fr and fr.get('src'): res.append(f'<iframe src="{html.escape(fr["src"])}" loading="lazy" allowfullscreen></iframe>')
            elif m: res.append(f'<p class="video-contenido"><a href="{html.escape(m.group(1).replace(chr(92), ""))}">Ver vídeo</a></p>')
        elif base == 'google_maps':
            fr = w.find('iframe')
            if fr: res.append(f'<iframe src="{html.escape(fr.get("src", ""))}" loading="lazy"></iframe>')
        elif base in ('testimonial', 'testimonial-carousel', 'reviews'):
            for el in w.select('.elementor-testimonial-content, .elementor-testimonial__text'):
                tx = limpio(inline(el))
                if tx: res.append(f'<blockquote><p>{tx}</p></blockquote>')
        else:
            cont = w.select_one('.elementor-widget-container') or w
            res.extend(bloques_html(cont))
    return res

def quitar_repetidos(bl):
    vistos, out = set(), []
    for b in bl:
        clave = re.sub(r'<[^>]+>', '', b).strip().lower()
        if len(clave) > 25 and clave in vistos: continue
        if b.startswith('<figure') or b.startswith('<div class="galeria'):
            if b in vistos: continue
            vistos.add(b)
        vistos.add(clave)
        out.append(b)
    return out

def secciones(bl):
    """Agrupa en <section class="bloque"> a partir de cada h2."""
    out, actual, primero = [], [], True
    def cerrar():
        nonlocal actual, primero
        if actual:
            out.append(f'<section class="bloque{" bloque--intro" if primero and not actual[0].startswith("<h2") else ""}">' + ''.join(actual) + '</section>')
        actual = []; primero = False
    for b in bl:
        if b.startswith('<h2') and actual: cerrar()
        actual.append(b)
    cerrar()
    return '\n'.join(out)

def convertir(html_crudo):
    soup = BeautifulSoup(html_crudo, 'html.parser')
    for s in soup(['script', 'style', 'noscript']): s.decompose()
    if soup.select('.elementor-widget'):
        bl = bloques_elementor(soup)
    else:
        bl = bloques_html(soup)
    return secciones(quitar_repetidos(bl))

def texto(h): return limpio(BeautifulSoup(h or '', 'html.parser').get_text(' '))

paginas = json.load(open(f'{CRUDO}/raw-pages-1.json'))
entradas = json.load(open(f'{CRUDO}/raw-posts-1.json'))
cats = {c['id']: c for c in json.load(open(f'{CRUDO}/raw-cats.json'))}
por_id = {p['id']: p for p in paginas}

ids_media = sorted({x['featured_media'] for x in paginas + entradas if x.get('featured_media')})
media = {}
for i in range(0, len(ids_media), 90):
    lote = ','.join(map(str, ids_media[i:i + 90]))
    with urllib.request.urlopen(f'{DOMINIO}/wp-json/wp/v2/media?include={lote}&per_page=100&_fields=id,source_url,alt_text') as r:
        for m in json.load(r): media[m['id']] = {'url': m['source_url'], 'alt': m.get('alt_text', '')}

def seo(x):
    y = x.get('yoast_head_json') or {}
    return {'title': y.get('title', ''), 'description': y.get('description', '') or y.get('og_description', '')}

def comun(x):
    return {
        'id_antiguo': x['id'],
        'slug': x['slug'],
        'titulo': html.unescape(x['title']['rendered']),
        'fecha': x['date'],
        'extracto': texto(x.get('excerpt', {}).get('rendered')),
        'imagen': media.get(x.get('featured_media'), {}),
        'seo': seo(x),
        'contenido': convertir(x['content']['rendered']),
    }

salida = {'origen': DOMINIO, 'paginas': [], 'entradas': [], 'categorias': [{'nombre': html.unescape(c['name']), 'slug': c['slug']} for c in cats.values()]}
for p in sorted(paginas, key=lambda p: (p['parent'] != 0, p['menu_order'], p['id'])):
    if p['slug'] in EXCLUIR: continue
    d = comun(p)
    d['padre'] = por_id[p['parent']]['slug'] if p['parent'] in por_id else ''
    d['orden'] = p.get('menu_order', 0)
    if p['slug'] in ('home', 'blog'): d['contenido'] = ''  # se generan con plantilla propia
    salida['paginas'].append(d)
for e in entradas:
    d = comun(e)
    d['categorias'] = [cats[c]['slug'] for c in e.get('categories', []) if c in cats]
    salida['entradas'].append(d)

json.dump(salida, open(SALIDA, 'w'), ensure_ascii=False, indent=1)
print('páginas', len(salida['paginas']), '· entradas', len(salida['entradas']), '· imágenes destacadas', len(media))
for p in salida['paginas']: print('  ', p['padre'] + '/' if p['padre'] else '', p['slug'], len(p['contenido']), 'car.')
