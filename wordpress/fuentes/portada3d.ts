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

/* La esencia: entrada de una vez al aparecer */
const ese = document.querySelector<HTMLElement>('[data-ese]');
if (ese) {
  const io2 = new IntersectionObserver(([en]) => {
    if (en.isIntersecting) { ese.classList.add('visible'); io2.disconnect(); }
  }, { threshold: 0.25 });
  io2.observe(ese);
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
