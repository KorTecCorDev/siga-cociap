# UI/UX: wayfinding, dashboard y componentes

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Mejoras de UI/UX (sesión 3)

### Sticky columns en tablas docente
- **`/docente/calificaciones/...`** — columnas N° y Apellidos congeladas al hacer
  scroll horizontal (`.col-num` sticky left:0, `.col-nombre` sticky left:40px)
- **`/docente/calificaciones/.../resumen/...`** — mismo patrón; además:
  - `.col-criterio` min-width:80px, `.col-conclusion` min-width responsivo (200/260/320px)
  - `.fila-pendiente` conserva su background naranja en celdas sticky
  - `.conclusion-texto` reemplaza el inline `style="font-size:12px"`
- Ambas tablas viven dentro de `.tabla-notas-wrapper` (overflow-x:auto)

### Componentes SASS nuevos/extendidos
- **`_buttons.scss`** — `.btn-group { display:inline-flex; gap:$spacing-sm }` reutilizable
- **`_tables.scss`** — `.tabla-notas-wrapper`, `.tabla-resumen` con sticky columns y
  `.conclusion-texto`
- **`_admin.scss`** — `.usuario-avatar` (círculo con iniciales coloreado por rol),
  `.td-usuario`, `.td-acciones`, `.form-grid`, `.form-section-title`, `.form-actions`,
  `.select-rol`, `.text-danger`, `.text-sm`, `.fila-inactiva`
- **`_boleta-digital.scss`** — todo el sistema de diseño de la boleta digital (BEM `.bd-`)

## Fixes importantes aplicados (sesión 4)
- **Comillas tipográficas en PHP** — `resources/views/admin/secciones/index.php`
  tenía comillas U+201D (`"`) en atributos HTML del botón de asignación en lugar de
  comillas ASCII U+0022 (`"`). El parser HTML las trata como texto, no como delimitadores
  de atributo → todos los `data-*` quedaban rotos → `SyntaxError` en el browser.
  **Diagnóstico:** `node -e "const src=require('fs').readFileSync('file.php','utf8');
  for(let i=0;i<src.length;i++){const c=src.charCodeAt(i);if(c===0x201C||c===0x201D)
  console.log('linea',src.substring(0,i).split('\\n').length,src[i]);}"` 
  **Fix:** `src.replace(/[""]/g, '"')` y reescribir el archivo.
- **`data-label` con guillemets** — el atributo usaba `«»` literales que `e()` no escapa.
  Corregido a `data-label="<?= e($s['grado_numero'] . $s['seccion_nombre']) ?>"` 
  (formato `1A`, `2B`, etc. — simple y sin caracteres especiales).
- **Fuentes Inter 404** — `@font-face` usaba rutas absolutas con `/siga-cociap/public/`.
  Corregido a rutas relativas `../assets/fonts/inter/` en `_typography.scss`.

## Sistema de color "wayfinding" (16/06/2026)

> Color FIJO por concepto en todo el sistema, como ayuda de orientación: hay
> docentes que trabajan en varios colegios y se saturan con mucha información;
> el color fijo deja que ubiquen el acceso sin leer.

- **Tokens en `resources/sass/base/_variables.scss`** (bloque "Wayfinding del dashboard
  docente"). Cada concepto tiene 3 variantes: `-line` (borde vivo), `-bg` (fondo tenue),
  `-ink` (título oscuro legible):
  | Concepto | `-line` | `-bg` | `-ink` |
  |---|---|---|---|
  | **Académicas / Mis cargas** (`$card-cargas-*`) | `#1e6fa8` azul | `#eef5fb` | `#1a5a8c` |
  | **Transversales / Tutoría** (`$card-tutoria-*`) | `#0d9488` teal | `#ecfbf8` | `#0f766e` |
  | **Conducta** (`$card-conducta-*`) | `#7c3aed` púrpura | `#f5f0fe` | `#6d28d9` |
  | **Nómina** (`$card-nomina-*`) | `#e07b1a` naranja | `#fef3e2` | `#b45309` |
- **REGLA:** rojo (`$color-error`) y ámbar (`$color-warning`) quedan RESERVADOS para los
  badges de estado (error/advertencia); NUNCA se usan como identidad de un acceso.
- Combinación azul↔naranja + teal/púrpura: bien diferenciable con daltonismo.
- Aplicado en `/docente/inicio` y `/director/bloqueos`. Usar estos mismos colores en
  futuras vistas para el mismo concepto.

## Dashboard del docente — cards de acceso (16/06/2026)

- **`/docente/inicio`** (`Docente\PanelController::index`) tiene 4 cards en `.dpanel-grid`:
  **Mis cargas académicas** (azul), **Tutoría — {grado}{sección}** (teal), **Conducta —
  {grado}{sección}** (púrpura) y **Nómina de matriculados** (naranja). Tutoría y Conducta
  solo aparecen si el docente es tutor del año activo (`$tutoria`/`$conducta` no nulos).
- `PanelController` ahora inyecta `ConductaModel` y calcula `$conducta` (mismo origen que
  usaba `mis-cargas`: `ConductaModel::getCierreVigente`).
- **Se eliminaron las cards largas** (`.tutoria-card`) de Tutoría y Conducta de
  `/docente/mis-cargas`; `CalificacionController::misCargas` ya NO calcula
  `$tutoria`/`$conducta` (y se quitaron de ese controlador `TransversalModel`/`ConductaModel`
  que quedaron sin uso). La vista solo lista cargas.
- **SASS** en `pages/_docente-panel.scss`: modificadores `.dpanel-card--{cargas,tutoria,
  conducta,nomina}` (borde + título por color). El **fondo tenue (`-bg`) SOLO se pinta
  cuando la card lleva un estado activo** (`--cerrado`/`--disponible`/`--progreso`); libre
  de estados = fondo blanco (menos ruido). Como Mis cargas y Nómina nunca llevan estado,
  quedan blancas; Tutoría/Conducta siempre traen estado, así que muestran su tinte.

## Nómina del docente — buscador destacado + íconos de sección (22/06/2026)

`/docente/nomina` (`Docente\PanelController::nomina`, vista `resources/views/docente/nomina.php`).
- **El buscador es la acción principal y va PRIMERO**, destacado: card `.nomina-buscar`
  (borde de marca + acento lateral `$brand-accent` + fondo `$brand-light` + sombra),
  campo con ícono de lupa (patrón `.buscador__campo`/`.buscador__icono`/`.buscador__input`),
  `autofocus`. El panel **Imprimir nómina** baja al final como acción secundaria
  (`.nomina-imprimir-card`: fondo gris, borde punteado).
- **Cada sección lleva su ícono de título** (`.nomina-seccion-ico`, mask + currentColor):
  Buscar → `lupa-look.svg`; Imprimir → `printer.svg`.
- El `h1` lleva el ícono de concepto wayfinding (ver sección siguiente).
- SASS en `pages/_docente-panel.scss`. JS `public/js/nomina.js` intacto (todo por ID).

## Wayfinding en el h1 de cada vista del docente (22/06/2026)

> Continuidad card→vista: el `h1` de cada vista a la que lleva una card del
> dashboard del docente lleva el MISMO glifo que la card, pequeño (`1.05em`) y
> antes del texto, tintando SOLO el ícono con el tono `-ink` del concepto (texto
> neutro = subtil). Refuerza el [sistema wayfinding por color](#sistema-de-color-wayfinding-16062026).

- **Punto único de verdad** en `pages/_docente-panel.scss`, junto al mapa de las
  cards (`.dpanel-card--*`): clase base `.page-title--wf` (icono vía `::before`,
  mask con `var(--wf-icon)` tintado con `var(--wf-ink)`) + modificadores por concepto.
- **Mapa concepto → ícono → color** (los `h1` y las cards comparten glifo):
  | Concepto | Modificador h1 | Ícono | `-ink` |
  |---|---|---|---|
  | Mis cargas | `page-title--cargas` | `book-bookmark` | azul |
  | Tutoría | `page-title--tutoria` | `users-group-rounded` | teal |
  | Conducta | `page-title--conducta` | `smile` | púrpura |
  | Nómina | `page-title--nomina` | `childs-students` | naranja |
  | Orden de mérito | `page-title--merito` | `medal-ribbon-star` | naranja (familia Nómina) |
  | Ranking por sección | `page-title--ranking` | `ver-resumen` | naranja (familia Nómina) |
- Aplicado en `mis-cargas`, `tutoria`, `conducta`, `nomina`, `orden-merito`
  (selector compartido: el modificador se elige por `$rutaBase`) y las dos vistas
  de periodo de mérito/ranking.
- **Colisión de íconos resuelta**: `users-group-rounded` quedó EXCLUSIVO de Tutoría.
  La card Nómina y su acción "Ver nómina" usan `childs-students`; "Buscar estudiante"
  usa `lupa-look`.
- **REGLA de coexistencia de colores (opción A — por rol/forma):** el color de
  CONCEPTO (wayfinding) vive solo en el chrome (cards + `h1`); el color de SECCIÓN
  (`--sec-*`, paleta por letra A-F en `_dashboard.scss`) vive en chips/anclas
  (`.seccion-ancla`). Comparten varios hex (Conducta púrpura == Sección B,
  Tutoría teal == Sección C), pero NO se confunden porque cada sistema vive en
  una forma/posición distinta. NUNCA pintar un `h1` con color de sección ni un
  chip de sección con color de concepto.

## Mis cargas — ancla de sección monocroma + jerarquía de grado (24/06/2026)

En `/docente/mis-cargas` (vista `mis-cargas.php`, SASS `pages/_dashboard.scss`):
- **Ancla de sección**: la **letra es el identificador** (única dentro del grado,
  por eso va grande y sola). Pasó a **monocromo** en la familia "Mis cargas" (azul
  `$card-cargas-*`); se quitó la paleta por letra del ancla y el grado/nivel
  repetidos. Render `"Sección A"` (rótulo + recuadro de la letra, sin duplicar la
  letra). La paleta por letra `.seccion-ancla--{letra}` (`--sec-*`) se **ELIMINÓ**
  (24/06): el acordeón del ranking (`.merito-seccion-acordeon` en
  `/docente/ranking-seccion`) era su único consumidor y ahora usa el MISMO monocromo
  azul de "Mis cargas" (`$card-cargas-*`). Distinción de sección = la LETRA, nunca
  color por letra (confunde con el wayfinding por concepto).
- **Bloque de grado** (`.card--grado` + `.grado-head`): se diferencia por
  **jerarquía tipográfica** (nivel como antetítulo + grado en grande), NO por color
  (los grados son secuenciales; un tinte por grado competiría con el azul de la
  página).
- **Ranking por sección estandarizado (24/06):** `ranking-seccion-periodo.php` ya no
  aplica `seccion-ancla--{letra}`; `.merito-seccion-acordeon` (`pages/_docente-panel.scss`)
  usa `$card-cargas-*` fijo. Misma lectura que mis-cargas (letra = identificador).

## Documentos en ventana nueva — botón "Cerrar" autocerrable (02/07/2026)

> **Bug corregido:** todos los documentos que abren en ventana aparte (boletas,
> reportes A4, nóminas) tenían un botón **"Volver"** que, en móvil, en vez de
> regresar a la ventana original **creaba una copia** del origen y las pestañas
> se **acumulaban** → lentitud en celulares. Reemplazado por **"✕ Cerrar"** que
> cierra la ventana de verdad. Commit `8d56103` (ya en `dev` y `main`).

### Causa raíz
- Los documentos se abren con `<a target="_blank" rel="noopener">` → la pestaña la
  crea el NAVEGADOR (no un script). Una pestaña recién abierta con `_blank` tiene
  `history.length === 1`, así que el "Volver" (`history.back()`) no aplicaba y caía
  a su *fallback*: **navegar al `document.referrer`** → una copia del listado de
  origen. La pestaña original seguía viva debajo. Cada documento abierto desde ahí
  dejaba otra pestaña `_blank` → acumulación.
- `window.close()` (que el *fallback* también intentaba) está **bloqueado** por el
  navegador cuando `window.opener` es `null`, y `rel="noopener"` justamente lo
  anula. Por eso "Cerrar" solo funciona si controlamos **cómo se abre** la ventana.

### Solución (Opción A — decidida por el usuario)
Una ventana **abierta por script** (`window.open`) SÍ es autocerrable con
`window.close()` desde su propio botón, aunque no se conserve el handle. Todas las
páginas son del **mismo origen** (el riesgo de `noopener` aquí es nulo).

- **Origen — interceptor global en `resources/js/app.js`** (cargado por
  `layouts/app.php` en toda vista interna): delegación de clic que captura
  `a[target="_blank"]` **del mismo origen**, hace `e.preventDefault()` +
  `window.open(href, '_blank')`. Respeta clic-medio/ctrl/cmd/shift/alt (abrir en
  2.º plano a voluntad), ignora `href="#"`, `download`, `mailto:`/`tel:`/`javascript:`
  y enlaces externos. **NO edita ninguno de los ~15 enlaces**; `target="_blank"`
  queda como *fallback* sin-JS. Hoy TODOS los `_blank` internos son exactamente
  estos documentos, así que el interceptor global == el conjunto objetivo.
- **Destino — botón "✕ Cerrar"** (reemplaza "Volver") en los **dos únicos** layouts
  de documento:
  - `layouts/print.php` (`.btn-boleta--cerrar`, id `btnCerrarDoc`) — cubre boleta
    imprimible, traslado, orden de mérito, desempates, nómina detallada, cuadro
    resumen, horario, y las 3 de admin boletas (`vista-previa`/`boletas-alumno`/`archivar`).
  - `boleta/digital.php` (id `bdCerrar`, ícono X) — boleta digital.
- **JS de cierre** en `print-fit.js` y `boleta-digital.js`: `window.close()` y, si
  quedó bloqueado (ventana abierta a mano, no por script), *fallback*
  `history.back()` → referrer del mismo origen → `base-url`.
- **SASS:** modificador `.btn-boleta--volver` → `.btn-boleta--cerrar` en
  `pages/_boleta.scss` (misma apariencia). Recompilar con `gulp build`.

### Alcance verificado
- Inventario de destinos: **todos** caen en `layouts/print.php` o
  `layouts/digital.php` (el botón solo vivía en esos 2 sitios) → el cambio es
  centralizado. No hay otras vistas de impresión con back propio.
- **Pendiente de validar en móvil real** (Chrome Android / Safari iOS): abrir
  varias boletas seguidas y confirmar que "✕ Cerrar" cierra la pestaña y no se
  acumulan. Es comportamiento de ventanas del navegador, no simulable por CLI.

## Card del tutor renombrada: "Competencias Transversales" (07/07/2026)

La card del dashboard docente que abre `/docente/tutoria` (conclusiones + cierre
de TIC/GAMA) pasó de titularse "Tutoría — {grado} {sección}" a **"Competencias
Transversales — {grado} {sección}"** (`docente/inicio.php`), y su subtítulo dejó
de repetir el nombre. Motivo: con Ética y Valores, "Tutoría (TOE)" es ahora una
carga académica más en mis-cargas y el rótulo viejo era ambiguo. La página
destino ya se titulaba "Tutoría — Competencias Transversales" (sin cambios); la
clase `.dpanel-card--tutoria` y el color teal de wayfinding se conservan.
