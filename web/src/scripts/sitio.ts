/**
 * Comportamiento común a todas las páginas. Ligero (sin GSAP):
 * desplazamiento suave, apariciones al hacer scroll, cabecera, menú móvil,
 * contadores, inclinación 3D de tarjetas y el interruptor "Reducir animaciones".
 */
import Lenis from 'lenis';

const html = document.documentElement;
const conMovimiento = () => html.classList.contains('movimiento');

/* ---------- Desplazamiento suave ---------- */
let lenis: Lenis | null = null;
if (conMovimiento()) {
  lenis = new Lenis({ autoRaf: true, lerp: 0.11, anchors: { offset: -90 } });
  (window as any).__lenis = lenis;
}

/* ---------- Contenido de WordPress: se le añaden las animaciones automáticamente ---------- */
document.querySelectorAll<HTMLElement>('.contenido').forEach((c) => {
  c.querySelectorAll<HTMLElement>('h2').forEach((h) => h.setAttribute('data-split', ''));
  c.querySelectorAll<HTMLElement>('.bloque > :not(h2):not(figure):not(.galeria-contenido)').forEach((el, i) => {
    el.setAttribute('data-reveal', ''); el.style.setProperty('--d', String((i % 3) * 80));
  });
  c.querySelectorAll<HTMLElement>('.bloque > figure, .galeria-contenido > figure').forEach((f) => f.setAttribute('data-reveal', 'foto'));
  c.querySelectorAll<HTMLElement>('figure img').forEach((img) => img.setAttribute('data-ampliar', ''));
  c.querySelectorAll<HTMLElement>('.cifra-contenido strong').forEach((n) => n.setAttribute('data-count', n.textContent ?? ''));
  c.querySelectorAll<HTMLAnchorElement>('.boton-contenido a').forEach((a) => {
    a.classList.add('boton'); a.insertAdjacentHTML('beforeend', ' <span class="flecha" aria-hidden="true">→</span>');
  });
});
// Menú de WordPress: botón para abrir cada submenú (teclado y móvil)
document.querySelectorAll<HTMLElement>('.menu-principal > .menu-item-has-children').forEach((li, i) => {
  const sub = li.querySelector<HTMLElement>(':scope > .sub-menu');
  if (!sub) return;
  sub.id = sub.id || `submenu-${i}`;
  li.setAttribute('data-sub', '');
  const b = document.createElement('button');
  b.type = 'button'; b.className = 'sub-boton'; b.setAttribute('aria-expanded', 'false'); b.setAttribute('aria-controls', sub.id); b.setAttribute('data-sub-boton', '');
  b.innerHTML = `<span class="sr-only">Abrir submenú de ${li.querySelector('a')?.textContent ?? ''}</span><span aria-hidden="true" class="sub-flecha"></span>`;
  li.querySelector(':scope > a')?.after(b);
});

/* ---------- Texto que entra por palabras ---------- */
document.querySelectorAll<HTMLElement>('[data-split]').forEach((el) => {
  let i = 0;
  const partir = (nodo: Node) => {
    if (nodo.nodeType === Node.TEXT_NODE) {
      const frag = document.createDocumentFragment();
      (nodo.textContent ?? '').split(/(\s+)/).forEach((trozo) => {
        if (!trozo) return;
        if (/^\s+$/.test(trozo)) { frag.append(document.createTextNode(' ')); return; }
        const p = document.createElement('span');
        p.className = 'p';
        const s = document.createElement('span');
        s.textContent = trozo;
        s.style.setProperty('--i', String(i++));
        p.append(s);
        frag.append(p);
      });
      nodo.parentNode?.replaceChild(frag, nodo);
    } else if (nodo.nodeType === Node.ELEMENT_NODE && !(nodo as HTMLElement).matches('br')) {
      [...nodo.childNodes].forEach(partir);
    }
  };
  if (!el.getAttribute('aria-label')) el.setAttribute('aria-label', el.textContent?.trim().replace(/\s+/g, ' ') ?? '');
  [...el.childNodes].forEach(partir);
  el.querySelectorAll('.p').forEach((p) => p.setAttribute('aria-hidden', 'true'));
});

/* ---------- Apariciones ---------- */
const observador = new IntersectionObserver(
  (entradas) => {
    entradas.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        observador.unobserve(e.target);
      }
    });
  },
  { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
);
document.querySelectorAll('[data-reveal], [data-split]').forEach((el) => {
  if (el.closest('[data-reveal-manual]')) return;
  observador.observe(el);
});

/* ---------- Contadores tipo marcador: cada dígito rueda hasta su valor ---------- */
const marcador = (el: HTMLElement) => {
  const final = (el.dataset.count ?? el.textContent ?? '').trim();
  if (!/\d/.test(final) || el.dataset.listo) return;
  el.dataset.listo = '1';
  el.setAttribute('aria-label', final);
  el.textContent = '';
  [...final].forEach((ch, i) => {
    if (/\d/.test(ch)) {
      const col = document.createElement('span'); col.className = 'odo'; col.setAttribute('aria-hidden', 'true');
      const tira = document.createElement('span'); tira.className = 'odo__tira';
      tira.textContent = '0123456789'.split('').join('\n');
      tira.style.setProperty('--n', ch); tira.style.setProperty('--i', String(i));
      col.append(tira); el.append(col);
    } else {
      const sp = document.createElement('span'); sp.setAttribute('aria-hidden', 'true'); sp.textContent = ch; el.append(sp);
    }
  });
};
const contador = new IntersectionObserver(
  (entradas) => entradas.forEach((e) => {
    if (!e.isIntersecting) return;
    contador.unobserve(e.target);
    (e.target as HTMLElement).classList.add('odo-ya');
  }),
  { threshold: 0.6 },
);
document.querySelectorAll<HTMLElement>('[data-count]').forEach((el) => {
  if (conMovimiento()) { marcador(el); contador.observe(el); }
});

/* ---------- Cabecera: sólida al bajar, se esconde al bajar y aparece al subir ---------- */
const cabecera = document.querySelector<HTMLElement>('[data-cabecera]');
let ultimaY = window.scrollY;
const alDesplazar = () => {
  const y = window.scrollY;
  if (!cabecera) return;
  cabecera.classList.toggle('solida', y > 40);
  if (!html.classList.contains('menu-abierto')) cabecera.classList.toggle('oculta', y > 400 && y > ultimaY + 2);
  if (y < ultimaY - 2) cabecera.classList.remove('oculta');
  ultimaY = y;
};
window.addEventListener('scroll', alDesplazar, { passive: true });
alDesplazar();

/* ---------- Menú móvil ---------- */
const boton = document.querySelector<HTMLButtonElement>('[data-menu-boton]');
const textoBoton = document.querySelector<HTMLElement>('[data-menu-texto]');
const cerrarMenu = () => {
  html.classList.remove('menu-abierto');
  boton?.setAttribute('aria-expanded', 'false');
  if (textoBoton) textoBoton.textContent = 'Abrir menú';
  lenis?.start();
};
boton?.addEventListener('click', () => {
  const abrir = boton.getAttribute('aria-expanded') !== 'true';
  if (!abrir) return cerrarMenu();
  html.classList.add('menu-abierto');
  boton.setAttribute('aria-expanded', 'true');
  if (textoBoton) textoBoton.textContent = 'Cerrar menú';
  lenis?.stop();
  document.querySelector<HTMLAnchorElement>('[data-menu] a')?.focus();
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && html.classList.contains('menu-abierto')) {
    cerrarMenu();
    boton?.focus();
  }
});
document.querySelectorAll('[data-menu] a').forEach((a) => a.addEventListener('click', cerrarMenu));

/* ---------- Submenú de Servicios ---------- */
document.querySelectorAll<HTMLElement>('[data-sub]').forEach((li) => {
  const b = li.querySelector<HTMLButtonElement>('[data-sub-boton]');
  b?.addEventListener('click', () => {
    const abrir = !li.classList.contains('abierto');
    li.classList.toggle('abierto', abrir);
    b.setAttribute('aria-expanded', String(abrir));
  });
  li.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && li.classList.contains('abierto')) { li.classList.remove('abierto'); b?.setAttribute('aria-expanded', 'false'); b?.focus(); }
  });
});

/* ---------- Inclinación 3D en tarjetas (solo ratón) ---------- */
if (conMovimiento() && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
  document.querySelectorAll<HTMLElement>('[data-tilt]').forEach((el) => {
    let raf = 0;
    el.addEventListener('pointermove', (e) => {
      const r = el.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      const y = (e.clientY - r.top) / r.height - 0.5;
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        el.style.setProperty('--ry', `${x * 10}deg`);
        el.style.setProperty('--rx', `${-y * 10}deg`);
        el.style.setProperty('--mx', `${(x + 0.5) * 100}%`);
        el.style.setProperty('--my', `${(y + 0.5) * 100}%`);
      });
    });
    el.addEventListener('pointerleave', () => {
      el.style.setProperty('--ry', '0deg');
      el.style.setProperty('--rx', '0deg');
    });
  });
}

/* ---------- Interruptor "Reducir animaciones" ---------- */
const interruptor = document.querySelector<HTMLButtonElement>('[data-movimiento]');
if (interruptor) {
  interruptor.setAttribute('aria-pressed', String(html.classList.contains('sin-movimiento')));
  interruptor.addEventListener('click', () => {
    const reducir = !html.classList.contains('sin-movimiento');
    try { localStorage.setItem('vinea-movimiento', reducir ? 'off' : 'on'); } catch (e) {}
    location.reload();
  });
}

/* ---------- Cursor propio y botones magnéticos (solo con ratón) ---------- */
if (conMovimiento() && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
  const cursor = document.createElement('div');
  cursor.className = 'cursor';
  cursor.setAttribute('aria-hidden', 'true');
  cursor.innerHTML = '<span class="cursor__texto"></span>';
  document.body.append(cursor);
  const texto = cursor.firstElementChild as HTMLElement;
  let x = innerWidth / 2, y = innerHeight / 2, cx = x, cy = y, moviendo = false;
  const mover = () => {
    cx += (x - cx) * 0.2;
    cy += (y - cy) * 0.2;
    cursor.style.transform = `translate3d(${cx}px, ${cy}px, 0)`;
    if (Math.abs(x - cx) > 0.1 || Math.abs(y - cy) > 0.1) requestAnimationFrame(mover);
    else moviendo = false;
  };
  window.addEventListener('pointermove', (e) => {
    x = e.clientX; y = e.clientY;
    cursor.classList.add('activo');
    if (!moviendo) { moviendo = true; requestAnimationFrame(mover); }
    const t = e.target as Element | null;
    const conTexto = t?.closest<HTMLElement>('[data-cursor]');
    const enlace = t?.closest('a, button, summary, label, input, textarea, select');
    cursor.classList.toggle('con-texto', !!conTexto);
    cursor.classList.toggle('sobre-enlace', !conTexto && !!enlace);
    cursor.classList.toggle('oculto', !!t?.closest('input, textarea, select'));
    if (conTexto) texto.textContent = conTexto.dataset.cursor ?? '';
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => cursor.classList.remove('activo'));

  document.querySelectorAll<HTMLElement>('.boton').forEach((b) => {
    b.addEventListener('pointermove', (e) => {
      const r = b.getBoundingClientRect();
      b.style.setProperty('--bx', `${(e.clientX - r.left - r.width / 2) * 0.22}px`);
      b.style.setProperty('--by', `${(e.clientY - r.top - r.height / 2) * 0.35}px`);
    });
    b.addEventListener('pointerleave', () => { b.style.setProperty('--bx', '0px'); b.style.setProperty('--by', '0px'); });
  });
}

/* ---------- Texto que se ilumina con el scroll (palabra a palabra) ---------- */
const rellenos = [...document.querySelectorAll<HTMLElement>('[data-relleno]')].map((el) => {
  const ps = (el.textContent ?? '').trim().split(/\s+/);
  el.setAttribute('aria-label', el.textContent?.trim() ?? '');
  el.textContent = '';
  const spans = ps.map((p, i) => {
    const s = document.createElement('span'); s.className = 'rl'; s.setAttribute('aria-hidden', 'true'); s.textContent = p;
    el.append(s); if (i < ps.length - 1) el.append(' ');
    return s;
  });
  return { el, spans };
});
if (rellenos.length) {
  if (!conMovimiento()) rellenos.forEach((r) => r.spans.forEach((s) => s.classList.add('on')));
  else {
    let pend = false;
    const pintar = () => {
      pend = false;
      const vh = innerHeight;
      rellenos.forEach(({ el, spans }) => {
        const r = el.getBoundingClientRect();
        if (r.bottom < -50 || r.top > vh + 50) return;
        const p = Math.min(1, Math.max(0, (vh * 0.86 - r.top) / (r.height + vh * 0.38)));
        const n = Math.round(p * spans.length);
        spans.forEach((s, i) => s.classList.toggle('on', i < n));
      });
    };
    window.addEventListener('scroll', () => { if (!pend) { pend = true; requestAnimationFrame(pintar); } }, { passive: true });
    pintar();
  }
}

/* ---------- Inclinación según la velocidad del scroll ---------- */
const sesgados = [...document.querySelectorAll<HTMLElement>('[data-skew]')];
if (lenis && sesgados.length) {
  let actual = 0;
  lenis.on('scroll', (l: any) => {
    const objetivo = Math.max(-7, Math.min(7, (l.velocity ?? 0) * 0.35));
    actual += (objetivo - actual) * 0.25;
    sesgados.forEach((el) => el.style.setProperty('--sesgo', `${actual.toFixed(2)}deg`));
  });
}

/* ---------- Vista previa flotante de la foto al pasar por una lista ---------- */
if (conMovimiento() && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
  const enlaces = [...document.querySelectorAll<HTMLElement>('[data-preview]')].filter((a) => a.dataset.preview);
  if (enlaces.length) {
    const caja = document.createElement('div'); caja.className = 'vista-previa'; caja.setAttribute('aria-hidden', 'true');
    const img = document.createElement('img'); img.alt = ''; caja.append(img); document.body.append(caja);
    let x = 0, y = 0, cx = 0, cy = 0, vx = 0, on = false, raf = 0;
    const mover = () => {
      const dx = x - cx; cx += dx * 0.16; cy += (y - cy) * 0.16; vx = dx;
      caja.style.transform = `translate3d(${cx}px, ${cy}px, 0) rotate(${Math.max(-12, Math.min(12, vx * 0.15))}deg)`;
      if (on || Math.abs(dx) > 0.5) raf = requestAnimationFrame(mover);
    };
    enlaces.forEach((a) => {
      a.addEventListener('pointerenter', (e) => {
        img.src = a.dataset.preview!; caja.classList.add('ver'); on = true;
        x = e.clientX; y = e.clientY; if (!raf) { cx = x; cy = y; } cancelAnimationFrame(raf); raf = requestAnimationFrame(mover);
      });
      a.addEventListener('pointermove', (e) => { x = e.clientX; y = e.clientY; });
      a.addEventListener('pointerleave', () => { caja.classList.remove('ver'); on = false; });
    });
  }
}

/* ---------- Visor de fotos con transición (pulsa cualquier foto del contenido) ---------- */
const ampliables = [...document.querySelectorAll<HTMLImageElement>('img[data-ampliar]')];
if (ampliables.length) {
  const visor = document.createElement('dialog'); visor.className = 'visor'; visor.setAttribute('aria-label', 'Foto ampliada');
  visor.innerHTML = '<button type="button" class="visor__cerrar" aria-label="Cerrar">×</button><img alt=""><p class="visor__pie"></p>';
  document.body.append(visor);
  const vimg = visor.querySelector('img')!, pie = visor.querySelector<HTMLElement>('.visor__pie')!;
  const vt = (f: () => void) => ((document as any).startViewTransition && conMovimiento() ? (document as any).startViewTransition(f) : f());
  let origen: HTMLImageElement | null = null;
  const abrir = (img: HTMLImageElement) => {
    origen = img;
    img.style.viewTransitionName = 'foto-ampliada';
    vt(() => {
      img.style.viewTransitionName = '';
      vimg.src = img.currentSrc || img.src; vimg.alt = img.alt;
      pie.textContent = img.closest('figure')?.querySelector('figcaption')?.textContent ?? '';
      vimg.style.viewTransitionName = 'foto-ampliada';
      visor.showModal(); lenis?.stop();
    });
  };
  const cerrar = () => {
    vt(() => {
      vimg.style.viewTransitionName = '';
      if (origen) origen.style.viewTransitionName = 'foto-ampliada';
      visor.close(); lenis?.start();
    })?.finished?.then(() => { if (origen) origen.style.viewTransitionName = ''; });
  };
  ampliables.forEach((img) => {
    img.tabIndex = 0; img.setAttribute('role', 'button'); img.setAttribute('aria-label', `Ampliar foto${img.alt ? ': ' + img.alt : ''}`);
    img.addEventListener('click', () => abrir(img));
    img.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(img); } });
  });
  visor.addEventListener('click', (e) => { if (e.target === visor || (e.target as HTMLElement).closest('.visor__cerrar')) cerrar(); });
  visor.addEventListener('cancel', (e) => { e.preventDefault(); cerrar(); });
}

/* ---------- Validación accesible de formularios ---------- */
document.querySelectorAll<HTMLFormElement>('[data-formulario]').forEach((form) => {
  const estado = form.querySelector<HTMLElement>('[data-estado]');
  form.addEventListener('submit', (e) => {
    let primero: HTMLElement | null = null;
    form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('[required]').forEach((campo) => {
      const ok = campo.type === 'checkbox' ? (campo as HTMLInputElement).checked : campo.checkValidity() && campo.value.trim() !== '';
      const err = campo.closest('.campo')?.querySelector<HTMLElement>('.campo__error');
      campo.setAttribute('aria-invalid', String(!ok));
      if (err) err.hidden = ok;
      if (!ok && !primero) primero = campo;
    });
    if (primero) { e.preventDefault(); (primero as HTMLElement).focus(); if (estado) estado.textContent = 'Revisa los campos marcados.'; }
    else if (estado) estado.textContent = 'Enviando…';
  });
});

export {};
