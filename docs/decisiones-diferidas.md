# Decisiones de diseño diferidas y planes cerrados

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Suspensiones / disciplina — decisión de diseño (02/07/2026)

> **NO implementado.** Solo se fija el PRINCIPIO de diseño para no cometer un error
> estructural cuando se construya. El colegio maneja faltas al reglamento con
> suspensiones (1 a 4 días, máx.) y, al extremo, expulsión. El registro de
> sanciones, la expulsión y el comportamiento de grilla se diseñarán JUNTOS como un
> módulo disciplinario propio (diferido por el usuario).

### El principio: las suspensiones NO se manejan con `desactivado`
- `desactivado` significa *"el estudiante YA NO está matriculado"* (baja
  administrativa / traslado de salida): apaga el login del apoderado
  (`desactivarUsuarioDeEstudiante`) y las boletas públicas de TODOS los periodos
  (`boletas_publicas.activa=0`), y lo saca del orden de mérito. Una suspensión es lo
  contrario: el alumno **sigue matriculado** y cumple una medida **temporal**.
- Una sanción exige lo que `desactivado` NO puede modelar: tipo de medida, duración
  (inicio/fin, nº días), falta/artículo del reglamento, autoridad que la impone,
  acta, estado (vigente/cumplida/anulada) e **historial acumulado** (varias sanciones
  por año). `desactivado` solo tiene UN `motivo_estado` de texto y un único estado.
- **Dominio propio, separado** del ciclo de vida de la matrícula. La **nota de
  conducta es INDEPENDIENTE**: una sanción NO toca la nota (el registro disciplinario
  va aparte).

### Requisito ya definido para el módulo futuro
- **El alumno suspendido debe DESAPARECER de la grilla de calificaciones por criterio**
  y reaparecer cuando vuelve a matrícula `Aprobado`. Esto es un comportamiento NUEVO:
  hoy `Docente\CalificacionController::getAlumnosSeccion` incluye `aprobada`,
  `pendiente` y `desactivado`; el ÚNICO que se cae de la grilla es `trasladado`.
  Implica un **estado temporal propio** (conceptualmente `suspendido`), **NO**
  `desactivado`.

### Tensiones a resolver ANTES de codificar (cuando se retome)
1. **Disparador:** el estado "suspendido" debe nacer del REGISTRO de la sanción
   (fechas/tipo/autoridad). Sin ese registro sería un estado huérfano — el mismo
   defecto que `desactivado`.
2. **Duración corta vs. grilla:** una suspensión de 1-4 días entrando/saliendo de la
   grilla genera huecos (si el docente califica ese día, el suspendido queda sin
   fila → recalificar al volver). Definir si "salir de la grilla" aplica a
   suspensiones cortas o solo a separaciones largas/indefinidas.
3. **No destruir lo registrado:** matrícula y notas persisten (la suspensión es
   temporal); solo se OCULTAN de la grilla y reaparecen intactas al volver.
- **Expulsión:** único caso donde el desenlace SÍ dispararía una baja de matrícula
  (`desactivado`/traslado), pero como CONSECUENCIA registrada aparte, no como el
  registro mismo. A decidir con el módulo completo.

### Interino aceptado (parche, no solución)
Hasta tener el módulo, se PUEDE usar la desactivación de matrícula como paño
temporal (baja reversible con motivo explícito, p. ej. `"Suspensión disciplinaria
N días (dd/mm–dd/mm)"`), **entendiendo sus límites**: (a) NO saca al alumno de la
grilla (sigue apareciendo para calificar); (b) apaga el login del apoderado y las
boletas de todos los periodos; (c) es manual y sin traza disciplinaria. Es un
parche consciente, no el comportamiento correcto.

## CAPACITACIÓN docentes 08/07/2026 — PLAN CERRADO (03/07/2026)

> Capacitación + presentación oficial de SIGACOCIAP. Estrategia debatida el
> 02-03/07; plan operativo CERRADO el 03/07. **No se construye nada nuevo** y
> la BD de producción NUNCA recibe datos de prueba.

### Plan final
- **Dos turnos:** primaria 12:30pm-2:00pm; secundaria 7:30pm-9:00pm (aprox.).
- **Demos del flujo completo** (aprobar → bloquear → cerrar bimestre → boleta):
  las proyecta el desarrollador desde su **entorno de desarrollo** (BD de
  desarrollo), nunca sobre producción. Los docentes no conocen ese entorno.
- **Práctica de los docentes en producción = TRABAJO REAL:** crean sus criterios
  y notas reales del II Bim. Sin notas de prueba → **sin backup/restore, sin
  ventana de mantenimiento**; primaria puede seguir digitando esa misma noche.
- **Boleta final demostrada con bimestres CERRADOS** (hoy solo el I Bim). El
  II Bim de producción permanece `activo` todo el día → cero fuga a familias.
- **Una sola URL para docentes** (producción). Si un docente aprueba/bloquea por
  error, se revierte con el desbloquear del director (cascada), sin restore.

### HALLAZGO técnico permanente — la fuga ocurre al CERRAR, no al bloquear
- La boleta pública usa `armar(..., soloOficiales=true)` que filtra periodos a
  `estado='cerrado'` (`BoletaModel::getPeriodosDelAnio`). Bloquear/aprobar
  competencias NO expone nada mientras el bimestre siga `activo`; ponerlo en
  `cerrado` lo expone al instante.

### Recomendación DIFERIDA — compuerta de publicación (C)
- Desacoplar "cerrar bimestre" (interno) de "publicar boletas a familias" (acto
  de dirección). **NO se construyó** (innecesaria para el taller con el plan final).
  **Retomar ANTES del cierre real del II Bim:** sin ella, cerrar publica al
  instante. También quedaron diferidos el modo mantenimiento (B) y el staging
  `dev.sigacociap.net`.
- ⚠️ **El diseño original de esta recomendación (flag `periodos.publicado` + un
  `AND` en `soloOficiales`) quedó OBSOLETO el 20/07/2026** y NO debe implementarse:
  `soloOficiales` ya no existe (lo reemplazó el parámetro `$datos` del Hito A) y un
  booleano no alcanza, porque la publicación es **por nivel y con fecha/hora**
  (primaria se entrega un día antes que secundaria). **Plan vigente en
  `docs/ESTADO.md` → "Compuerta de publicación de boletas".**

### Reencuadre de la fecha límite (guion del taller)
- Mostrar el flujo completo revela que las boletas se arman al instante → NO
  omitirlo; reencuadrar: el cuello de botella se movió a la **completitud de
  TODOS los docentes** (un docente tarde deja la boleta incompleta y congela a
  toda la sección — tutor y transversales dependen de que todos cierren), y
  **publicar es decisión institucional**, no consecuencia del último clic.
  Táctica: mostrar una boleta incompleta y el tablero de completitud X/Y.
