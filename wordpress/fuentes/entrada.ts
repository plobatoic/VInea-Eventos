/**
 * Entrada con el logo (solo la primera vez en cada visita). La gota cae y las letras suben (CSS);
 * después la gota vuela hasta la gota de la portada mientras el fondo crema se desvanece, y la capa
 * desaparece. Un clic en cualquier momento la salta.
 */
const html = document.documentElement;
const capa = document.querySelector<HTMLElement>('[data-ilogo]');
if (capa && html.classList.contains('con-intro')) {
  const gota = capa.querySelector<HTMLElement>('.ilogo__gota')!;
  let hecho = false;
  const fin = () => {
    if (hecho) return;
    hecho = true;
    capa.remove();
    html.classList.remove('con-intro', 'ilogo-llega');
    html.classList.add('sin-intro');
  };
  const vuela = () => {
    if (hecho) return;
    const destino = document.querySelector<HTMLElement>('[data-gota-css]');
    const a = gota.getBoundingClientRect();
    const b = destino?.getBoundingClientRect();
    capa.classList.add('ilogo--vuela');
    if (!b || !b.width || !a.width) { setTimeout(fin, 700); return; }
    const s = b.width / a.width;
    const dx = b.left + b.width / 2 - (a.left + a.width / 2);
    const dy = b.top + b.height / 2 - (a.top + a.height / 2);
    gota.style.animation = 'none';
    const vuelo = gota.animate(
      [
        { transform: 'none', opacity: 1 },
        { transform: `translate(${dx * 0.5}px, ${dy * 0.5 - 40}px) rotate(-8deg) scale(${(1 + s) / 2})`, opacity: 1, offset: 0.55 },
        { transform: `translate(${dx}px, ${dy}px) rotate(0deg) scale(${s})`, opacity: 1, offset: 0.85 },
        { transform: `translate(${dx}px, ${dy}px) rotate(0deg) scale(${s})`, opacity: 0 },
      ],
      { duration: 1150, easing: 'cubic-bezier(0.6, 0, 0.25, 1)', fill: 'forwards' },
    );
    setTimeout(() => html.classList.add('ilogo-llega'), 1150 * 0.8);
    vuelo.finished.then(fin, fin);
  };
  // El vuelo empieza cuando termina de subir la última letra (así no depende de lo que tarde en cargar)
  let t = 0;
  const ultima = capa.querySelector<SVGElement>('.logo__letra:last-of-type');
  const anims = ultima?.getAnimations() ?? [];
  const pendientes = anims.filter((x) => x.playState !== 'finished');
  if (!pendientes.length) t = window.setTimeout(vuela, 250);
  else Promise.all(pendientes.map((x) => x.finished)).then(() => { t = window.setTimeout(vuela, 350); }, () => vuela());
  capa.addEventListener('click', () => { clearTimeout(t); fin(); });
  setTimeout(fin, 7000); // red de seguridad
}
export {};
