import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const foto = z.object({ src: z.string(), alt: z.string() });
const punto = z.object({ titulo: z.string(), texto: z.string() });
const cifra = z.object({ valor: z.union([z.string(), z.number()]).transform(String), etiqueta: z.string() });
const seo = z.object({ title: z.string(), description: z.string() });

const servicios = defineCollection({
  loader: glob({ pattern: '*.md', base: './src/content/servicios' }),
  schema: z.object({
    titulo: z.string(),
    tipo: z.enum(['servicio', 'experiencia']).default('servicio'),
    orden: z.number().default(99),
    etiqueta: z.string().optional(),
    titular: z.string(),
    resumen: z.string(),
    imagen: z.string(),
    imagenAlt: z.string(),
    galeria: z.array(foto).default([]),
    destacados: z.array(punto).default([]),
    proceso: z.array(punto).default([]),
    incluye: z.array(z.string()).default([]),
    cifras: z.array(cifra).default([]),
    faq: z.array(z.object({ pregunta: z.string(), respuesta: z.string() })).default([]),
    cta: z.object({ titulo: z.string(), texto: z.string() }).optional(),
    seo: seo.optional(),
    urlAntigua: z.string().optional(),
  }),
});

const proyectos = defineCollection({
  loader: glob({ pattern: '*.md', base: './src/content/proyectos' }),
  schema: z.object({
    titulo: z.string(),
    fecha: z.coerce.date(),
    cliente: z.string().optional(),
    lugar: z.string().optional(),
    categoria: z.string().optional(),
    resumen: z.string(),
    imagen: z.string().optional(),
    imagenAlt: z.string().optional(),
    galeria: z.array(foto).default([]),
    cifras: z.array(cifra).default([]),
    destacado: z.boolean().default(false),
    urlAntigua: z.string().optional(),
  }),
});

const paginas = defineCollection({
  loader: glob({ pattern: '*.md', base: './src/content/paginas' }),
  schema: z
    .object({
      titulo: z.string(),
      etiqueta: z.string().optional(),
      titular: z.string().optional(),
      resumen: z.string().optional(),
      imagen: z.string().optional(),
      imagenAlt: z.string().optional(),
      seo: seo.optional(),
      urlAntigua: z.string().optional(),
      valores: z.array(punto).default([]),
      equipo: z.array(z.object({ nombre: z.string(), rol: z.string().optional(), texto: z.string().optional() })).default([]),
      iniciativas: z.array(punto).default([]),
      testimonio: z.object({ texto: z.string(), autor: z.string().optional() }).optional(),
    })
    .passthrough(),
});

const eventos = defineCollection({
  loader: glob({ pattern: '*.md', base: './src/content/eventos' }),
  schema: z.object({
    titulo: z.string(),
    fecha: z.coerce.date(),
    lugar: z.string().optional(),
    resumen: z.string(),
    imagen: z.string().optional(),
    imagenAlt: z.string().optional(),
    estado: z.enum(['proximo', 'agotado', 'pasado']).default('proximo'),
    enlace: z.string().optional(),
  }),
});

export const collections = { servicios, proyectos, paginas, eventos };
