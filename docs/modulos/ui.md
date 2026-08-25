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
  conducta,nomina}` (borde + título por color). Todas las cards van sobre **fondo blanco**;
  el color de concepto vive solo en el borde izquierdo y el título.
- **CORRECCIÓN (31/07/2026):** este doc afirmaba que el fondo tenue (`-bg`) se pintaba
  cuando la card llevaba estado activo. **No es cierto hoy**: las clases
  `.dpanel-card--{cerrado,disponible,progreso}` que la vista sigue emitiendo **no tienen
  ninguna regla** ni en `pages/_docente-panel.scss` ni en `public/css/app.css` (verificado
  sobre el CSS compilado). Son ganchos inertes. Las reglas `&--progreso/&--disponible/
  &--cerrado` que existen en `pages/_dashboard.scss:929` pertenecen a `.tutoria-card`, la
  card larga que se retiró de `/docente/mis-cargas`. **En `/docente/inicio` el ÚNICO
  elemento que comunica estado es el badge** (ver sección del semáforo, más abajo).

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

### Quién sale en el buscador — SOLO matrículas `aprobada` (10/08/2026)

Decisión del usuario. El buscador pasó de listar `aprobada + pendiente + desactivado`
a listar **solo `aprobada`**, el mismo criterio que ya usaba la nómina imprimible.
Medido: de **525 a 521** (salen las 3 matrículas `pendiente` del año y 1 `retirado`;
los `trasladado` ya estaban fuera).

🔴 **Esto NO saca a nadie de la evaluación, y la distinción es el punto entero:**

| | Quién aparece |
|---|---|
| **Rosters** (grilla de notas, asistencia, conducta, transversales) | `aprobada` + `pendiente` + `desactivado` — **sin cambios** |
| **Buscador de la nómina** (consulta) y nómina imprimible | solo `aprobada` |

- Un roster es donde se **escribe**: excluir de ahí a un `pendiente` significa que
  nadie puede calificarlo, que su boleta sale con `0 faltas` —dato falso, no ausente—
  y que su evaluación incompleta **abortaría el cierre**. Es el invariante de
  `CLAUDE.md` y el motivo del fix del 04/08. **Aquí solo se oculta una card de
  resultado**: ningún dato cambia.
- **Verificado con `verif_roster_asistencia.php` tras el cambio: OK**, y su bloque 3
  sigue listando a las 3 `pendiente` (696, 695, 693) como parte del roster.
- ⚠️ **Contrapartida conocida:** un `desactivado` por DEUDA sí se califica y ya no
  saldrá en el buscador. Hoy no muerde —los 11 desactivados del año son
  trasladados/retirados, fuera de la evaluación—, pero es el precio de filtrar por
  estado en esta pantalla.

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

## Semáforo de estado de las cards del tutor (31/07/2026)

> Las cards **Competencias Transversales** y **Conducta** de `/docente/inicio` pintaban
> de ÁMBAR todo lo que no estuviera cerrado, así que "todavía no depende de ti" y "te
> toca registrar" se veían idénticos. Ahora el color distingue **de quién depende la
> acción**.

**REGLA — tres estados, un solo significado por color:**

| Color | Badge | Significado | Transversales | Conducta |
|---|---|---|---|---|
| **Gris** | `badge--espera` | **No depende del tutor todavía** | `Bloqueadas X de Y` (faltan docentes de área) | `En espera` (RA no bloqueó) |
| **Ámbar** | `badge--warning` | **Le toca al tutor** | `Disponible — N conclusión(es) pendiente(s)` · `Disponible para cerrar` | `Disponible` |
| **Verde** | `badge--activo` | **Terminado** | `Cerrado el dd/mm/aaaa` | `Cerrada el dd/mm/aaaa` |

- **Punto único:** `$tBadge` / `$cBadge` (dos `match()` en `resources/views/docente/inicio.php`,
  junto al cálculo de `$tEstado`/`$cEstado`). El badge se elige ahí, nunca inline en el HTML.
- **`badge--espera`** (`components/_cards.scss`) es NUEVO y separado de `badge--sin-notas`
  aunque compartan fondo `#f1f5f9`: significan cosas distintas y el texto va un paso más
  oscuro (`#475569` vs `#64748b`) porque en 11px el gris claro queda al límite del
  contraste AA.
- **"Disponible para cerrar" se queda en ÁMBAR a propósito.** El verde tiene UN solo
  significado: "esta card ya no me pide nada". Si se pintara verde al terminar de
  registrar, el docente asumiría que acabó y dejaría el bimestre sin cerrar (y el KPI
  "Avance del bimestre" nunca llegaría a 100%, porque cuenta `cierre`/`cerrado`, no los
  registros — ver `PanelController::index`).
- **NO usar rojo para "todavía no te toca"** (evaluado y descartado el 31/07/2026):
  1. invierte la jerarquía de atención — el rojo jala la vista hacia la única card donde
     el docente **no puede actuar**, y deja la accionable por debajo;
  2. es el estado NORMAL al abrir cada bimestre, así que todo tutor vería sus dos cards
     en rojo sin haber hecho nada mal → el rojo se desgasta;
  3. en esa misma pantalla el rojo YA significa urgencia (`dpanel-kpi__num--err` en
     "Cargas sin criterios" y en `diasCierre <= 3`, `badge--error` en Pendientes).
- **Los textos NO cambiaron** — solo el color. La lógica de estados
  (`PanelController::index`, líneas de `$tutoria`/`$conducta`) quedó intacta.

### Asimetría conocida (deuda consciente)
En **Conducta**, el ámbar (`Disponible`) NO distingue "aún no registro ninguna nota" de
"ya registré todo, solo falta cerrar", porque **ese dato no existe**: `PanelController`
solo trae `cierre` y `cerrado`. Para diferenciarlo haría falta un método nuevo en
`ConductaModel` que cuente matrículas del roster con `nota_tutor IS NULL`
(`completitudSeccion()` NO sirve: mide las respuestas del auxiliar, no la nota del tutor).
Se decidió **no implementarlo por ahora** (31/07/2026). Transversales sí distingue, vía
`$tutoria['pendientes']`, pero ambos sub-casos comparten el ámbar por la regla de arriba.

## Modales: cerrar descarta lo no guardado (04/08/2026)

**Síntoma (detectado probando `/admin/curriculum`):** se abría "Editar área", se
tecleaba algo, se pulsaba **Cancelar** y al volver a abrir el modal seguían los valores
tecleados — como si el cambio se hubiera guardado, cuando no se envió nada a la BD.

**Causa:** `Modal.cerrar()` (`resources/js/app.js`) solo ocultaba el overlay. Los modales
se renderizan **una sola vez** con los valores de la BD en el atributo `value`; al
escribir se cambia la **propiedad** del control, no el atributo, así que el DOM conserva
lo tecleado indefinidamente. No era un fallo de servidor: la BD nunca se tocó.

**Arreglo:** `Modal.cerrar()` hace `form.reset()` sobre cada `<form>` del overlay, dentro
de `cleanup()` (no al iniciar el cierre, para que los campos no parpadeen durante la
animación de salida). `reset()` devuelve cada control a su valor por defecto, que es
exactamente el que pintó el servidor.

**Por qué es seguro tocarlo en el `Modal` genérico** — inventario completo de los 7
modales del sistema al momento del cambio:

| Modal | Origen de sus valores | Efecto del reset |
|---|---|---|
| Currículo: área, subárea, competencia | servidor (`value="..."`) | **corrige el bug** |
| `modalFechas`, `modalReabrir` (`anio-academico.js`) | JS, reescritos en CADA apertura | ninguno |
| `modalTutor` (`secciones.js`) | JS: `sel.innerHTML` se rehace en CADA apertura | ninguno |
| `modalCierre` (`director/anios/show.php`) | sin formulario | ninguno |

El envío AJAX de `modalTutor` llama a `Modal.cerrar()` y acto seguido a
`window.location.reload()`, así que el reset no puede revertir nada ya guardado.

⚠️ **Regla para modales nuevos:** si un modal se puebla por JS, debe hacerlo en **cada**
apertura, nunca una sola vez al cargar la página. `Modal.cerrar` asume esa invariante.

## Orden alfabético en español: la Ñ va después de la N (04/08/2026)

**Síntoma (detectado en la grilla de notas de 4° A primaria):** ÑIQUEN PAJUELO aparecía
**antes** que NOLASCO REYES.

**Causa:** las columnas de `personas` son `utf8mb4_unicode_ci`, colación que equipara
**Ñ ≡ N** en el nivel primario de comparación. Al comparar "ÑIQUEN" con "NOLASCO" la Ñ
pesa como N, empatan en la primera letra y decide la segunda: **I < O**, así que ÑIQUEN
salía primero. En el alfabeto español la Ñ es letra propia y va **después** de la N, o
sea NOLASCO → ÑIQUEN. No era un fallo del roster ni de la grilla: era el `ORDER BY`.

**Arreglo:** `COLLATE utf8mb4_spanish_ci` en el ordenamiento, con punto único en
`app/Helpers/helpers.php`:

```php
const COLLATE_ES = 'COLLATE utf8mb4_spanish_ci';
function orden_alfabetico(string $alias = 'p', int $campos = 3): string

// uso:  ORDER BY " . orden_alfabetico('p') . "
//       ORDER BY n.id, g.numero, " . orden_alfabetico('per') . "
```

Aplicado a los **30 `ORDER BY`** de 19 archivos: grillas de notas/asistencia/conducta,
tutoría, boletas públicas, nóminas, exportación SIAGIE, usuarios, apoderados,
exoneraciones, rectificaciones y Centro de Control.

**Por qué NO se cambió la colación de las columnas** (la alternativa obvia, que habría
arreglado los 30 sitios con un `ALTER TABLE`): la colación no gobierna solo el orden,
también el `=` y el `LIKE`. Hoy, gracias a `unicode_ci`, buscar "NUNUVERO" encuentra a
**NUÑUVERO** — y la gente teclea sin ñ. Además arrastra riesgo de
`Illegal mix of collations` contra columnas de otras tablas que siguen en `unicode_ci`.
El `COLLATE` en el `ORDER BY` cambia el orden y **nada más**.

`spanish_ci` y no `spanish2_ci`: la segunda trata CH y LL como letras independientes,
criterio que la RAE abandonó en 1994.

**Impacto real medido:** 58 personas tienen Ñ en su nombre, pero comparando las 23
secciones con ambas colaciones **solo cambia 4° A de primaria** — en las demás la Ñ está
en medio del apellido y no compite contra una N en la misma posición.

**Lo que NO se ve afectado:**
- **Actas SIAGIE:** `MatcherEstudiantes::normalizar` mapea `'Ñ' => 'N'` y casa por código
  o por nombre normalizado **en PHP**, nunca por posición de fila.
- **Orden de mérito:** los puestos ya no se desempatan por apellido (la cascada termina
  en `m.id`), así que el snapshot de B1 no se mueve.

⚠️ **Regla para consultas nuevas:** todo `ORDER BY` sobre nombres de personas usa
`orden_alfabetico()`. Nunca ordenar por `p.apellido_paterno` a secas ni por un alias
`CONCAT(...)` — el alias hereda la colación de la columna y reintroduce el problema (era
el caso de `ControlOperativoModel::alertasEvaluacionIncompleta`, que ordenaba por `alumno`).


## Zona de resultado: el hover no puede borrarla (25/08/2026)

`.col-resultado` marca las columnas **calculadas** (promedio, nota final, literal)
para que no se confundan con las de origen. Vive en `components/_tables.scss` y la
usan **seis vistas**: `consulta-notas/{conducta,transversales,_tabla}` y
`docente/{conducta,resumen-competencia,tutoria}`.

- 🔴 **Su fondo era `#f8fafc`, el MISMO valor literal que `$bg-secondary`**, que es
  el color del hover de fila. Al pasar por una fila, toda ella tomaba ese gris y la
  zona de resultado **desaparecía** — justo cuando se está señalando la fila, y
  justo la función para la que la clase existe.
- **La solución es el ESCALÓN, no un color**: en hover la zona sube un tono
  (`#eef2f7`, el que ya usa `thead .col-resultado`) y la diferencia relativa se
  conserva en los dos estados. No entra ningún color nuevo al sistema.
- ⚠️ **Va por ESPECIFICIDAD, no por orden**: `.tabla-{notas,resumen} tr:hover td`
  es **(0,2,2)** y la regla de la zona es **(0,3,2)**. Y se escriben **las dos
  familias** de tabla porque cada una define su propio hover; un
  `tr:hover .col-resultado` suelto (0,2,1) no bastaría.
- **La zona de resultado CIERRA la fila.** En `consulta-notas/conducta.php` el
  orden pasó a `N° | Nombre | Sí/total | ┃ Nota auxiliar | Nota tutor | Final |
  Literal`: el separador `col-resultado--inicio` abre un bloque que no debe quedar
  interrumpido por una columna suelta a su derecha. La regla vale para **las dos
  ramas** de esa vista (el bimestre legado también abre su zona con el separador).
- Verificado en `database/verificaciones/verif_zona_resultado.php`, que mide la
  **propiedad** —que el escalón sobreviva al hover— y no un color concreto: fijar
  el valor convertiría cualquier retoque de la paleta en un fallo.

### `.tabla-pie`: el pie de grilla, formalizado (25/08/2026)

Contenedor estándar de lo que va **debajo** de una tabla: leyendas, notas al pie,
totales en texto. Dentro va `.tabla-pie__leyenda` con sus `__item`.

- 🔴 **VA FUERA DE `.tabla-notas-wrapper`, NUNCA DENTRO.** El wrapper es el área de
  scroll (`overflow-x: auto`). Un pie metido ahí falla de **tres** formas a la vez:
  se **desplaza** con la tabla al hacer scroll horizontal y se va de la pantalla,
  queda **pegado al borde** sin margen, y si la grilla está en un `.card` —que
  lleva `overflow: hidden` con esquinas redondeadas— sale **recortado** por la
  curva. Los tres pasaron en la grilla de conducta.
- El pie es **hermano** del wrapper, no su hijo:
  `<div class="tabla-notas-wrapper"><table>…</table></div>` + `<div class="tabla-pie">`.
- `--suelto` para cuando no hay card alrededor (quita borde y fondo).

### `.tabla-pie__leyenda`: una sola leyenda para las grillas de datos

Explica bajo la tabla lo que las cabeceras solo dicen con `title` — un tooltip **no
existe en móvil ni para quien navega con teclado**, que es justo donde trabajan los
auxiliares. Vive en `components/_tables.scss`, con las tablas.

- La usan el partial de asistencia (F/FJ/T/TJ) y la grilla de conducta (las tres
  notas y el `Sí / total`). Nació como `.asistencia-leyenda` el mismo día y se
  extrajo al raíz **antes de que hubiera una segunda copia**.
- 🔴 **NO se llama `.tabla-leyenda`, y el motivo importa.** Ese nombre se usó unas
  horas y **ya estaba ocupado**: `pages/_registro-cierre.scss` lo tiene para una
  `<table>` de los imprimibles (`admin/conducta/imprimir.php`). Como ese parcial se
  importa **después**, el `display:flex` de la de pantalla caía sobre la tabla del
  papel y el `font-size: 6.5pt` del papel sobre las leyendas de pantalla: rompía en
  las **dos** direcciones y sin ningún error visible. Antes de bautizar una clase,
  buscarla en TODO `resources/sass/` — y buscarla también anidada (`&__x`), que es
  como se escapó `competencia-card__codigo` el mismo día.
- Cada página aporta solo su modificador (p. ej. `--registrada` en asistencia).
  Si una hoja de `pages/` vuelve a declarar `display`/`gap`/`color` para una
  leyenda propia, es la copia que se quería evitar.
- ⚠️ **Esto NO gobierna todas las clases `*-leyenda` del sistema**: las de boleta
  impresa, horario y el donut de bloqueos son de otro contexto y no se unifican.


## El chip de código, y su modificador `--solo` (25/08/2026)

`.competencia-card__codigo` es **el** chip de código del sistema. Su nombre está
anclado a `competencia-card` por historia, pero 4 de sus 6 usos ya estaban fuera de
ese bloque: el proyecto lo trata como global.

Marca el código de **competencias** (`codigo_minedu`: C1…C57) y el de **criterios
de conducta** (`criterios_conducta.codigo`, migración 056), que son numeraciones
distintas y no se cruzan — cada una vive en su pantalla.

- **`--solo`**: el chip lleva `margin-right` porque normalmente va **delante de un
  nombre**. Cuando el código va solo —una cabecera de columna— ese margen lo
  descentra. El modificador lo quita y aprieta el relleno.
- ⚠️ **Al usarlo en una cabecera, revisar el `min-width` de la columna**: el chip
  trae su propio relleno. En la grilla de conducta hubo que subir
  `th.conducta-th-crit` de 56 a 64 px, o el chip de `C10` tocaba los bordes.
- ⚠️ **Buscarlo en el SCSS por su bloque padre.** Está escrito como `&__codigo`
  anidado dentro de `.competencia-card`, así que `grep "competencia-card__codigo"`
  sobre `resources/sass/` **no lo encuentra** — y hay una copia idéntica en
  `components/_dashboard.scss`, que **no se importa desde ningún sitio**. La
  vigente es la de `pages/_dashboard.scss`.

### El literal se muestra, no se insinúa con el color

En la grilla Sí/No de conducta la nota mostraba solo el **numeral**, con el literal
reducido a la clase de color (`nota-numeral--a`). Un color no es legible para quien
no distingue esos tonos, y el **imprimible oficial ya estampa `17 (A)`**: la
pantalla decía menos que el papel.

Ahora hay **dos columnas**: Nota y Literal, como en `/conducta`. 🔴 El literal se
**sumó**, no sustituyó al numeral — hay un aserto que falla si alguien «simplifica»
quitando cualquiera de los dos, y otro que comprueba sobre el **DOM** que son dos
`<td>` y no dos `<span>` en la misma celda (contar clases no distingue una cosa de
la otra).

Las dos van marcadas con **`col-resultado`**, la zona de resultado del sistema: son
columnas **calculadas** a partir de los Sí/No, igual que el promedio y el literal de
las demás grillas. ⚠️ Al aplicarla hubo que **retirar el `border-left` manual** que
`conducta-th-nota` traía para separarse de los criterios: `col-resultado--inicio` ya
dibuja ese separador, y mantener los dos daba doble línea.
