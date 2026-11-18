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

/* ================= HERO: zoom a través del logo ================= */
const hero = document.querySelector<HTMLElement>('[data-hero]');
const canvas = document.querySelector<HTMLCanvasElement>('[data-gota]');
const plana = document.querySelector<HTMLElement>('[data-gota-plana]');
const logo = document.querySelector<HTMLElement>('[data-hero-logo]');
const svgLogo = logo?.querySelector<SVGSVGElement>('svg');
const letras = [...(logo?.querySelectorAll<SVGGElement>('.logo__letra') ?? [])];
const fades = [...document.querySelectorAll<HTMLElement>('[data-hero-fade]')];
const brillo = document.querySelector<HTMLElement>('[data-hero-brillo]');
const portal = document.querySelector<HTMLElement>('[data-portal]');
const fotos = [...document.querySelectorAll<HTMLElement>('[data-portal-foto]')];
const portalTexto = document.querySelector<HTMLElement>('[data-portal-texto]');
const bajar = document.querySelector<HTMLElement>('[data-hero-bajar]');

// Escena 3D: a z=0 la mitad de la altura visible son 1,406 unidades
const MITAD = 4.5 / 1.6 / 2;
// Posición de la gota dentro del viewBox del logo (0 0 1353 435)
const GOTA = { x: 11, y: 10, lado: 412, vbw: 1353, vbh: 435 };

const medir = () => {
  const hr = hero!.getBoundingClientRect();
  const sr = svgLogo!.getBoundingClientRect();
  const k = sr.width / GOTA.vbw;
  const lado = GOTA.lado * k;
  const cx = sr.left - hr.left + (GOTA.x + GOTA.lado / 2) * k;
  const cy = sr.top - hr.top + (GOTA.y + GOTA.lado / 2) * k;
  const W = hr.width, H = hr.height, px = H / (2 * MITAD);
  return { W, H, px, lado, cx, cy, movil: W / H < 0.9 };
};

const estado: EstadoGota = { rx: 0, ry: 0, rz: 0, escala: 0.4, ox: 0, oy: 0, ondula: 0, opacidad: 1, vivo: 0.25 };
let m0 = hero && svgLogo ? medir() : null;

// Mezcla de colores para oscurecer el fondo
const mezcla = (a: number[], b: number[], t: number) => `rgb(${a.map((v, i) => Math.round(lerp(v, b[i], t))).join(',')})`;
const HUESO = [247, 243, 236], NOCHE = [20, 18, 16];

let gota3d = false;
const iniciarGota = () => {
  if (!canvas || !m0) return;
  const g = crearGota(canvas, estado);
  if (!g) return; // sin WebGL: sigue la gota plana
  gota3d = true;
  html.classList.add('gota-3d');
  if (plana) plana.style.display = 'none';
  canvas.style.transition = 'opacity .6s';
  if (movimiento) g.arrancar(); else g.fotograma();
};

// Arranca con la primera interacción (o a los pocos segundos con ratón) para no frenar la carga
let gotaIniciada = false;
const eventosInicio = ['pointermove', 'pointerdown', 'touchstart', 'wheel', 'keydown', 'scroll'] as const;
const lanzar = () => {
  if (gotaIniciada) return;
  gotaIniciada = true;
  eventosInicio.forEach((ev) => window.removeEventListener(ev, lanzar));
  hero?.classList.add('portal-listo'); // ahora sí se descargan las fotos del portal
  const idle = (window as any).requestIdleCallback ?? ((f: () => void) => setTimeout(f, 50));
  idle(iniciarGota, { timeout: 600 });
};
eventosInicio.forEach((ev) => window.addEventListener(ev, lanzar, { passive: true, once: true }));
if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) setTimeout(lanzar, 4000);
if (!movimiento) lanzar();

if (movimiento && hero && portal && svgLogo && m0) {
  const prog = { p: 0 };

  // Gota plana (sin WebGL): se coloca exactamente encima de la del logo
  const colocarPlana = (cx: number, cy: number, lado: number, giro: number, p: number) => {
    if (!plana || gota3d) return;
    const usar = p > 0.003;
    hero!.classList.toggle('usa-plana', usar);
    plana.style.display = usar ? 'block' : 'none';
    if (!usar) return;
    plana.style.width = `${m0!.lado}px`;
    const s = lado / m0!.lado;
    plana.style.transform = `translate3d(${cx - lado / 2}px, ${cy - lado / 2}px, 0) scale(${s}) rotate(${giro}rad)`;
  };

  const aplicar = () => {
    const p = prog.p;
    const m = m0!;

    // A) Textos del hero se van (0 → 0,14)
    const a = suave(clamp(p / 0.14));
    fades.forEach((el, i) => {
      el.style.opacity = String(1 - a);
      el.style.transform = `translate3d(0, ${a * (30 + i * 16)}px, 0)`;
    });
    if (bajar) bajar.style.opacity = String(1 - clamp(p / 0.06));

    // B) Zoom a través del logo (0,03 → 0,46): la gota viaja al centro y crece girando,
    //    las letras pasan "por delante de la cámara" y desaparecen.
    const bz = clamp((p - 0.03) / 0.43);
    const viaje = suave(bz);
    const cx = lerp(m.cx, m.W / 2, viaje);
    const cy = lerp(m.cy, m.H / 2, viaje);
    const ladoFinal = Math.min(m.H * (m.movil ? 0.42 : 0.56), m.W * 0.8);
    const lado = lerp(m.lado, ladoFinal, entra(bz) * 0.35 + viaje * 0.65);

    const zoom = 1 + Math.pow(bz, 1.6) * 7;
    svgLogo.style.transformOrigin = `${(GOTA.x + GOTA.lado / 2) / GOTA.vbw * 100}% ${(GOTA.y + GOTA.lado / 2) / GOTA.vbh * 100}%`;
    svgLogo.style.transform = `translate3d(${cx - m.cx}px, ${cy - m.cy}px, 0) scale(${zoom})`;
    letras.forEach((l, k) => {
      const t = clamp((bz - k * 0.035) / 0.5);
      l.style.opacity = String(1 - suave(t));
      l.style.transform = `translate(${t * (k - 1) * 6}%, ${-t * (8 + k * 5)}%) rotate(${t * (k - 2) * 7}deg)`;
    });

    // Fondo: de hueso a noche, para que la gota brille y el portal se abra en oscuro
    const f = suave(clamp((p - 0.12) / 0.3));
    hero.style.setProperty('--fondo', mezcla(HUESO, NOCHE, f));
    if (brillo) brillo.style.opacity = String(1 - f * 0.7);

    // Gota 3D: mismo sitio y tamaño que la del logo; luego vuela y da una vuelta completa
    estado.ox = (cx - m.W / 2) / m.px;
    estado.oy = -(cy - m.H / 2) / m.px;
    estado.escala = (lado / 2 / m.px) * 0.97;
    estado.ry = Math.PI * 2 * suave(bz);
    estado.rx = Math.sin(bz * Math.PI) * -0.45;
    estado.rz = Math.sin(bz * Math.PI) * 0.18;
    estado.vivo = lerp(0.25, 0, clamp(bz * 3)) + Math.sin(bz * Math.PI) * 0.6;
    estado.ondula = Math.sin(bz * Math.PI) * 1.2;
    colocarPlana(cx, cy, lado, Math.sin(bz * Math.PI) * 0.4, p);

    // C) La gota se abre como ventana (0,44 → 0,88) y pasan tres fotos
    const c = clamp((p - 0.44) / 0.44);
    const fin = Math.hypot(m.W, m.H) * 1.3;
    if (p > 0.01) hero.classList.add('portal-listo');
    portal.style.visibility = p > 0.43 ? 'visible' : 'hidden';
    portal.style.setProperty('--m', `${lerp(ladoFinal, fin, entra(c))}px`);
    estado.opacidad = 1 - clamp((p - 0.45) / 0.04);
    if (plana && !gota3d) plana.style.opacity = String(1 - clamp((p - 0.45) / 0.04));
    fotos.forEach((foto, i) => {
      const entrada = i === 0 ? 1 : suave(clamp((c - (0.18 + i * 0.26)) / 0.14));
      foto.style.opacity = String(entrada);
      foto.style.transform = `scale(${lerp(1.4, 1, suave(c))})`;
    });

    // D) Texto final sobre la foto (0,86 → 1)
    if (portalTexto) {
      const d = suave(clamp((p - 0.86) / 0.12));
      portalTexto.style.opacity = String(d);
      portalTexto.style.transform = `translate3d(0, ${(1 - d) * 50}px, 0)`;
    }
  };

  gsap.to(prog, {
    p: 1,
    ease: 'none',
    onUpdate: aplicar,
    scrollTrigger: {
      trigger: hero,
      start: 'top top',
      end: () => `+=${innerHeight * 2.6}`,
      pin: true,
      scrub: 0.8,
      invalidateOnRefresh: true,
      onRefresh: () => {
        // remedir con el logo en su sitio
        svgLogo.style.transform = '';
        m0 = medir();
        aplicar();
      },
    },
  });
  aplicar();
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

/* ================= FRANJA DE SERVICIOS ================= */
if (movimiento) {
  document.querySelectorAll<HTMLElement>('[data-franja]').forEach((fila) => {
    const dir = parseFloat(fila.dataset.franja ?? '1');
    gsap.fromTo(fila, { xPercent: dir < 0 ? 0 : -25 }, {
      xPercent: dir < 0 ? -25 : 0,
      ease: 'none',
      scrollTrigger: { trigger: fila.parentElement, start: 'top bottom', end: 'bottom top', scrub: 0.4 },
    });
  });
}

/* ================= OPINIONES ================= */
const lista = document.querySelector<HTMLElement>('[data-opiniones]');
document.querySelectorAll<HTMLButtonElement>('[data-op]').forEach((b) =>
  b.addEventListener('click', () => {
    if (!lista) return;
    const paso = (lista.querySelector('li')?.getBoundingClientRect().width ?? 300) + 20;
    lista.scrollBy({ left: paso * parseFloat(b.dataset.op ?? '1'), behavior: movimiento ? 'smooth' : 'auto' });
  }),
);
