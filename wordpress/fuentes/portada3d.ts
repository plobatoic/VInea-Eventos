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

/* La esencia: paseo por la galería 3D guiado por el scroll */
const sala = document.querySelector<HTMLElement>('[data-sala]');
if (sala && movimiento) {
  const mundo = sala.querySelector<HTMLElement>('[data-sala-mundo]')!;
  const texto = sala.querySelector<HTMLElement>('[data-sala-texto]')!;
  const entrada = sala.querySelector<HTMLElement>('[data-sala-entrada]')!;
  const plano = sala.querySelector<SVGElement>('.sala__plano')!;
  const yo = sala.querySelector<SVGCircleElement>('[data-sala-yo]')!;
  const cl = (v: number) => Math.min(1, Math.max(0, v));
  const suave = (t: number) => t * t * (3 - 2 * t);
  const inOut = (t: number) => (t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
  const fino = matchMedia('(hover: hover) and (pointer: fine)').matches;
  let visible = false, raf = 0, mx = 0, my = 0, tmx = 0, tmy = 0, D = 4400;
  const medir = () => { D = parseFloat(getComputedStyle(sala).getPropertyValue('--D')) || 4400; };
  medir();
  const pinta = () => {
    raf = 0;
    const r = sala.getBoundingClientRect();
    const p = cl(-r.top / Math.max(1, r.height - innerHeight));
    const andar = inOut(cl(p / 0.86));
    const z = andar * (D - 560);
    const paso = Math.sin(andar * 26) * 3 * (1 - andar);           // el balanceo al caminar
    const giro = Math.sin(andar * Math.PI * 2) * 4;                  // mira a un lado y a otro
    mx += (tmx - mx) * 0.1; my += (tmy - my) * 0.1;
    mundo.style.transform = `rotateY(${(giro + mx * 10).toFixed(2)}deg) rotateX(${(my * -5).toFixed(2)}deg) translate3d(0, ${paso.toFixed(1)}px, ${z.toFixed(1)}px)`;
    const e = suave(cl((p - 0.03) / 0.14));
    entrada.style.opacity = (1 - e).toFixed(3);
    entrada.style.transform = `translate3d(0, ${(-e * 40).toFixed(1)}px, 0) scale(${(1 + e * 0.15).toFixed(3)})`;
    const k = suave(cl((p - 0.8) / 0.14));
    texto.style.opacity = k.toFixed(3);
    texto.style.transform = `translate3d(0, ${((1 - k) * 30).toFixed(1)}px, 0)`;
    plano.style.setProperty('--dib', (1 - suave(cl(p / 0.5))).toFixed(3));
    yo.setAttribute('cy', (212 - andar * 172).toFixed(1));
    if (Math.abs(tmx - mx) + Math.abs(tmy - my) > 0.002) pedir();
  };
  const pedir = () => { if (!raf && visible) raf = requestAnimationFrame(pinta); };
  new IntersectionObserver(([en]) => { visible = en.isIntersecting; pedir(); }, { rootMargin: '200px' }).observe(sala);
  addEventListener('scroll', pedir, { passive: true });
  addEventListener('resize', () => { medir(); pedir(); }, { passive: true });
  if (fino) addEventListener('pointermove', (ev) => { tmx = ev.clientX / innerWidth - 0.5; tmy = ev.clientY / innerHeight - 0.5; pedir(); }, { passive: true });
  visible = true; pinta(); visible = false;
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
