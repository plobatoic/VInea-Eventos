/**
 * Gota 3D de Vinea — WebGL puro (sin librerías, ~6 KB).
 * La forma del logo (círculo + esquina superior izquierda) se dibuja con
 * "raymarching": volumen redondeado, brillo, reflejo de estudio y borde suave.
 * - Resolución adaptativa: si el dispositivo va justo, baja la calidad sola.
 * - Solo se dibuja cuando está en pantalla y la pestaña está visible.
 */

const VERT = `attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}`;

const FRAG = `
precision highp float;
uniform vec2 uRes;
uniform float uTime;
uniform mat3 uM;
uniform float uScale;
uniform vec2 uOff;
uniform float uWob;

// caja con radio por esquina (x: sup-dcha, y: inf-dcha, z: sup-izq, w: inf-izq)
float sdRB(vec2 p, vec2 b, vec4 r){ r.xy=(p.x>0.)?r.xy:r.zw; r.x=(p.y>0.)?r.x:r.y; vec2 q=abs(p)-b+r.x; return min(max(q.x,q.y),0.)+length(max(q,0.))-r.x; }
float smin(float a, float b, float k){ float h=clamp(.5+.5*(b-a)/k,0.,1.); return mix(b,a,h)-k*h*(1.-h); }

// Forma del logo: círculo de radio 1 + cuadrante superior izquierdo con esquina suavizada
float gota2(vec2 p){
  float c = length(p)-1.;
  float b = sdRB(p-vec2(-.5,.5), vec2(.5), vec4(0.,0.,.13,0.));
  return min(c,b);
}

float mapa(vec3 p){
  vec3 q = uM*(p-vec3(uOff,0.))/uScale;
  float g = gota2(q.xy);
  float re = .2;
  // grosor tipo almohada calculado con una versión suavizada de la forma (sin pliegues)
  float h = .15 + .16*smoothstep(0., .95, 1.-length(q.xy));
  vec2 w = vec2(g+re, abs(q.z)-h);
  float d = min(max(w.x,w.y),0.)+length(max(w,0.))-re;
  d += uWob*.03*sin(q.x*2.6+uTime*1.1)*sin(q.y*2.3+uTime*.9)*sin(q.z*3.+uTime*.7);
  return d*uScale*.85;
}

vec3 normal(vec3 p){
  const vec2 k = vec2(1.,-1.); float e = .0012*uScale;
  return normalize(k.xyy*mapa(p+k.xyy*e)+k.yyx*mapa(p+k.yyx*e)+k.yxy*mapa(p+k.yxy*e)+k.xxx*mapa(p+k.xxx*e));
}

void main(){
  vec2 uv = (gl_FragCoord.xy-.5*uRes)/uRes.y;
  vec3 ro = vec3(0.,0.,4.5);
  vec3 rd = normalize(vec3(uv,-1.6));
  vec3 oc = ro-vec3(uOff,0.);
  float R = 1.62*uScale;
  float b = dot(oc,rd), c = dot(oc,oc)-R*R, disc = b*b-c;
  if(disc<0.){ gl_FragColor=vec4(0.); return; }
  float sq = sqrt(disc);
  float t = max(-b-sq,0.), tmax = -b+sq;
  float dmin = 1e9, tmin = t; bool hit = false;
  for(int i=0;i<90;i++){
    float d = mapa(ro+rd*t);
    if(d<dmin){ dmin=d; tmin=t; }
    if(d<.0008*t){ hit=true; break; }
    t += d;
    if(t>tmax) break;
  }
  float px = tmin/(uRes.y*1.6);
  float a = hit ? 1. : 1.-smoothstep(0., px*1.6, dmin);
  if(a<=0.001){ gl_FragColor=vec4(0.); return; }
  if(!hit) t = tmin;

  vec3 p = ro+rd*t;
  vec3 n = normal(p);
  vec3 q = uM*(p-vec3(uOff,0.))/uScale;

  // Degradado de marca: coral abajo-izquierda → naranja intenso arriba-derecha
  vec3 coral = vec3(1.,.54,.40), naranja = vec3(1.,.30,.05);
  vec3 base = mix(coral, naranja, smoothstep(-1.1,.9,(q.x+q.y)*.62+.15));

  vec3 L = normalize(vec3(-.55,.75,.85));
  vec3 V = -rd, H = normalize(L+V);
  float dif = clamp(dot(n,L)*.5+.5,0.,1.); dif *= dif;
  float spe = pow(clamp(dot(n,H),0.,1.),70.)*.55;
  float fre = pow(1.-clamp(dot(n,V),0.,1.),3.);
  vec3 Rf = reflect(rd,n);
  float caja = smoothstep(.45,.9,Rf.y)*smoothstep(-.9,.1,Rf.x)*.32;     // reflejo de "softbox"
  float suelo = smoothstep(-.2,-.9,Rf.y)*.12;                           // rebote cálido inferior
  float sss = pow(clamp(1.-abs(q.z)*2.,0.,1.),2.)*.12;                  // translucidez en los cantos

  vec3 col = base*(.5+.68*dif);
  col += base*sss;
  col += vec3(1.,.95,.88)*(spe+caja);
  col += vec3(1.,.72,.55)*fre*.32;
  col += vec3(1.,.55,.35)*suelo;
  col = clamp(col,0.,1.);
  gl_FragColor = vec4(col*a, a);
}`;

export interface EstadoGota {
  rx: number; ry: number; rz: number;
  escala: number; ox: number; oy: number;
  ondula: number; opacidad: number;
}

export function crearGota(canvas: HTMLCanvasElement, estado: EstadoGota) {
  const gl = canvas.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false, powerPreference: 'high-performance' });
  if (!gl) return null;

  const sh = (tipo: number, src: string) => {
    const s = gl.createShader(tipo)!;
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s) ?? 'shader');
    return s;
  };
  const prog = gl.createProgram()!;
  try {
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, VERT));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, FRAG));
  } catch (e) {
    console.warn('[gota3d]', e);
    return null;
  }
  gl.linkProgram(prog);
  if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return null;
  gl.useProgram(prog);

  const buf = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
  const loc = gl.getAttribLocation(prog, 'p');
  gl.enableVertexAttribArray(loc);
  gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

  const u = (n: string) => gl.getUniformLocation(prog, n);
  const uRes = u('uRes'), uTime = u('uTime'), uM = u('uM'), uScale = u('uScale'), uOff = u('uOff'), uWob = u('uWob');

  const movil = window.matchMedia('(max-width: 760px)').matches;
  let calidad = movil ? 0.62 : 0.85;
  let w = 0, h = 0;
  const ajustar = () => {
    const dpr = Math.min(window.devicePixelRatio || 1, 1.6) * calidad;
    w = Math.max(1, Math.round(canvas.clientWidth * dpr));
    h = Math.max(1, Math.round(canvas.clientHeight * dpr));
    canvas.width = w;
    canvas.height = h;
    gl.viewport(0, 0, w, h);
  };
  ajustar();
  const ro = new ResizeObserver(ajustar);
  ro.observe(canvas);

  // Movimiento con el ratón (suavizado)
  const raton = { x: 0, y: 0, sx: 0, sy: 0 };
  window.addEventListener('pointermove', (e) => {
    raton.x = e.clientX / window.innerWidth - 0.5;
    raton.y = e.clientY / window.innerHeight - 0.5;
  }, { passive: true });

  const matriz = (rx: number, ry: number, rz: number) => {
    const cx = Math.cos(rx), sx = Math.sin(rx), cy = Math.cos(ry), sy = Math.sin(ry), cz = Math.cos(rz), sz = Math.sin(rz);
    // R = Rz * Ry * Rx (objeto → mundo). Se pasa R en filas = R^T en columnas (mundo → objeto).
    const r = [
      [cz * cy, cz * sy * sx - sz * cx, cz * sy * cx + sz * sx],
      [sz * cy, sz * sy * sx + cz * cx, sz * sy * cx - cz * sx],
      [-sy, cy * sx, cy * cx],
    ];
    return new Float32Array([r[0][0], r[0][1], r[0][2], r[1][0], r[1][1], r[1][2], r[2][0], r[2][1], r[2][2]]);
  };

  let visible = true, activo = false, raf = 0, t0 = performance.now();
  let muestras = 0, acumulado = 0, ultimo = performance.now();

  const dibujar = (ahora: number) => {
    const t = (ahora - t0) / 1000;
    raton.sx += (raton.x - raton.sx) * 0.05;
    raton.sy += (raton.y - raton.sy) * 0.05;
    const flotar = Math.sin(t * 0.8) * 0.05;
    gl.uniform2f(uRes, w, h);
    gl.uniform1f(uTime, t);
    gl.uniformMatrix3fv(uM, false, matriz(
      estado.rx + raton.sy * 0.45 + Math.sin(t * 0.5) * 0.06,
      estado.ry + raton.sx * 0.6 + Math.sin(t * 0.37) * 0.1,
      estado.rz,
    ));
    gl.uniform1f(uScale, estado.escala);
    gl.uniform2f(uOff, estado.ox, estado.oy + flotar);
    gl.uniform1f(uWob, estado.ondula);
    canvas.style.opacity = String(estado.opacidad);
    gl.clearColor(0, 0, 0, 0);
    gl.clear(gl.COLOR_BUFFER_BIT);
    if (estado.opacidad > 0.01) gl.drawArrays(gl.TRIANGLES, 0, 3);

    // Calidad adaptativa
    const dt = ahora - ultimo; ultimo = ahora;
    if (dt < 200) { acumulado += dt; muestras++; }
    if (muestras === 40) {
      const media = acumulado / muestras;
      if (media > 24 && calidad > 0.4) { calidad = Math.max(0.4, calidad - 0.12); ajustar(); }
      else if (media < 14 && calidad < (movil ? 0.75 : 1)) { calidad = Math.min(movil ? 0.75 : 1, calidad + 0.06); ajustar(); }
      muestras = 0; acumulado = 0;
    }
    if (activo) raf = requestAnimationFrame(dibujar);
  };

  const arrancar = () => { if (activo || !visible || document.hidden) return; activo = true; ultimo = performance.now(); raf = requestAnimationFrame(dibujar); };
  const parar = () => { activo = false; cancelAnimationFrame(raf); };

  new IntersectionObserver(([e]) => { visible = e.isIntersecting; visible ? arrancar() : parar(); }).observe(canvas);
  document.addEventListener('visibilitychange', () => (document.hidden ? parar() : arrancar()));

  return {
    arrancar,
    parar,
    /** Dibuja un único fotograma (modo sin animaciones). */
    fotograma: () => { parar(); dibujar(performance.now()); parar(); },
  };
}
