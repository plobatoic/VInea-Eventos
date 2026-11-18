/**
 * Portada 3D (ligera, ~1 KB): inclina la escena con el ratón y con el scroll,
 * y pausa las animaciones CSS cuando no están en pantalla (ahorro de batería y CPU).
 * Todo el movimiento lo hace la tarjeta gráfica (transform); aquí solo se cambian variables CSS.
 */
const html = document.documentElement;
const movimiento = html.classList.contains('movimiento');

// Pausar lo que no se ve
const pausables = document.querySelectorAll<HTMLElement>('[data-h3d], [data-muro]');
const io = new IntersectionObserver((es) => es.forEach((e) => e.target.classList.toggle('en-pausa', !e.isIntersecting)), { rootMargin: '100px' });
pausables.forEach((el) => io.observe(el));

const hero = document.querySelector<HTMLElement>('[data-h3d]');
if (hero && movimiento) {
  let mx = 0, my = 0, tx = 0, ty = 0, sc = 0, tsc = 0, raf = 0;
  const paso = () => {
    mx += (tx - mx) * 0.08; my += (ty - my) * 0.08; sc += (tsc - sc) * 0.2;
    hero.style.setProperty('--mx', mx.toFixed(3));
    hero.style.setProperty('--my', my.toFixed(3));
    hero.style.setProperty('--sc', sc.toFixed(3));
    raf = Math.abs(tx - mx) + Math.abs(ty - my) + Math.abs(tsc - sc) > 0.001 ? requestAnimationFrame(paso) : 0;
  };
  const pedir = () => { if (!raf) raf = requestAnimationFrame(paso); };
  if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    window.addEventListener('pointermove', (e) => { tx = e.clientX / innerWidth - 0.5; ty = e.clientY / innerHeight - 0.5; pedir(); }, { passive: true });
  }
  window.addEventListener('scroll', () => {
    const v = Math.min(1, Math.max(0, scrollY / (hero.offsetHeight || 1)));
    if (v < 1.01) { tsc = v; pedir(); }
  }, { passive: true });
}

/* La esencia: plano de dron guiado por el scroll */
const dron = document.querySelector<HTMLElement>('[data-dron]');
if (dron && movimiento) {
  const cam = dron.querySelector<HTMLElement>('[data-dron-camara]')!;
  const plano = dron.querySelector<HTMLElement>('[data-dron-plano]')!;
  const texto = dron.querySelector<HTMLElement>('[data-dron-texto]')!;
  const nubes = [...dron.querySelectorAll<HTMLElement>('.dron__nube')].map((n) => ({ n, z: parseFloat(n.style.getPropertyValue('--nz')) || 0 }));
  const cl = (v: number) => Math.min(1, Math.max(0, v));
  const suave = (t: number) => t * t * (3 - 2 * t);
  const inOut = (t: number) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
  const LEJOS = 2600;
  let visible = false, raf = 0;
  const pinta = () => {
    raf = 0;
    const r = dron.getBoundingClientRect();
    const H = innerHeight, W = innerWidth, movil = W < 900;
    const p = cl(-r.top / Math.max(1, r.height - H));
    const e = inOut(cl(p / 0.72));
    const fin = suave(cl((p - 0.5) / 0.3));
    const lejos = 1 - e;
    const z = -(movil ? 1500 : LEJOS) * lejos;
    const balanceo = Math.sin(p * 18) * 5 * lejos;
    const tx = movil ? 0 : W * 0.22 * fin - W * 0.07 * lejos;
    const ty = (movil ? -H * 0.2 * fin : 0) - H * (movil ? 0.3 : 0.34) * lejos + balanceo; // desde lejos, el mosaico centrado
    cam.style.transform = `translate3d(${tx.toFixed(1)}px, ${ty.toFixed(1)}px, ${z.toFixed(1)}px) rotateX(${(60 * lejos).toFixed(2)}deg) rotateZ(${(-16 * lejos).toFixed(2)}deg)`;
    plano.style.opacity = (1 - suave(cl((p - 0.42) / 0.3))).toFixed(3);
    for (const { n, z: nz } of nubes) {
      const ef = z + nz; // distancia de la nube a la cámara (1000 = la cámara)
      n.style.opacity = (0.95 * cl((750 - ef) / 450)).toFixed(3);
    }
    const k = suave(cl((p - 0.66) / 0.2));
    texto.style.opacity = k.toFixed(3);
    texto.style.transform = `translate3d(0, ${((1 - k) * 34).toFixed(1)}px, 0)`;
  };
  const pedir = () => { if (!raf) raf = requestAnimationFrame(pinta); };
  new IntersectionObserver(([en]) => { visible = en.isIntersecting; if (visible) pedir(); }, { rootMargin: '200px' }).observe(dron);
  addEventListener('scroll', () => { if (visible) pedir(); }, { passive: true });
  addEventListener('resize', pedir, { passive: true });
  pinta();
}

/* Reseñas de Google: «Leer más» en las largas y «Ver más reseñas» */
const resenas = document.querySelector<HTMLElement>('[data-resenas]');
if (resenas) {
  const marcarLargas = () => resenas.querySelectorAll<HTMLElement>('.resena').forEach((r) => {
    const t = r.querySelector<HTMLElement>('.resena__texto');
    const b = r.querySelector<HTMLButtonElement>('.resena__mas');
    if (!t || !b || r.classList.contains('abierta')) return;
    b.hidden = t.scrollHeight <= t.clientHeight + 2;
  });
  resenas.addEventListener('click', (e) => {
    const b = (e.target as HTMLElement).closest<HTMLButtonElement>('.resena__mas');
    if (b) {
      const r = b.closest('.resena')!;
      const abierta = r.classList.toggle('abierta');
      b.setAttribute('aria-expanded', String(abierta));
      b.textContent = abierta ? 'Leer menos' : 'Leer más';
      return;
    }
    const ver = (e.target as HTMLElement).closest<HTMLButtonElement>('[data-resenas-ver]');
    if (ver) {
      resenas.classList.add('todas');
      ver.closest('.resenas__pie')?.remove();
      requestAnimationFrame(marcarLargas);
    }
  });
  marcarLargas();
  window.addEventListener('resize', marcarLargas, { passive: true });
  document.fonts?.ready.then(marcarLargas);
}

/* Pausar el muro de marcas cuando no se ve */
document.querySelectorAll<HTMLElement>('.marcas').forEach((el) => io.observe(el));
export {};
