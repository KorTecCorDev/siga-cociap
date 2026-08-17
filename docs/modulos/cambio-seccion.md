# Cambio de sección a mitad de bimestre

> **Estado: PLAN DISEÑADO Y APROBADO por el usuario el 09/07/2026. NADA construido.**
> Portado a `docs/` el **17/08/2026** desde la memoria de sesión, donde vivía sin versionar.
> Verificado ese día contra el código: **la función no existe** — 0 rutas, sin
> `CambioSeccionModel`, sin tablas `cambios_seccion*` y **ningún `UPDATE` de
> `matriculas.seccion_id`** en toda la aplicación. Hoy un cambio de sección solo se hace
> a mano en la BD.
> Estado vivo y prioridades: `docs/ESTADO.md`. Módulos que toca: `matriculas.md`,
> `calificaciones.md`, `boletas.md`, `admin.md`.
>
> ⚠️ **La migración se renumeró a `053`.** El plan original reservaba la `039`, pero
> `039_areas_codigo_siagie.sql` se creó el **12/07/2026**, tres días después de escribirse
> el plan. Al retomar, verificar de nuevo cuál es el siguiente número libre.

## 1. Motivo del negocio

Cambios de sección casuales, principalmente por problemas de convivencia o conducta —que
el alumno esté cómodo—. Hoy se espera al FIN del bimestre para mover; se quiere soportar el
caso **a mitad de bimestre**.

Flujo deseado: el docente de la carga en la sección **destino** «recibe» las notas de la
sección **origen** (por carga, competencia y criterio) y **él decide, criterio por criterio**,
si convalidar —teclear la nota de origen en un criterio suyo— o calificar en limpio.

## 2. El hecho técnico que manda (raíz de todo)

**Los bloqueos (`bloqueos_competencia`) son POR CARGA, no por alumno.** `getBoletaAlumno`
cruza el bloqueo por `(carga_id, competencia_id, periodo_id)`.

Si se hace un simple `UPDATE matriculas.seccion_id`, las filas de notas de la sección origen
quedan colgadas del `carga_id` de origen. Cuando el docente de origen bloquee su competencia,
**ese bloqueo matchea también al alumno mudado** → sus notas viejas **reaparecen en la
boleta** y, si la sección destino ya calificó, la competencia sale **DUPLICADA**.

Por eso la mudanza **debe archivar y retirar** las notas activas de la sección origen. No es
un detalle de implementación: es la razón de que exista el snapshot. Ver
`docs/modulos/boletas.md` (invariante «Boleta = solo competencias bloqueadas»).

## 3. Decisiones cerradas (no re-preguntar)

1. **Identidad = la MISMA matrícula** (`UPDATE seccion_id`), mismo grado y año. El historial
   cerrado, la conducta y la asistencia siguen al alumno. Ver `matriculas.md`.
2. **Notas del bimestre ACTIVO en origen** = snapshot a nivel de criterio como referencia
   **+ DELETE de las tablas vivas** (`calificaciones` y `calificaciones_criterio`).
3. **Bimestres cerrados anteriores = intactos en la sección origen.** Siguen saliendo en la
   boleta desde la carga de origen, porque el alumno realmente estuvo allí esos bimestres.
   Sin duplicar: origen único por bimestre.
4. **Candado:** se permite mover salvo que haya ≥1 competencia **BLOQUEADA** en origen en el
   periodo activo. Las confirmadas-sin-bloquear **sí** se pueden mover. Ver
   `calificaciones.md`.
5. **Conducta activa sigue al alumno** (`conducta_respuestas` es por matrícula, no por
   sección; la cuenta el cierre de la sección destino). Ver `admin.md`.
6. **Convalidar = CON botón** en la v1 (copia el valor de origen al criterio de destino que
   el docente elija).
7. **Reversión = CON deshacer** en la v1 (restaura las notas archivadas de origen).

## 4. Modelo de datos — migración `053_cambio_seccion.sql` (solo esquema)

- **`cambios_seccion`** (el evento): `id`, `matricula_id`, `anio_id`, `periodo_id`,
  `seccion_origen_id`, `seccion_destino_id`, `motivo TEXT NOT NULL`,
  `estado ENUM('vigente','revertido') DEFAULT 'vigente'`, `movido_por`, `movido_en`,
  `revertido_por NULL`, `revertido_en NULL`. `KEY (matricula_id, periodo_id, estado)`.
- **`cambios_seccion_competencia`**: `id`, `cambio_id` FK `ON DELETE CASCADE`,
  `carga_id_origen`, `competencia_id`, `nota_numerica TINYINT NULL`,
  `conclusion_descriptiva TEXT NULL`. `UNIQUE (cambio_id, carga_id_origen, competencia_id)`.
- **`cambios_seccion_criterio`**: `id`, `cambio_id` FK `ON DELETE CASCADE`,
  `carga_id_origen`, `competencia_id`, `criterio_id_origen` FK a `criterios` **NULL**
  (queda NULL si el docente de origen lo borra después), `criterio_nombre VARCHAR(120)`
  **denormalizado**, `nota TINYINT`. `UNIQUE (cambio_id, criterio_id_origen)`.

El snapshot sirve **a la vez** como referencia visual para el docente destino y como fuente
para **restaurar** en la reversión.

## 5. Backend — `CambioSeccionModel` (nuevo)

- `seccionesDestino(matriculaId)` — secciones del mismo grado y año, distintas de la actual.
- `elegible(matriculaId)` — `estado IN ('aprobada','pendiente')`, no estar en un retorno de
  grado activo, y el candado de la decisión 4.
- `ejecutar(matriculaId, destinoId, motivo, userId)` — **TRANSACCIÓN**: INSERT en
  `cambios_seccion` → snapshot de `calificaciones` a `cambios_seccion_competencia` y de
  `calificaciones_criterio` a `cambios_seccion_criterio` (solo periodo activo) → DELETE de
  esas filas vivas → `UPDATE seccion_id`. La conducta **no se toca**. Devuelve `cambio_id`.
- `revertir(cambioId, userId)` — **TRANSACCIÓN** con guarda: rechaza si la sección destino
  tiene una competencia bloqueada del alumno en el periodo activo; restaura el snapshot a
  las tablas vivas (por `criterio_id_origen`, omitiendo con aviso si el criterio ya no
  existe); `UPDATE seccion_id` de vuelta; marca `'revertido'`.
- `getReferenciaParaCarga(matriculaId, cargaDestinoId, periodoId)` — competencias y criterios
  archivados cuya competencia pertenece a la **misma área** que la carga destino. Alimenta
  el panel del docente.
- **Convalidar NO necesita endpoint:** el botón copia el valor al input del criterio de
  destino y dispara el **autosave existente** (`/docente/calificaciones/{carga}/autosave`).
  Reusa el flujo actual completo (guardar / omisión / confirmar). Ver `calificaciones.md`.

## 6. Rutas

- `GET`/`POST` `/matriculas/{id}/cambiar-seccion` y
  `POST /matriculas/{id}/cambiar-seccion/revertir`, con `requireRole` admin /
  `registro_academico` + `validateCsrf`.
- ⚠️ **Literales ANTES del patrón `GET /matriculas/{id}`** — invariante del router.
- Docente: `formulario()` adjunta la referencia a cada alumno con cambio vigente cuyo destino
  sea la sección de la carga. **Sin rutas de escritura nuevas.**

## 7. Frontend

- `/matriculas/{id}`, card «Gestión de la matrícula»: fila desplegable «Cambiar de sección»
  (mismo patrón *disclosure* que Desactivar y Exonerar): select de destino + textarea de
  motivo `required`. Si hay un cambio vigente → bloque «Revertir cambio de sección» con el
  resumen del snapshot.
- Grilla del docente (`calificaciones.php`): panel de referencia colapsable por alumno mudado
  + botón «Convalidar → [select de criterio]» que copia el valor y guarda.
- SASS: `pages/_matriculas.scss` (`.mat-cambio-seccion-form`) y `pages/_dashboard.scss`
  (`.referencia-convalidacion`). JS: `matriculas.js` (toggle) y `calificaciones.js`
  (copiar → autosave). Recordar `gulp build`.

## 8. No-regresión (verificar al construir)

- **Boleta:** al borrar las filas activas de origen no queda match de bloqueo → sin fuga ni
  duplicado. `getBoletaAlumno` **no se toca**. Los bimestres cerrados de origen, intactos.
- **Orden de mérito:** el alumno pasa a competir en la sección destino en el periodo activo;
  los snapshots ya cerrados no cambian.
- **Conducta:** las respuestas siguen al alumno y el cierre de la sección destino las cuenta.

## 9. Casos borde ya definidos

- **Mudanzas encadenadas A→B→C:** un solo cambio `'vigente'` por matrícula y periodo; una
  mudanza nueva supersede a la anterior y la referencia es el snapshot más reciente.
- **Criterio de origen borrado tras el snapshot:** el display usa `criterio_nombre`
  (denormalizado) y la restauración lo omite con aviso.
- **No hace falta recalcular** a los demás alumnos de la sección origen: las filas son por
  alumno, y la completitud y el cierre se actualizan solos.

## 10. Orden de construcción

1. Migración `053` → 2. `CambioSeccionModel` (`ejecutar` + `revertir`) → 3. UI de la
matrícula → 4. panel de referencia y botón convalidar en la grilla del docente →
5. verificación local end-to-end → 6. documentación (`matriculas.md`, nota en
`calificaciones.md`, y `docs/ESTADO.md`).

## 11. 🔴 LO ÚNICO ABIERTO — preguntar al usuario antes de construir

**Semántica de la reversión cuando la sección destino YA cargó notas activas del alumno.**

- **Propuesta:** archivarlas **simétricamente** (no se pierde nada) y luego restaurar las de
  origen.
- **Alternativa:** descartar lo que cargó destino.

El usuario **no ha elegido**. Es la única decisión pendiente del plan.
