/**
 * Portada (tema WordPress): entrada de cine y coreografía con el scroll.
 *
 * Al cargar: fondo oscuro con brillo que sigue al ratón y partículas de luz; la gota
 * cae con rebote y las letras de «vinea» suben una a una (CSS).
 * Al bajar (sección fijada):
 *   A) los textos se van;
 *   B) la cámara atraviesa el logo: las letras pasan por delante y la gota 3D viaja al
 *      centro girando y se vuelve de CRISTAL, dejando ver la foto a través;
 *   C) la gota se abre como ventana con esa misma foto (sin corte) y las partículas
 *      estallan; dentro pasan las fotos del montaje;
 *   D) aparece el lema original palabra a palabra.
 * Todo respeta «reducir movimiento».
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { crearGota, type EstadoGota } from './gota-cristal';
import { crearParticulas, type EstadoParticulas } from './particulas';

gsap.registerPlugin(ScrollTrigger);

const html = document.documentElement;
const movimiento = html.classList.contains('movimiento');
const lenis = (window as any).__lenis;
if (lenis) lenis.on('scroll', ScrollTrigger.update);

const clamp = (v: number, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const lerp = (a: number, b: number, t: number) => a + (b - a) * t;
const suave = (t: number) => t * t * (3 - 2 * t);
const entra = (t: number) => t * t * t;

const hero = document.querySelector<HTMLElement>('[data-hero]');
const canvas = document.querySelector<HTMLCanvasElement>('[data-gota]');
const lienzoP = document.querySelector<HTMLCanvasElement>('[data-particulas]');
const plana = document.querySelector<HTMLElement>('[data-gota-plana]');
const logo = document.querySelector<HTMLElement>('[data-hero-logo]');
const svgLogo = logo?.querySelector<SVGSVGElement>('svg');
const letras = [...(logo?.querySelectorAll<SVGGElement>('.logo__letra') ?? [])];
const fades = [...document.querySelectorAll<HTMLElement>('[data-hero-fade]')];
const aura = document.querySelector<HTMLElement>('[data-aura]');
const portal = document.querySelector<HTMLElement>('[data-portal]');
const fotos = [...document.querySelectorAll<HTMLElement>('[data-portal-foto]')];
const portalTexto = document.querySelector<HTMLElement>('[data-portal-texto]');
const bajar = document.querySelector<HTMLElement>('[data-hero-bajar]');

/* ---------- Brillo que sigue al ratón ---------- */
if (aura && movimiento && window.matchMedia('(hover: hover)').matches) {
  let ax = 50, ay = 45, tx = 50, ty = 45, mov = false;
  const paso = () => {
    ax += (tx - ax) * 0.06; ay += (ty - ay) * 0.06;
    aura.style.setProperty('--ax', `${ax}%`); aura.style.setProperty('--ay', `${ay}%`);
    if (Math.abs(tx - ax) > 0.05 || Math.abs(ty - ay) > 0.05) requestAnimationFrame(paso); else mov = false;
  };
  window.addEventListener('pointermove', (e) => {
    tx = (e.clientX / innerWidth) * 100; ty = (e.clientY / innerHeight) * 100;
    if (!mov) { mov = true; requestAnimationFrame(paso); }
  }, { passive: true });
}

/* ---------- Lema palabra a palabra ---------- */
const palabras: HTMLElement[] = [];
portalTexto?.querySelectorAll<HTMLElement>('[data-palabras]').forEach((el) => {
  el.setAttribute('aria-label', el.textContent?.trim() ?? '');
  const ps = (el.textContent ?? '').trim().split(/\s+/);
  el.textContent = '';
  ps.forEach((p, i) => {
    const s = document.createElement('span');
    s.className = 'palabra';
    s.setAttribute('aria-hidden', 'true');
    s.textContent = p;
    el.append(s);
    if (i < ps.length - 1) el.append(' ');
    palabras.push(s);
  });
});

/* ---------- Escena 3D ---------- */
const MITAD = 4.5 / 1.6 / 2;
const GOTA = { x: 11, y: 10, lado: 412, vbw: 1353, vbh: 435 };
const medir = () => {
  const hr = hero!.getBoundingClientRect();
  const sr = svgLogo!.getBoundingClientRect();
  const k = sr.width / GOTA.vbw;
  return {
    W: hr.width, H: hr.height, px: hr.height / (2 * MITAD),
    lado: GOTA.lado * k,
    cx: sr.left - hr.left + (GOTA.x + GOTA.lado / 2) * k,
    cy: sr.top - hr.top + (GOTA.y + GOTA.lado / 2) * k,
    movil: hr.width / hr.height < 0.9,
  };
};
let m0 = hero && svgLogo ? medir() : null;

const estado: EstadoGota = { rx: 0, ry: 0, rz: 0, escala: 0.4, ox: 0, oy: 0, ondula: 0, opacidad: 1, vivo: 0.25, foto: 0 };
const estP: EstadoParticulas = { estallido: 0, opacidad: 1, cx: 0, cy: 0 };
const fotoCristal = fotos[0]?.querySelector('img');

let gota3d = false;
const iniciarGota = () => {
  if (!canvas || !m0) return;
  const g = crearGota(canvas, estado);
  if (!g) return;
  gota3d = true;
  html.classList.add('gota-3d');
  if (plana) plana.style.display = 'none';
  const url = fotoCristal?.currentSrc || fotoCristal?.src;
  if (url) g.cargarFoto(url);
  if (movimiento) g.arrancar(); else g.fotograma();
};

const particulas = lienzoP ? crearParticulas(lienzoP, estP) : null;
if (particulas) { if (movimiento) particulas.arrancar(); else particulas.fotograma(); }

// La gota 3D arranca con la primera interacción (o a los pocos segundos con ratón)
let lanzada = false;
const eventos = ['pointermove', 'pointerdown', 'touchstart', 'wheel', 'keydown', 'scroll'] as const;
const lanzar = () => {
  if (lanzada) return;
  lanzada = true;
  eventos.forEach((ev) => window.removeEventListener(ev, lanzar));
  hero?.classList.add('portal-listo');
  const idle = (window as any).requestIdleCallback ?? ((f: () => void) => setTimeout(f, 50));
  idle(iniciarGota, { timeout: 600 });
};
eventos.forEach((ev) => window.addEventListener(ev, lanzar, { passive: true, once: true }));
if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) setTimeout(lanzar, 3500);
if (!movimiento) lanzar();

if (movimiento && hero && portal && svgLogo && m0) {
  const prog = { p: 0 };

  const colocarPlana = (cx: number, cy: number, lado: number, giro: number, p: number) => {
    if (!plana || gota3d) return;
    const usar = p > 0.003;
    hero.classList.toggle('usa-plana', usar);
    plana.style.display = usar ? 'block' : 'none';
    if (!usar) return;
    plana.style.width = `${m0!.lado}px`;
    plana.style.transform = `translate3d(${cx - lado / 2}px, ${cy - lado / 2}px, 0) scale(${lado / m0!.lado}) rotate(${giro}rad)`;
  };

  const aplicar = () => {
    const p = prog.p;
    const m = m0!;

    // A) los textos se van
    const a = suave(clamp(p / 0.12));
    fades.forEach((el, i) => {
      el.style.opacity = String(1 - a);
      el.style.transform = `translate3d(0, ${a * (40 + i * 20)}px, 0)`;
    });
    if (bajar) bajar.style.opacity = String(1 - clamp(p / 0.05));

    // B) zoom a través del logo
    const bz = clamp((p - 0.02) / 0.44);
    const viaje = suave(bz);
    const cx = lerp(m.cx, m.W / 2, viaje);
    const cy = lerp(m.cy, m.H / 2, viaje);
    const ladoFinal = Math.min(m.H * (m.movil ? 0.46 : 0.62), m.W * 0.82);
    const lado = lerp(m.lado, ladoFinal, entra(bz) * 0.35 + viaje * 0.65);

    const zoom = 1 + Math.pow(bz, 1.5) * 9;
    svgLogo.style.transformOrigin = `${((GOTA.x + GOTA.lado / 2) / GOTA.vbw) * 100}% ${((GOTA.y + GOTA.lado / 2) / GOTA.vbh) * 100}%`;
    svgLogo.style.transform = `translate3d(${cx - m.cx}px, ${cy - m.cy}px, 0) scale(${zoom})`;
    letras.forEach((l, k) => {
      const t = clamp((bz - k * 0.03) / 0.45);
      l.style.opacity = String(1 - suave(t));
      l.style.transform = `translate(${t * (k - 1) * 9}%, ${-t * (10 + k * 6)}%) rotate(${t * (k - 2) * 9}deg)`;
    });
    if (aura) aura.style.opacity = String(1 - clamp((p - 0.3) / 0.3) * 0.6);

    estado.ox = (cx - m.W / 2) / m.px;
    estado.oy = -(cy - m.H / 2) / m.px;
    estado.escala = (lado / 2 / m.px) * 0.97;
    estado.ry = Math.PI * 2 * suave(bz);
    estado.rx = Math.sin(bz * Math.PI) * -0.5;
    estado.rz = Math.sin(bz * Math.PI) * 0.2;
    estado.vivo = lerp(0.25, 0, clamp(bz * 3)) + Math.sin(bz * Math.PI) * 0.6;
    estado.ondula = Math.sin(bz * Math.PI) * 1.3;
    estado.foto = suave(clamp((p - 0.2) / 0.22));
    colocarPlana(cx, cy, lado, Math.sin(bz * Math.PI) * 0.4, p);

    // C) la gota se abre como ventana; las partículas estallan
    const c = clamp((p - 0.45) / 0.42);
    const fin = Math.hypot(m.W, m.H) * 1.3;
    if (p > 0.01) hero.classList.add('portal-listo');
    portal.style.visibility = p > 0.445 ? 'visible' : 'hidden';
    portal.style.setProperty('--m', `${lerp(ladoFinal, fin, entra(c))}px`);
    estado.opacidad = 1 - clamp((p - 0.46) / 0.03);
    if (plana && !gota3d) plana.style.opacity = String(1 - clamp((p - 0.46) / 0.03));
    fotos.forEach((foto, i) => {
      const op = i === 0 ? 1 : suave(clamp((c - (0.22 + i * 0.25)) / 0.14));
      foto.style.opacity = String(op);
      foto.style.transform = `scale(${lerp(1.0, 1.12, suave(c)) + (i > 0 ? (1 - op) * 0.15 : 0)})`;
    });
    estP.cx = cx; estP.cy = cy;
    estP.estallido = suave(clamp((p - 0.44) / 0.3));
    estP.opacidad = 1 - clamp((p - 0.62) / 0.12);

    // D) el lema aparece palabra a palabra
    const d = clamp((p - 0.84) / 0.14);
    if (portalTexto) portalTexto.style.opacity = d > 0 ? '1' : '0';
    palabras.forEach((s, i) => {
      const t = suave(clamp(d * (palabras.length + 4) / 4 - i / 4));
      s.style.opacity = String(t);
      s.style.transform = `translate3d(0, ${(1 - t) * 0.6}em, 0)`;
      s.style.filter = t < 1 ? `blur(${(1 - t) * 6}px)` : '';
    });
  };

  gsap.to(prog, {
    p: 1,
    ease: 'none',
    onUpdate: aplicar,
    scrollTrigger: {
      trigger: hero,
      start: 'top top',
      end: () => `+=${innerHeight * 2.8}`,
      pin: true,
      scrub: 0.9,
      invalidateOnRefresh: true,
      onRefresh: () => { svgLogo.style.transform = ''; m0 = medir(); aplicar(); },
    },
  });
  aplicar();
}

/* ================= SERVICIOS EN HORIZONTAL ================= */
const seccionServicios = document.querySelector<HTMLElement>('[data-servicios]');
const pista = document.querySelector<HTMLElement>('[data-pista]');
if (movimiento && seccionServicios && pista) {
  gsap.matchMedia().add('(min-width: 900px) and (min-height: 600px)', () => {
    html.classList.add('pista-horizontal');
    ScrollTrigger.refresh();
    const distancia = () => Math.max(0, pista.scrollWidth - innerWidth);
    const horizontal = gsap.to(pista, {
      x: () => -distancia(), ease: 'none',
      scrollTrigger: { trigger: seccionServicios, start: 'top top', end: () => `+=${distancia() + innerHeight * 0.3}`, pin: true, scrub: 0.6, invalidateOnRefresh: true },
    });
    pista.querySelectorAll<HTMLElement>('[data-servicio]').forEach((t) => {
      gsap.fromTo(t, { rotateY: -24, z: -140, transformPerspective: 1100 }, {
        rotateY: 0, z: 0, ease: 'none',
        scrollTrigger: { trigger: t, containerAnimation: horizontal, start: 'left 108%', end: 'left 55%', scrub: true },
      });
    });
    return () => html.classList.remove('pista-horizontal');
  });
}

/* ================= PROFUNDIDAD, FRANJA Y OPINIONES ================= */
if (movimiento) {
  document.querySelectorAll<HTMLElement>('[data-parallax]').forEach((el) => {
    const v = parseFloat(el.dataset.parallax ?? '0');
    gsap.fromTo(el, { yPercent: v * 60 }, { yPercent: -v * 60, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true } });
  });
  document.querySelectorAll<HTMLElement>('[data-franja]').forEach((fila) => {
    const dir = parseFloat(fila.dataset.franja ?? '1');
    gsap.fromTo(fila, { xPercent: dir < 0 ? 0 : -25 }, { xPercent: dir < 0 ? -25 : 0, ease: 'none', scrollTrigger: { trigger: fila.parentElement, start: 'top bottom', end: 'bottom top', scrub: 0.4 } });
  });
}
const lista = document.querySelector<HTMLElement>('[data-opiniones]');
document.querySelectorAll<HTMLButtonElement>('[data-op]').forEach((b) =>
  b.addEventListener('click', () => {
    if (!lista) return;
    const paso = (lista.querySelector('li')?.getBoundingClientRect().width ?? 300) + 20;
    lista.scrollBy({ left: paso * parseFloat(b.dataset.op ?? '1'), behavior: movimiento ? 'smooth' : 'auto' });
  }),
);

window.addEventListener('load', () => ScrollTrigger.refresh());
document.fonts?.ready.then(() => ScrollTrigger.refresh());
