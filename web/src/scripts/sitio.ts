/**
 * Comportamiento común a todas las páginas. Ligero (sin GSAP):
 * desplazamiento suave, apariciones al hacer scroll, cabecera, menú móvil,
 * contadores, inclinación 3D de tarjetas y el interruptor "Reducir animaciones".
 */
import Lenis from 'lenis';

const html = document.documentElement;
const conMovimiento = () => html.classList.contains('movimiento');

/* ---------- Desplazamiento suave ---------- */
let lenis: Lenis | null = null;
if (conMovimiento()) {
  lenis = new Lenis({ autoRaf: true, lerp: 0.11, anchors: { offset: -90 } });
  (window as any).__lenis = lenis;
}

/* ---------- Texto que entra por palabras ---------- */
document.querySelectorAll<HTMLElement>('[data-split]').forEach((el) => {
  let i = 0;
  const partir = (nodo: Node) => {
    if (nodo.nodeType === Node.TEXT_NODE) {
      const frag = document.createDocumentFragment();
      (nodo.textContent ?? '').split(/(\s+)/).forEach((trozo) => {
        if (!trozo) return;
        if (/^\s+$/.test(trozo)) { frag.append(document.createTextNode(' ')); return; }
        const p = document.createElement('span');
        p.className = 'p';
        const s = document.createElement('span');
        s.textContent = trozo;
        s.style.setProperty('--i', String(i++));
        p.append(s);
        frag.append(p);
      });
      nodo.parentNode?.replaceChild(frag, nodo);
    } else if (nodo.nodeType === Node.ELEMENT_NODE && !(nodo as HTMLElement).matches('br')) {
      [...nodo.childNodes].forEach(partir);
    }
  };
  if (!el.getAttribute('aria-label')) el.setAttribute('aria-label', el.textContent?.trim().replace(/\s+/g, ' ') ?? '');
  [...el.childNodes].forEach(partir);
  el.querySelectorAll('.p').forEach((p) => p.setAttribute('aria-hidden', 'true'));
});

/* ---------- Apariciones ---------- */
const observador = new IntersectionObserver(
  (entradas) => {
    entradas.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        observador.unobserve(e.target);
      }
    });
  },
  { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
);
document.querySelectorAll('[data-reveal], [data-split]').forEach((el) => {
  if (el.closest('[data-reveal-manual]')) return;
  observador.observe(el);
});

/* ---------- Contadores ---------- */
const contador = new IntersectionObserver(
  (entradas) => {
    entradas.forEach((e) => {
      if (!e.isIntersecting) return;
      contador.unobserve(e.target);
      const el = e.target as HTMLElement;
      const final = el.dataset.count ?? el.textContent ?? '';
      const m = final.match(/^(\D*)([\d.,]+)(.*)$/);
      if (!m || !conMovimiento()) return;
      const [, antes, num, despues] = m;
      const objetivo = parseFloat(num.replace(/\./g, '').replace(',', '.'));
      const usaPunto = num.includes('.');
      const t0 = performance.now();
      const dur = 1600;
      const paso = (t: number) => {
        const k = Math.min(1, (t - t0) / dur);
        const v = Math.round(objetivo * (1 - Math.pow(1 - k, 4)));
        el.textContent = antes + (usaPunto ? v.toLocaleString('es-ES') : String(v)) + despues;
        if (k < 1) requestAnimationFrame(paso);
        else el.textContent = final;
      };
      requestAnimationFrame(paso);
    });
  },
  { threshold: 0.6 },
);
document.querySelectorAll('[data-count]').forEach((el) => contador.observe(el));

/* ---------- Cabecera: sólida al bajar, se esconde al bajar y aparece al subir ---------- */
const cabecera = document.querySelector<HTMLElement>('[data-cabecera]');
let ultimaY = window.scrollY;
const alDesplazar = () => {
  const y = window.scrollY;
  if (!cabecera) return;
  cabecera.classList.toggle('solida', y > 40);
  if (!html.classList.contains('menu-abierto')) cabecera.classList.toggle('oculta', y > 400 && y > ultimaY + 2);
  if (y < ultimaY - 2) cabecera.classList.remove('oculta');
  ultimaY = y;
};
window.addEventListener('scroll', alDesplazar, { passive: true });
alDesplazar();

/* ---------- Menú móvil ---------- */
const boton = document.querySelector<HTMLButtonElement>('[data-menu-boton]');
const textoBoton = document.querySelector<HTMLElement>('[data-menu-texto]');
const cerrarMenu = () => {
  html.classList.remove('menu-abierto');
  boton?.setAttribute('aria-expanded', 'false');
  if (textoBoton) textoBoton.textContent = 'Abrir menú';
  lenis?.start();
};
boton?.addEventListener('click', () => {
  const abrir = boton.getAttribute('aria-expanded') !== 'true';
  if (!abrir) return cerrarMenu();
  html.classList.add('menu-abierto');
  boton.setAttribute('aria-expanded', 'true');
  if (textoBoton) textoBoton.textContent = 'Cerrar menú';
  lenis?.stop();
  document.querySelector<HTMLAnchorElement>('[data-menu] a')?.focus();
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && html.classList.contains('menu-abierto')) {
    cerrarMenu();
    boton?.focus();
  }
});
document.querySelectorAll('[data-menu] a').forEach((a) => a.addEventListener('click', cerrarMenu));

/* ---------- Inclinación 3D en tarjetas (solo ratón) ---------- */
if (conMovimiento() && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
  document.querySelectorAll<HTMLElement>('[data-tilt]').forEach((el) => {
    let raf = 0;
    el.addEventListener('pointermove', (e) => {
      const r = el.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      const y = (e.clientY - r.top) / r.height - 0.5;
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        el.style.setProperty('--ry', `${x * 10}deg`);
        el.style.setProperty('--rx', `${-y * 10}deg`);
        el.style.setProperty('--mx', `${(x + 0.5) * 100}%`);
        el.style.setProperty('--my', `${(y + 0.5) * 100}%`);
      });
    });
    el.addEventListener('pointerleave', () => {
      el.style.setProperty('--ry', '0deg');
      el.style.setProperty('--rx', '0deg');
    });
  });
}

/* ---------- Interruptor "Reducir animaciones" ---------- */
const interruptor = document.querySelector<HTMLButtonElement>('[data-movimiento]');
if (interruptor) {
  interruptor.setAttribute('aria-pressed', String(html.classList.contains('sin-movimiento')));
  interruptor.addEventListener('click', () => {
    const reducir = !html.classList.contains('sin-movimiento');
    try { localStorage.setItem('vinea-movimiento', reducir ? 'off' : 'on'); } catch (e) {}
    location.reload();
  });
}

/* ---------- Enlaces externos de Instagram, etc. ya marcados en HTML ---------- */
export {};
