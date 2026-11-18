/**
 * Portada: coreografía con el scroll.
 * 1. La gota 3D del logo flota a la derecha del titular.
 * 2. Al bajar, el titular se va, la gota viaja al centro y gira.
 * 3. La gota se convierte en una ventana (máscara) que se abre sobre la foto del Liceu.
 * 4. Los servicios pasan en horizontal; el collage de "momentos" tiene profundidad.
 * Todo se desactiva con "movimiento reducido".
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { crearGota, type EstadoGota } from './gota3d';

gsap.registerPlugin(ScrollTrigger);

const html = document.documentElement;
const movimiento = html.classList.contains('movimiento');
const lenis = (window as any).__lenis;
if (lenis) lenis.on('scroll', ScrollTrigger.update);

const clamp = (v: number, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const lerp = (a: number, b: number, t: number) => a + (b - a) * t;
const suave = (t: number) => t * t * (3 - 2 * t);
const entra = (t: number) => t * t * t;

/* ================= HERO ================= */
const hero = document.querySelector<HTMLElement>('[data-hero]');
const canvas = document.querySelector<HTMLCanvasElement>('[data-gota]');
const portal = document.querySelector<HTMLElement>('[data-portal]');
const portalFoto = document.querySelector<HTMLElement>('[data-portal-foto]');
const portalTexto = document.querySelector<HTMLElement>('[data-portal-texto]');
const textoHero = document.querySelector<HTMLElement>('[data-hero-texto]');
const bajar = document.querySelector<HTMLElement>('[data-hero-bajar]');

// Medidas de la escena: a z=0 la mitad de la altura visible son 1,406 unidades
const MITAD = 4.5 / 1.6 / 2;
const PX_POR_UNIDAD = () => (hero?.clientHeight ?? innerHeight) / (2 * MITAD);

const metrica = () => {
  const w = hero?.clientWidth ?? innerWidth;
  const h = hero?.clientHeight ?? innerHeight;
  const aspecto = w / h;
  const movil = aspecto < 0.9;
  return {
    w, h, movil,
    esc0: movil ? MITAD * 0.22 : Math.min(MITAD * 0.46, MITAD * aspecto * 0.24),
    ox0: movil ? MITAD * aspecto * 0.3 : MITAD * aspecto * 0.5,
    oy0: movil ? MITAD * 0.6 : 0.02,
    esc1: movil ? MITAD * 0.44 : MITAD * 0.5,
  };
};

const estado: EstadoGota = { rx: -0.1, ry: 0.35, rz: 0.06, escala: 0.6, ox: 1, oy: 0, ondula: 1, opacidad: 1 };
const reposo = () => {
  const m = metrica();
  estado.escala = m.esc0; estado.ox = m.ox0; estado.oy = m.oy0;
};
reposo();

/* El nombre «vinea» acompaña a la gota (como en el logo, con las letras sobre su parte baja) */
const nombre = document.querySelector<HTMLElement>('[data-hero-nombre]');
const letrasNombre = [...(nombre?.querySelectorAll<SVGGElement>('.logo__letra') ?? [])];
const colocarNombre = (p = 0) => {
  if (!nombre || !hero) return;
  const px = PX_POR_UNIDAD();
  const w = hero.clientWidth, h = hero.clientHeight;
  const R = estado.escala * px;
  const cx = w / 2 + estado.ox * px, cy = h / 2 - estado.oy * px;
  const ancho = R * 2.7;
  nombre.style.width = `${ancho}px`;
  nombre.style.transform = `translate3d(${(cx - ancho * 0.5).toFixed(1)}px, ${(cy + R * 0.98 - ancho * 336 / 1100).toFixed(1)}px, 0)`;
  letrasNombre.forEach((l, k) => {
    const t = suave(clamp((p - k * 0.018) / 0.2));
    l.style.opacity = String(1 - t);
    l.style.transform = t ? `translate(${(k - 2) * 22 * t}%, ${-60 * t}%) rotate(${(k - 2) * 8 * t}deg)` : '';
  });
  nombre.classList.add('listo');
};
colocarNombre();

// La gota 3D se inicia cuando el navegador está libre, para no retrasar la carga
const iniciarGota = () => {
  if (!canvas) return;
  const gota = crearGota(canvas, estado);
  if (!gota) return; // sin WebGL: se queda la gota plana en CSS
  html.classList.add('gota-lista');
  canvas.style.opacity = '1';
  if (movimiento) gota.arrancar();
  else gota.fotograma();
};
// Se arranca con la primera interacción (o a los pocos segundos en escritorio):
// así la carga inicial queda libre y la gota plana en CSS ocupa su lugar mientras tanto.
let gotaIniciada = false;
const eventosInicio = ['pointermove', 'pointerdown', 'touchstart', 'wheel', 'keydown', 'scroll'] as const;
const lanzar = () => {
  if (gotaIniciada) return;
  gotaIniciada = true;
  eventosInicio.forEach((ev) => window.removeEventListener(ev, lanzar));
  const idle = (window as any).requestIdleCallback ?? ((f: () => void) => setTimeout(f, 50));
  idle(iniciarGota, { timeout: 600 });
};
eventosInicio.forEach((ev) => window.addEventListener(ev, lanzar, { passive: true, once: true }));
if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) setTimeout(lanzar, 4500);
if (!movimiento) lanzar();
if (!movimiento) window.addEventListener('resize', () => { reposo(); colocarNombre(0); });

if (movimiento && hero && portal) {
  const prog = { p: 0 };

  const aplicar = () => {
    const p = prog.p;
    const m = metrica();
    // Fase 1 (0 → 0,45): el texto se va y la gota viaja al centro girando
    const a = suave(clamp(p / 0.45));
    estado.ox = lerp(m.ox0, 0, a);
    estado.oy = lerp(m.oy0, 0, a);
    estado.escala = lerp(m.esc0, m.esc1, a);
    estado.ry = lerp(0.35, Math.PI * 2, a);
    estado.rx = lerp(-0.1, 0, a);
    estado.rz = lerp(0.06, 0, a);
    estado.ondula = 1 - a;
    colocarNombre(p);

    if (textoHero) {
      const t = clamp(p / 0.28);
      textoHero.style.opacity = String(1 - t);
      textoHero.style.transform = `translate3d(0, ${-t * 120}px, 0)`;
      textoHero.style.visibility = t >= 1 ? 'hidden' : '';
    }
    if (bajar) bajar.style.opacity = String(1 - clamp(p / 0.08));

    // Fase 2 (0,44 → 0,88): la gota se convierte en ventana y se abre
    const b = clamp((p - 0.44) / 0.44);
    const inicio = 2 * m.esc1 * PX_POR_UNIDAD();
    const fin = Math.hypot(m.w, m.h) * 1.25;
    portal.style.visibility = p > 0.43 ? 'visible' : 'hidden';
    portal.style.setProperty('--m', `${lerp(inicio, fin, entra(b))}px`);
    estado.opacidad = 1 - clamp((p - 0.45) / 0.05);
    if (portalFoto) portalFoto.style.transform = `scale(${lerp(1.35, 1, suave(b))})`;

    // Fase 3 (0,84 → 1): aparece el texto sobre la foto
    if (portalTexto) {
      const c = suave(clamp((p - 0.84) / 0.14));
      portalTexto.style.opacity = String(c);
      portalTexto.style.transform = `translate3d(0, ${(1 - c) * 50}px, 0)`;
    }
  };

  gsap.to(prog, {
    p: 1,
    ease: 'none',
    onUpdate: aplicar,
    scrollTrigger: {
      trigger: hero,
      start: 'top top',
      end: () => `+=${innerHeight * 1.8}`,
      pin: true,
      scrub: 0.7,
      invalidateOnRefresh: true,
      onRefresh: aplicar,
    },
  });
  aplicar();
  window.addEventListener('resize', () => { if (prog.p === 0) { reposo(); colocarNombre(0); } });
}

/* ================= SERVICIOS EN HORIZONTAL ================= */
const seccionServicios = document.querySelector<HTMLElement>('[data-servicios]');
const pista = document.querySelector<HTMLElement>('[data-pista]');
if (movimiento && seccionServicios && pista) {
  const mm = gsap.matchMedia();
  mm.add('(min-width: 900px) and (min-height: 600px)', () => {
    html.classList.add('pista-horizontal');
    ScrollTrigger.refresh();
    const distancia = () => Math.max(0, pista.scrollWidth - innerWidth);
    const horizontal = gsap.to(pista, {
      x: () => -distancia(),
      ease: 'none',
      scrollTrigger: {
        trigger: seccionServicios,
        start: 'top top',
        end: () => `+=${distancia()}`,
        pin: true,
        scrub: 0.6,
        invalidateOnRefresh: true,
      },
    });
    // Cada tarjeta entra girando en 3D
    pista.querySelectorAll<HTMLElement>('[data-servicio]').forEach((tarjeta) => {
      gsap.fromTo(
        tarjeta,
        { rotateY: -24, z: -120, transformPerspective: 1100 },
        {
          rotateY: 0, z: 0, ease: 'none',
          scrollTrigger: { trigger: tarjeta, containerAnimation: horizontal, start: 'left 105%', end: 'left 55%', scrub: true },
        },
      );
    });
    return () => html.classList.remove('pista-horizontal');
  });
}

/* ================= PROFUNDIDAD (parallax) ================= */
if (movimiento) {
  document.querySelectorAll<HTMLElement>('[data-parallax]').forEach((el) => {
    const v = parseFloat(el.dataset.parallax ?? '0');
    gsap.fromTo(el, { yPercent: v * 60 }, {
      yPercent: -v * 60,
      ease: 'none',
      scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true },
    });
  });
}

// Recalcula tras cargar fuentes e imágenes
window.addEventListener('load', () => ScrollTrigger.refresh());
document.fonts?.ready.then(() => ScrollTrigger.refresh());
