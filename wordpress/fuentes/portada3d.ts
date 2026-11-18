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
export {};
