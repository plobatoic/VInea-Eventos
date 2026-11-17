export const sitio = {
  nombre: 'Vinea Eventos y Turismo',
  nombreCorto: 'Vinea',
  url: 'https://vineaeventos.com',
  email: 'info@vineaeventos.com',
  telefonos: [
    { texto: '+34 611 88 40 83', href: 'tel:+34611884083' },
    { texto: '+34 623 00 43 81', href: 'tel:+34623004381' },
  ],
  zona: 'Manresa · Terrassa · Barcelona',
  redes: [
    { nombre: 'Instagram', href: 'https://www.instagram.com/vinea_eventos_turismo/' },
    { nombre: 'LinkedIn', href: 'https://www.linkedin.com/company/vinea-eventos-y-turismo' },
  ],
  descripcion:
    'Agencia de eventos en Barcelona para empresas e instituciones. Producción integral de congresos, ferias, galas, team building y experiencias accesibles.',
};

export const menu = [
  { texto: 'Servicios', href: '/servicios/' },
  { texto: 'Proyectos', href: '/proyectos/' },
  { texto: 'Eventos', href: '/eventos/' },
  { texto: 'Nuestra esencia', href: '/nuestra-esencia/' },
  { texto: 'Accesibilidad', href: '/accesibilidad-e-inclusion/' },
];

export const fecha = (d: Date, opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }) =>
  new Intl.DateTimeFormat('es-ES', { ...opts, timeZone: 'Europe/Madrid' }).format(d);
