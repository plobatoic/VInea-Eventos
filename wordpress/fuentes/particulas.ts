/**
 * Partículas de luz (bokeh) con profundidad para la portada. Canvas 2D ligero:
 * cada partícula es un "sprite" desenfocado ya dibujado, así que pintar es barato.
 * - Flotan hacia arriba despacio y se desplazan con el ratón según su profundidad.
 * - `estallido` (0..1) las empuja hacia fuera desde el centro (cuando se abre el portal).
 * - Solo se anima mientras el canvas está en pantalla y la pestaña visible.
 */
export interface EstadoParticulas { estallido: number; opacidad: number; cx: number; cy: number }

export function crearParticulas(canvas: HTMLCanvasElement, estado: EstadoParticulas) {
  const ctx = canvas.getContext('2d', { alpha: true });
  if (!ctx) return null;
  const movil = window.matchMedia('(max-width: 760px)').matches;
  const N = movil ? 26 : 58;
  const colores = ['255,106,51', '255,138,102', '255,86,17', '247,233,214', '255,180,140'];

  // Sprites desenfocados (uno por color)
  const sprites = colores.map((c) => {
    const s = document.createElement('canvas');
    s.width = s.height = 64;
    const g = s.getContext('2d')!;
    const rg = g.createRadialGradient(32, 32, 0, 32, 32, 32);
    rg.addColorStop(0, `rgba(${c},1)`);
    rg.addColorStop(0.35, `rgba(${c},.55)`);
    rg.addColorStop(1, `rgba(${c},0)`);
    g.fillStyle = rg;
    g.fillRect(0, 0, 64, 64);
    return s;
  });

  let w = 0, h = 0, dpr = 1;
  const ajustar = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    w = canvas.clientWidth; h = canvas.clientHeight;
    canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  };
  ajustar();
  new ResizeObserver(ajustar).observe(canvas);

  const rnd = (a: number, b: number) => a + Math.random() * (b - a);
  const ps = Array.from({ length: N }, () => ({
    x: Math.random(), y: Math.random(), z: rnd(0.15, 1),
    r: rnd(3, 26), v: rnd(0.006, 0.02), c: Math.floor(Math.random() * sprites.length),
    fase: Math.random() * Math.PI * 2, a: rnd(0.25, 0.85),
  }));

  const raton = { x: 0, y: 0, sx: 0, sy: 0 };
  window.addEventListener('pointermove', (e) => { raton.x = e.clientX / innerWidth - 0.5; raton.y = e.clientY / innerHeight - 0.5; }, { passive: true });

  let activo = false, visible = true, raf = 0, t0 = performance.now(), antes = t0;
  const dibujar = (ahora: number) => {
    const dt = Math.min(0.05, (ahora - antes) / 1000); antes = ahora;
    const t = (ahora - t0) / 1000;
    raton.sx += (raton.x - raton.sx) * 0.04;
    raton.sy += (raton.y - raton.sy) * 0.04;
    ctx.clearRect(0, 0, w, h);
    canvas.style.opacity = String(estado.opacidad);
    if (estado.opacidad > 0.01) {
      const e = estado.estallido;
      for (const p of ps) {
        p.y -= p.v * dt * (0.6 + p.z);
        if (p.y < -0.1) { p.y = 1.1; p.x = Math.random(); }
        let x = (p.x + Math.sin(t * 0.3 + p.fase) * 0.01 - raton.sx * 0.06 * p.z) * w;
        let y = (p.y - raton.sy * 0.05 * p.z) * h;
        if (e > 0) {
          // empuje radial desde el centro de la gota
          const dx = x - estado.cx, dy = y - estado.cy;
          const d = Math.hypot(dx, dy) || 1;
          const empuje = e * e * (500 + 900 * p.z);
          x += (dx / d) * empuje; y += (dy / d) * empuje;
        }
        const r = p.r * (0.5 + p.z) * (1 + e * 1.5);
        ctx.globalAlpha = p.a * (0.55 + 0.45 * Math.sin(t * 0.8 + p.fase)) * (1 - e * 0.6);
        ctx.drawImage(sprites[p.c], x - r, y - r, r * 2, r * 2);
      }
      ctx.globalAlpha = 1;
    }
    if (activo) raf = requestAnimationFrame(dibujar);
  };
  const arrancar = () => { if (activo || !visible || document.hidden) return; activo = true; antes = performance.now(); raf = requestAnimationFrame(dibujar); };
  const parar = () => { activo = false; cancelAnimationFrame(raf); };
  new IntersectionObserver(([en]) => { visible = en.isIntersecting; visible ? arrancar() : parar(); }).observe(canvas);
  document.addEventListener('visibilitychange', () => (document.hidden ? parar() : arrancar()));
  return { arrancar, parar, fotograma: () => { dibujar(performance.now()); } };
}
