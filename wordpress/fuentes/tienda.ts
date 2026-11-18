// Carrito de entradas (en el navegador). Los precios que cuentan son los del servidor:
// aquí solo se muestran. Clave: «slug:índice» → cantidad.
type Item = { evento: string; nombre: string; precio: number; max: number; url: string; img: string; fecha: string };
type Datos = { carrito: string; pago: string; entradas: string; catalogo: Record<string, Item> };

const T = (window as unknown as { VINEA_TIENDA?: Datos }).VINEA_TIENDA;
const CLAVE = 'vinea-carrito';
const euros = (n: number) => { const [e, d] = n.toFixed(2).split('.'); return e.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + d + ' €'; };
const esc = (s: string) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]!);

function leer(): Record<string, number> {
  try {
    const c = JSON.parse(localStorage.getItem(CLAVE) || '{}');
    const o: Record<string, number> = {};
    for (const k in c) if (T?.catalogo[k] && c[k] > 0) o[k] = Math.min(Math.floor(c[k]), T.catalogo[k].max);
    return o;
  } catch { return {}; }
}
function guardar(c: Record<string, number>) {
  try { localStorage.setItem(CLAVE, JSON.stringify(c)); } catch { /* sin almacenamiento */ }
  pintarTodo();
}
function totales(c: Record<string, number>) {
  let n = 0, t = 0;
  for (const k in c) { n += c[k]; t += c[k] * T!.catalogo[k].precio; }
  return { n, t };
}

// ---------- Botón de la cabecera
function pintarBoton() {
  const { n, t } = totales(leer());
  document.querySelectorAll<HTMLElement>('[data-carrito-boton]').forEach((b) => {
    b.querySelector('[data-carrito-total]')!.textContent = euros(t);
    const nn = b.querySelector('[data-carrito-n]')!;
    if (nn.textContent !== String(n)) { nn.textContent = String(n); b.classList.remove('carrito-btn--pulso'); void b.offsetWidth; if (n) b.classList.add('carrito-btn--pulso'); }
    b.classList.toggle('carrito-btn--lleno', n > 0);
    b.setAttribute('aria-label', `Carrito: ${n} ${n === 1 ? 'entrada' : 'entradas'}, ${euros(t)}`);
  });
}

// ---------- Filas de producto (panel, carrito, resumen)
function filas(c: Record<string, number>, editable: boolean) {
  return Object.keys(c).map((k) => {
    const it = T!.catalogo[k];
    return `<li class="linea" data-linea="${esc(k)}">
      <a class="linea__foto" href="${esc(it.url)}" tabindex="-1" aria-hidden="true">${it.img ? `<img src="${esc(it.img)}" alt="" width="72" height="72" loading="lazy">` : ''}</a>
      <div class="linea__texto">
        <a class="linea__evento" href="${esc(it.url)}">${esc(it.evento)}</a>
        <p class="linea__nombre">${esc(it.nombre)}</p>
        ${it.fecha ? `<p class="linea__fecha">${esc(it.fecha)}</p>` : ''}
        ${editable ? `<div class="cantidad cantidad--peq"><button type="button" data-l-menos aria-label="Quitar una">−</button><input type="number" min="1" max="${it.max}" value="${c[k]}" aria-label="Cantidad de ${esc(it.nombre)}" data-l-num><button type="button" data-l-mas aria-label="Añadir una">+</button></div>` : `<p class="linea__cant">× ${c[k]}</p>`}
      </div>
      <div class="linea__fin">
        <strong class="linea__precio">${euros(c[k] * it.precio)}</strong>
        ${editable ? `<button class="linea__quitar" type="button" data-l-quitar aria-label="Eliminar ${esc(it.nombre)} del carrito">Eliminar</button>` : ''}
      </div>
    </li>`;
  }).join('');
}

function enlazarFilas(raiz: HTMLElement) {
  raiz.querySelectorAll<HTMLElement>('[data-linea]').forEach((li) => {
    const k = li.dataset.linea!;
    const poner = (v: number) => { const c = leer(); const m = T!.catalogo[k].max; if (v <= 0) delete c[k]; else c[k] = Math.min(v, m); guardar(c); };
    li.querySelector('[data-l-menos]')?.addEventListener('click', () => poner((leer()[k] || 0) - 1));
    li.querySelector('[data-l-mas]')?.addEventListener('click', () => poner((leer()[k] || 0) + 1));
    li.querySelector<HTMLInputElement>('[data-l-num]')?.addEventListener('change', (e) => poner(parseInt((e.target as HTMLInputElement).value, 10) || 0));
    li.querySelector('[data-l-quitar]')?.addEventListener('click', () => poner(0));
  });
}

// ---------- Panel lateral
const panel = document.querySelector<HTMLElement>('[data-panel-carrito]');
let antes: HTMLElement | null = null;
function pintarPanel() {
  if (!panel) return;
  const c = leer();
  const lista = panel.querySelector<HTMLElement>('[data-panel-lista]')!;
  lista.innerHTML = Object.keys(c).length ? `<ul class="lineas" role="list">${filas(c, true)}</ul>` : '<p class="carrito-vacio__texto">Tu carrito está vacío.</p>';
  enlazarFilas(lista);
  panel.querySelector('[data-panel-total]')!.textContent = euros(totales(c).t);
}
function abrirPanel() {
  if (!panel) return;
  antes = document.activeElement as HTMLElement;
  pintarPanel();
  panel.hidden = false;
  requestAnimationFrame(() => requestAnimationFrame(() => panel.classList.add('panel-carrito--abierto')));
  panel.querySelector<HTMLElement>('.panel-carrito__caja')!.focus();
  document.documentElement.classList.add('con-panel');
}
function cerrarPanel() {
  if (!panel || panel.hidden) return;
  panel.classList.remove('panel-carrito--abierto');
  document.documentElement.classList.remove('con-panel');
  setTimeout(() => { panel.hidden = true; }, 380);
  antes?.focus();
}
panel?.querySelectorAll('[data-panel-cerrar]').forEach((b) => b.addEventListener('click', cerrarPanel));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarPanel(); });

// ---------- Caja de tickets del evento
document.querySelectorAll<HTMLFormElement>('[data-tickets]').forEach((f) => {
  const tickets = [...f.querySelectorAll<HTMLElement>('[data-ticket]')].filter((t) => t.querySelector('[data-num]'));
  const boton = f.querySelector<HTMLButtonElement>('[data-comprar]');
  const aviso = f.querySelector<HTMLElement>('[data-aviso]');
  const recalcular = () => {
    let n = 0, tot = 0;
    tickets.forEach((t) => {
      const inp = t.querySelector<HTMLInputElement>('[data-num]')!;
      const max = parseInt(inp.max, 10);
      let v = Math.max(0, Math.min(max, parseInt(inp.value, 10) || 0));
      inp.value = String(v);
      const p = parseFloat(t.dataset.precio || '0');
      t.querySelector('[data-sub]')!.textContent = euros(v * p);
      t.classList.toggle('ticket--elegido', v > 0);
      t.querySelector<HTMLButtonElement>('[data-menos]')!.disabled = v <= 0;
      t.querySelector<HTMLButtonElement>('[data-mas]')!.disabled = v >= max;
      n += v; tot += v * p;
    });
    f.querySelector('[data-cant-total]')!.textContent = String(n);
    f.querySelector('[data-total]')!.textContent = euros(tot);
    if (boton) boton.disabled = n === 0;
  };
  tickets.forEach((t) => {
    const inp = t.querySelector<HTMLInputElement>('[data-num]')!;
    t.querySelector('[data-menos]')!.addEventListener('click', () => { inp.value = String((parseInt(inp.value, 10) || 0) - 1); recalcular(); });
    t.querySelector('[data-mas]')!.addEventListener('click', () => { inp.value = String((parseInt(inp.value, 10) || 0) + 1); recalcular(); });
    inp.addEventListener('input', recalcular);
  });
  recalcular();
  f.addEventListener('submit', (e) => {
    e.preventDefault();
    const c = leer();
    let añadidas = 0;
    tickets.forEach((t) => {
      const k = t.dataset.ticket!;
      const v = parseInt(t.querySelector<HTMLInputElement>('[data-num]')!.value, 10) || 0;
      if (v > 0 && T?.catalogo[k]) { c[k] = Math.min((c[k] || 0) + v, T.catalogo[k].max); añadidas += v; }
      t.querySelector<HTMLInputElement>('[data-num]')!.value = '0';
    });
    if (!añadidas) return;
    guardar(c);
    recalcular();
    if (aviso) aviso.textContent = `${añadidas} ${añadidas === 1 ? 'entrada añadida' : 'entradas añadidas'} al carrito.`;
    abrirPanel();
  });
});

// ---------- Página del carrito
const pagCarrito = document.querySelector<HTMLElement>('[data-pagina-carrito]');
function pintarCarrito() {
  if (!pagCarrito) return;
  const c = leer();
  const lleno = Object.keys(c).length > 0;
  pagCarrito.querySelector<HTMLElement>('[data-carrito-lleno]')!.hidden = !lleno;
  pagCarrito.querySelector<HTMLElement>('[data-carrito-vacio]')!.hidden = lleno;
  if (!lleno) return;
  const lista = pagCarrito.querySelector<HTMLElement>('[data-carrito-lista]')!;
  lista.innerHTML = filas(c, true);
  enlazarFilas(lista);
  const { n, t } = totales(c);
  pagCarrito.querySelectorAll('[data-carrito-suma]').forEach((e) => (e.textContent = euros(t)));
  pagCarrito.querySelector('[data-carrito-cuenta]')!.textContent = `${n} ${n === 1 ? 'entrada' : 'entradas'}`;
}

// ---------- Página de pago
const pagPago = document.querySelector<HTMLElement>('[data-pagina-pago]');
function pintarPago() {
  if (!pagPago) return;
  const c = leer();
  const lleno = Object.keys(c).length > 0;
  pagPago.querySelector<HTMLElement>('[data-pago-lleno]')!.hidden = !lleno;
  pagPago.querySelector<HTMLElement>('[data-carrito-vacio]')!.hidden = lleno;
  if (!lleno) return;
  pagPago.querySelector<HTMLElement>('[data-pago-lista]')!.innerHTML = filas(c, false);
  pagPago.querySelectorAll('[data-carrito-suma]').forEach((e) => (e.textContent = euros(totales(c).t)));
  pagPago.querySelector<HTMLInputElement>('[data-pago-carrito]')!.value = JSON.stringify(c);
}
// Pedido hecho: se vacía el carrito.
if (document.querySelector('[data-pedido-ok]')) { try { localStorage.removeItem(CLAVE); } catch { /* nada */ } }

function pintarTodo() {
  pintarBoton();
  if (panel && !panel.hidden) pintarPanel();
  pintarCarrito();
  pintarPago();
}
pintarTodo();
// Otra pestaña cambió el carrito
window.addEventListener('storage', (e) => { if (e.key === CLAVE) pintarTodo(); });
