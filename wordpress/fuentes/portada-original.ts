/** Portada: bloques de la Home original (esencia con transición, reseñas, marcas, lema y cierre). */
const html = document.documentElement;
const movimiento = html.classList.contains('movimiento');
const io = new IntersectionObserver((es) => es.forEach((e) => e.target.classList.toggle('en-pausa', !e.isIntersecting)), { rootMargin: '100px' });
document.querySelectorAll<HTMLElement>('.marcas, [data-ese]').forEach((el) => io.observe(el));

// Aparecer una vez (lema, conciencia)
const una = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('visible'); una.unobserve(e.target); } }), { threshold: 0.3 });
document.querySelectorAll<HTMLElement>('.og-lema').forEach((el) => una.observe(el));

// La esencia: entrada y transición entre las dos fotos
const ese = document.querySelector<HTMLElement>('[data-ese]');
if (ese) {
  const o = new IntersectionObserver(([en]) => {
    if (en.isIntersecting) { ese.classList.add('visible'); o.disconnect(); if (movimiento) setTimeout(() => ese.classList.add('ciclo'), 1800); }
  }, { threshold: 0.25 });
  o.observe(ese);
}

// Reseñas: «Leer más» y «Ver más reseñas»
const resenas = document.querySelector<HTMLElement>('[data-resenas]');
if (resenas) {
  const marcar = () => resenas.querySelectorAll<HTMLElement>('.resena').forEach((r) => {
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
    if (ver) { resenas.classList.add('todas'); ver.closest('.resenas__pie')?.remove(); requestAnimationFrame(marcar); }
  });
  marcar();
  addEventListener('resize', marcar, { passive: true });
  document.fonts?.ready.then(marcar);
}
export {};
