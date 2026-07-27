# ESTADO vivo del proyecto

> Único lugar donde se registran pendientes, migraciones y planes con fecha.
> Actualizar aquí (no en CLAUDE.md). Última revisión: **27/07/2026**.

## Migraciones
- **`046_orden_merito_inmutable`** (24/07): Fase B del rediseño del orden de mérito.
  Additiva: `periodos_publicacion.primera_publicacion_en` (marca monotónica de primera
  publicación, backfill de lo ya publicado) + tabla nueva `orden_merito_rectificado`
  (versión no oficial del ranking). No altera datos existentes. Idempotente
  (`ADD COLUMN IF NOT EXISTS` + `CREATE TABLE IF NOT EXISTS`). **APLICADA EN LOCAL
  Y PROD** (prod el 25/07/2026, importada a mano por phpMyAdmin ANTES del merge
  `dev`→`main` que desplegó el código de las Fases A y B). Ver
  `docs/modulos/orden-merito.md`.
- **`045_matriculas_tipo_retirado`** (22/07): agrega `'retirado'` al enum
  `matriculas.tipo`. Marca al estudiante que YA NO ASISTE pero no se trasladó
  oficialmente (sin constancia ni IE destino; la familia espera que regrese). Los
  9 rosters de evaluación (calificaciones, conducta ×5, resumen/bloqueo,
  transversales, tutoría) pasan de `!= 'trasladado'` a
  `NOT IN ('trasladado','retirado')`; boleta/mérito/SIAGIE NO cambian (un retirado
  es desactivado no-trasladado → BORRADOR, fuera de mérito/export). Reversible vía
  `tipo_anterior`. **EN PROD** (22/07, importada a mano ANTES del merge `dev`→`main`
  `10d6d51`). Idempotente (`MODIFY`). Ver `docs/modulos/matriculas.md`.
- **`044_periodos_publicacion`** (21/07): COMPUERTA DE PUBLICACIÓN DE BOLETAS.
  Crea `periodos_publicacion` (periodo × nivel → `publica_en`, con suspensión
  reversible por reapertura y despublicación manual definitiva con motivo) y
  hace el **backfill retroactivo obligatorio** de todo bimestre ya cerrado.
  Cerrar un bimestre ya NO publica sus boletas. Idempotente (verificada con
  2 corridas). Regla completa en `docs/modulos/boletas.md`.
  **APLICADA EN LOCAL Y PROD.** En prod se importó a mano (phpMyAdmin) el
  **22/07/2026**, ANTES del merge `dev`→`main` que desplegó el código — así el
  código nuevo nunca corrió sin su tabla. Backfill verificado (B1 sigue visible).
- **LOCAL y PROD: al día hasta la `045`.** En prod: 038-043 el 20/07/2026, 044 y
  045 el 22/07/2026, 034-037 el 09/07/2026. En local la `043` (`cierres_asistencia`) se
  había saltado al aplicarse suelta; se corrió el **22/07/2026** (estructura
  verificada idéntica a la migración) y local quedó igualado a prod.
  Con esto quedan desbloqueados en prod: reprocesar las actas SIAGIE de
  4°A/4°B B1 (ver Pendientes operativos) y la calificación extraordinaria.
- **`043_cierres_asistencia`** (17/07): crea `cierres_asistencia` (una sola
  etapa: RA bloquea; anulable con traza). Soporte del historial de bimestres y
  del imprimible oficial de Conducta/Asistencia (ver `docs/modulos/admin.md`).
- **`042_calificacion_extraordinaria`** (16/07): `criterios.extraordinario`,
  `calificaciones.extraordinaria` y `rectificaciones_calificacion.tipo`.
  Soporte de la CALIFICACIÓN EXTRAORDINARIA: RA registra nota (con motivo) a un
  alumno sin calificación en competencia cerrada/bloqueada, desde Rectificación.
  Va a boleta y SIAGIE; NO cuenta en el orden de mérito. Idempotente; verificada
  end-to-end en local (25 checks, Inglés 4°A C2 B1). Ver
  `docs/modulos/calificaciones.md` y `docs/modulos/orden-merito.md`.
- **`041_areas_codigo_siagie_primaria`** (16/07): puebla `areas.codigo_siagie`
  para PRIMARIA (los códigos NO son los de secundaria: Inglés `0003`, COMU `0005`,
  PPSS `067`; transversales `0006,0007`; CAST SEGNL y Tutoría sin código a
  propósito). Habilita el fallback por posición del exportador SIAGIE también en
  primaria (causa raíz de las actas 4°A/4°B B1 con Inglés en blanco). Además
  FORMALIZA el rename de Inglés C1 primaria al nombre oficial CN (aplicado a mano
  el 14/07 en local+prod; en ambas es no-op, corrige solo en setups desde cero).
  Idempotente; validada con `--simular` sobre el acta real de 4°A B1 (reporte
  byte-idéntico pre/post migración). Ver `docs/modulos/export-siagie.md`.
- **`040_notas_autorizadas_siagie`** (14/07): crea `notas_autorizadas_siagie`
  (matricula+competencia+periodo → literal + conclusión + resolución, UNIQUE).
  "Informe aparte" de notas que dirección autoriza para un alumno NO evaluado por
  ausencia justificada, VÁLIDAS SOLO PARA EL SIAGIE (no tocan `calificaciones`,
  boleta ni orden de mérito). El export las usa solo para rellenar la celda en
  blanco de una competencia bloqueada. Idempotente. Ver
  `docs/modulos/export-siagie.md` y `docs/modulos/matriculas.md`.
- **`039_areas_codigo_siagie`** (12/07): agrega `areas.codigo_siagie` y lo puebla
  para SECUNDARIA (mapeo hoja→área del exportador SIAGIE; transversales `0006,0007`).
  Corrige el `nombre_siagie` erróneo del Taller Raz. Mat. Primaria queda NULL a
  propósito (mantiene su matching global validado). Idempotente. Ver
  `docs/modulos/export-siagie.md`.
- Migraciones más recientes (034-037): `034_purga_docente_duplicada`,
  `035_area_etica_boleta`, `036_competencia_etica_valores` (crea C57, interruptor
  de Ética), `037_consolidar_docentes_duplicados`. Todas en LOCAL y PROD.
- **`038_matriculas_traslado_entrada_pendiente`** (09/07): corrige 6 matrículas
  mal registradas en el registro masivo. 4 pasan a `pendiente` (para exigir
  documentos); de esas, 3 además a `tipo='nuevo'` (traslado de entrada) y 1 se
  mantiene `continuador`. Ancla por DNI + año activo + guarda `estado='aprobada'`
  (portable e idempotente). Verificada en local (4 filas; reintento 0/0). NO
  escribe motivo_estado. **APLICADA EN PROD** (20/07/2026, dentro del lote 038-043;
  ver el punto "LOCAL y PROD: al día" y la sección Git).
- Orden completo de setup desde cero: ver `docs/infraestructura.md`.
- OJO al crear un año académico nuevo: `getOrCreateConfiguracion` inserta
  `duracion_hora_min = 50` por defecto; el año 2026 usa 45.

## Pendientes de desarrollo
- **Rediseño 2 del orden de mérito — IMPLEMENTADO Y PROBADO (26/07/2026), EN `dev`,
  PENDIENTE DE DEPLOY.** Las 6 fases + una fase extra (F5b) y varios fixes; **sin
  migración nueva**, así que el deploy es merge + push sin tocar la BD de prod.
  Qué hace cada fase, las desviaciones respecto del plan y los efectos colaterales
  aceptados: `docs/modulos/orden-merito-rediseno.md` **§8** (manda esa sección, no las
  §1-5, que son el plan original). Estado vigente del módulo: `orden-merito.md`.
  Diferencia consciente con el diseño: el cierre **no** valida "0 competencias sin
  bloquear" (P3) porque él mismo las fuerza.
- **Compuerta de publicación: EN PRODUCCIÓN desde el 22/07/2026** (migración 044
  + merge `dev`→`main` `dca4023`). Cerrar ya no publica; se publica por nivel con
  fecha/hora desde `/admin/control`. Regla, decisiones y verificación en
  `docs/modulos/boletas.md`. El diseño viejo de `docs/decisiones-diferidas.md`
  (`periodos.publicado`) quedó OBSOLETO: no alcanzaba un booleano.
  - **Pendiente relacionado:** el **logro anual** todavía usa "último bimestre
    cerrado"; debe exigir **año académico cerrado**. Se dejó fuera a propósito
    (decisión #9): el usuario explicará antes la situación del cierre de fin de año.
- **Staging `dev.sigacociap.net`** (diferido): subdominio alimentado por `dev`,
  BD propia, secretos fuera del repo.
- **Modo mantenimiento** (diferido, opcional): pantalla 503 + lista blanca staff.
- **CSP:** pasada dedicada — auditar estilos inline (`style="--pct:..."`) y el QR
  antes de aplicar `Content-Security-Policy`.
- **Limpieza menor:** quitar del `.gitignore` las reglas obsoletas de
  `public/assets/img/firmas/`; `AuthMiddleware` está SIN USAR (la auth es por
  controlador) → decidir si se conecta o se elimina.
- **Nómina detallada admin/RA — etapa 2** (resumen estadístico); la etapa 1
  (nómina imprimible global con filtros) está implementada. Ver `docs/modulos/admin.md`.
- **Búsqueda del index de matrículas** no matchea códigos provisionales `P…`
  (cae en la rama de nombre). Ajuste chico en `construirFiltros` si se pide.
- **"Reemplazar docente" en sección unidocente** no actualiza `secciones.tutor_id`
  ni opera sobre todas las cargas del tutor → el entrante pierde `es_aula`
  (vista consolidada, Tutoría/Conducta).
- **Recreos:** no modelados (hoy son el hueco entre bloques). Primaria tiene 2 y
  secundaria 1 en horas distintas; chocan con el eje de fila única del imprimible.
- **Logins para apoderados** (módulo diferido, análisis de impacto ya hecho):
  alta que reuse persona, soporte multi-hijo (`getHijo` LIMIT 1; 84 apoderados con
  >1 hijo), arreglar `desactivarUsuarioDeEstudiante`, política de contraseñas.
- **Módulo de suspensiones/disciplina** (diferido): principios de diseño fijados
  en `docs/decisiones-diferidas.md` — NUNCA manejarlas con estado `desactivado`.
- **Boletas de matrículas desactivadas por vías internas: EN PRODUCCIÓN
  (merge a `main` 08-09/07/2026)** — desactivados por deuda/baja: BORRADOR
  forzado; trasladados consumados vía gestión: última boleta OFICIAL con
  estructura anual completa; buscador de nómina docente ampliado; token público
  intacto. Regla completa en `docs/modulos/boletas.md`. Incluye la reubicación
  del registro de exoneraciones a "Gestión de la matrícula"
  (`docs/modulos/matriculas.md`).

## Compuerta de publicación de boletas — EN PRODUCCIÓN (22/07/2026)

> Cerrar un bimestre **ya no publica** sus boletas a las familias. Publicar es un
> acto separado, **por nivel y con fecha/hora**, desde `/admin/control`.
> Migración **044** aplicada en LOCAL y **PROD** (prod el 22/07/2026, importada a
> mano antes del merge que desplegó el código). El backfill retroactivo fue
> dentro de la migración; B1 verificado visible tras el deploy.
>
> **La regla completa, el modelo de datos, la matriz de reapertura, los 4 puntos
> de lectura y la verificación viven en `docs/modulos/boletas.md`** (sección
> "Compuerta de publicación de boletas"). No duplicar aquí.

Resumen de lo que cambió, para orientarse:
- Nueva tabla `periodos_publicacion` + `PublicacionBoletaModel` (punto único).
- `armar()` suma el umbral **`'archivo'`**: mismo corte de datos que `'oficial'`
  pero ignora la compuerta, para que RA pueda **imprimir antes de la reunión**
  de entrega (era la decisión que quedaba abierta; se cerró el 21/07).
- La compuerta oculta el bimestre **completo** (notas, asistencia, conducta y la
  columna), no solo las notas.
- `cerrar()` solo **restaura** publicaciones suspendidas por una reapertura;
  nunca crea publicaciones nuevas.
- Despublicar a mano **marca** la fila (motivo + autor auditados), no la borra.

**Sin resolver (fuera de alcance, decisión #9):** el **logro anual** sigue usando
"último bimestre cerrado" y debería exigir **año académico cerrado**.

## Ética y Valores (Educación Religiosa) — plan de encendido (07/07/2026)

> SOLO SECUNDARIA — no tocar nada de primaria. Diseño completo en
> `docs/modulos/calificaciones.md` (sección "Ética y Valores"). Código en `main`
> (deploy 08/07) y **migraciones 035/036 YA aplicadas en PROD (09/07)** → el
> interruptor (C57) está encendido en producción. La fase de datos por UI de abajo
> queda como referencia histórica del encendido.

**Fase de datos en PROD (la ejecuta RA/admin por la UI, en este orden):**
1. Crear las **11 cargas TOE de secundaria** (área 24, docente = tutor vigente
   de cada sección, horas reales de tutoría 1-2h). Verificar duplicados antes
   (`cargas_academicas` sin UNIQUE KEY).
2. Currículum → área 24: `nombre_boleta = 'Ética y Valores'`,
   `alias_boleta = '(Educación Religiosa)'`. Verificar `nombre_siagie` NULL.
   → **empaquetado en migración `035_area_etica_boleta`** (el `nombre_siagie`
   NO se toca ahí; se decide al construir el exportador SIAGIE de secundaria).
3. Currículum → área 14 (Ed. Religiosa secundaria): **quitar** el alias huérfano
   "(Ética y Valores)" (nunca se imprimió: el área no tiene cargas ni notas).
4. Exoneraciones de religión: registrarlas **contra el área 24** (motivo:
   "Exoneración de Educación Religiosa"). El candado nuevo impide exonerar si
   ya hay notas vivas.
5. **Interruptor (al final):** crear la competencia del área 24 —
   `codigo=C57`, nombre_corto "Actúa con valores éticos y conciencia moral",
   nombre_completo "Actúa con valores éticos según los principios de su
   conciencia moral en situaciones concretas de la vida escolar y comunitaria."
   Al existir, la card aparece sola a los 11 tutores.
   → **empaquetado en migración `036_competencia_etica_valores`** (correr
   DESPUÉS de 035; en local resultó id 127).

**Operación:** criterios libres del tutor (flujo normal); exonerados = fila EXO
sin input (ya genérico); la sección de transversales NO aparece en la carga TOE
(exclusión nueva). Hito A fuerza bloqueos del tutor como a cualquier docente.

**Comunicación (colegio):** comunicado escrito en la PRIMERA entrega de boletas
del II Bim (área oficial evaluada por su dimensión de conciencia moral, a cargo
del tutor; derecho de exoneración disponible). NO diferir a fin de año.

**Datos de ensayo en LOCAL** (borrar si estorban a la demo del 08/07):
la competencia C57 (área 24, hoy id=127) YA NO es ensayo: la crea la migración
`036` — NO borrarla. Restan como ensayo: carga id=416 (1°A sec., tutor
docente_id=2) y exoneración id=2 (matrícula 198, "ENSAYO LOCAL"). Además conducta B2 de la
sección 13: 510 respuestas sembradas + cierre RA id=25 (limpiar con
`DELETE FROM conducta_respuestas WHERE periodo_id=2 AND matricula_id IN
(SELECT id FROM matriculas WHERE seccion_id=13); DELETE FROM cierres_conducta
WHERE id=25;`).

## Exportación SIAGIE (implementada 03/07 — B1 cerrado en prod el 20/07)
- **B1 COMPLETO subido al SIAGIE sin rebotes (20/07/2026, confirmado por el
  usuario):** todas las notas del I Bimestre (primaria y secundaria) se llenaron
  por este flujo y el SIAGIE aceptó los archivos. Esto valida end-to-end el
  pipeline y cierra los pendientes de "piloto de re-importación", "verificar
  end-to-end" y "reprocesar actas de primaria". Lo que sigue son mejoras
  (automatización del lote) y los diferidos de secundaria, no correcciones.
- **Módulo web "Actas SIAGIE" (12/07):** UI para admin/RA (subir → previsualizar
  con resolución de identidad → confirmar → descargar). Flujo efímero, una
  sección por vez (primaria y secundaria). Las libs se movieron de
  `scripts/siagie/lib/` a `app/Siagie/` (namespace `App\Siagie\`, autocargable) y
  la orquestación del CLI se extrajo a `app/Siagie/LlenadorSiagie.php` (CLI =
  wrapper delgado). Detalle en `docs/modulos/export-siagie.md`.
- **Cambio de sección sin tramitar — detección (12/07):** el módulo detecta si una
  fila `sin_match` es un alumno que SIGA tiene en OTRA sección del mismo grado y
  permite resolverlo por DNI (escribe sus notas reales, marcado como cruce en el
  reporte). Ver `docs/modulos/export-siagie.md`.
- **PENDIENTE — trámite de "cambio de sección" en SIGA (evaluar):** hoy no existe;
  la matrícula fija `seccion_id` al crear y no hay `UPDATE`. Mover un alumno a
  mitad de bimestre es delicado (sus `calificaciones` cuelgan de las `cargas` de
  la sección vieja). Por ahora el módulo SIAGIE solo lo detecta/resuelve en el
  acta; la reconciliación real en SIGA queda como decisión de diseño futura.
- **Piloto de re-importación: SUPERADO.** El B1 completo se re-importó al SIAGIE
  sin rebotes (20/07); los shared strings anexados fueron aceptados, así que el
  fallback previsto en `docs/modulos/export-siagie.md` no hizo falta.
- **Discrepancia de catálogo — Inglés C1: RESUELTA (histórico).** Renombrada al
  nombre oficial CN (con "oralmente") directo en BD local+prod el 14/07;
  formalizada en la migración `041` (16/07, no-op donde ya está corregida). Las
  actas de primaria llenadas ANTES del 14/07 (4°A/4°B B1) salieron con Inglés en
  blanco y ya fueron reprocesadas dentro del cierre de B1 del 20/07. Diagnóstico
  completo en `docs/modulos/export-siagie.md`.
- **`codigo_siagie` de primaria: POBLADO** (migración `041`, 16/07) con los
  códigos del archivo RegNotas real de 4°A B1. El fallback por posición ya
  opera en ambos niveles; una discrepancia de nombre futura ya no deja la
  columna muda.
- **Variante SECUNDARIA — IMPLEMENTADA (12/07), B1 operativo.** Verificada con
  nóminas reales (S1A, S5B). NL literal confirmado; diferenciación por área
  (migración 039) → MATE (4/4, sin choque con talleres) e Inglés (por posición)
  ya se llenan. Detalle en `docs/modulos/export-siagie.md`. **Diferidos:**
  - **Selector de talleres** (por nómina, sin flag persistente) + definir cómo
    llegan sus notas (hoja propia vs área anfitriona) — cuando haya archivo con
    un taller aprobado en SIAGIE.
  - **Ética/EREL para B2:** mapear **C57 (área 24, tutoría) → las 2 columnas de
    Educación Religiosa (035-EREL)**; la nota única del tutor se DUPLICA; exonerados
    → EXO. En B1 no hay notas de Ética → EREL en blanco es correcto.

## Rediseño del orden de mérito (COMPLETADO — 25/07/2026)

> Plan aprobado por el usuario. 3 fases, en `dev`. Reglas de negocio confirmadas:
> (1) el ranking permanece por `tipo` (fuera solo `trasladado`/`retirado`); (2) el
> snapshot OFICIAL es inmutable una vez que el periodo **estuvo publicado** (compuerta
> 044, monotónico, a nivel de periodo — B1 se publicó con ambos niveles a la vez);
> (3) cierres/reaperturas/rectificaciones posteriores a la publicación generan una
> versión **rectificada NO oficial**, nunca tocan el oficial.

- **Fase A — filtro por `tipo` (HECHA, `dev`, 24/07):** `OrdenMeritoModel` pasó los 5
  `estado='aprobada'` a `tipo NOT IN ('trasladado','retirado')`; anclaje de retorno
  intacto; verificado (pendientes entran, trasladado/retirado fuera, retorno OK, B1 sin
  empates nuevos). Docs: `orden-merito.md` §7.1 + invariante en CLAUDE.md.
  **Commit `c81a963`; EN PROD (merge `dev`→`main` `68968bb`, 25/07/2026).**
- **Fase B — inmutabilidad + versión no oficial (HECHA, `dev`, 24/07):** migración
  **046** additiva (`periodos_publicacion.primera_publicacion_en` con backfill + tabla
  `orden_merito_rectificado`); `PublicacionBoletaModel::fuePublicado()` (monotónico);
  `OrdenMeritoModel::registrarRanking()` (punto único con candado) +
  `generarSnapshotRectificado()` + `calcularFilasRanking()` (refactor) + lectores;
  `cerrar` y rectificación migrados a `registrarRanking`; card + vista de solo lectura
  en `/admin/control`. Verificado en local (candado: con oficial presente + B1
  publicado, la 1ª llamada YA rechaza tocar el oficial → rectificado; oficial
  intacto; limpieza restauró snapshot vacío). **Migración 046 aplicada en LOCAL
  Y PROD (25/07); commit `bf31526`; EN PROD (merge `68968bb`, 25/07/2026).**
  Gulp NO requerido (reusa clases).
- **Fase C — reconstrucción de B1 (HECHA, EN PROD 25/07/2026):** el usuario decidió
  el roster por REGLA (no por el documento de dirección): **todos los estudiantes con
  calificaciones bloqueadas/aprobadas en B1, SIN filtro de tipo**, conservando el anclaje
  de retornos y la exclusión de áreas transversal/tutoría. Resultado: **snapshot oficial
  de B1 = 528 filas** (roster en vivo con filtro de tipo daba 520/519; la regla reincorpora
  8 `trasladado` + `541` `retirado`, todos continuadores con notas B1 completas y
  bloqueadas). "Bloqueadas y aprobadas" no cambia el universo (0 calificaciones de mérito
  B1 sin bloqueo). El único alumno realmente integrado en B2 (1, sin notas B1) queda fuera
  por construcción. **0 empates pendientes** con el roster de 528 (verificado con la cascada
  real `aplicarDesempate`). Snapshot generado por script one-off (SIN filtro de tipo) e
  importado a mano por phpMyAdmin (DELETE 519 previas + INSERT 528; `filas=528, mn=1,
  mx=72`). **Caso especial de B1: la regla GENERAL del código NO cambió** (sigue filtrando
  por tipo, Fase A). El candado 046 protege el oficial (B1 publicado → rectificaciones
  futuras van a `orden_merito_rectificado`). ⚠️ **NO correr `backfill_orden_merito.php`
  en prod** (usa `generarSnapshot` con filtro de tipo → sobrescribiría el 528 por 519).

## Pendientes operativos (usuario / colegio)
- **Alumno retirado (feature del 22/07, migración 045):** marcado como `retirado`
  en prod ✓ (22/07). **Limpieza quirúrgica de conducta B2 HECHA en prod (24/07)**:
  matrícula 541 (DNI 63361405, sección A, `conducta_cerrada=0` verificado), 10 filas
  de `conducta_respuestas` del II Bim eliminadas; `calificaciones_conducta` no tenía
  fila (0). Notas académicas intactas. Respaldo reversible vía tablas
  `_bkp_conducta_resp_541` / `_bkp_calif_conducta_541` (dejadas en prod como red de
  seguridad → **borrarlas tras el cierre de conducta de la sección A**).
- **Validar en móvil real** el botón "✕ Cerrar" de documentos en ventana nueva
  (Chrome Android / Safari iOS): abrir varias boletas seguidas y confirmar que la
  pestaña se cierra y no se acumulan.
- **Digitar horarios reales en prod:** 1°A secundaria (11 cursos "sin horario
  propio" tras la migración 031) y las áreas sin bloques reales tras la 030
  (CyT/Matemática primaria 4°-6°, Arte y Cultura 1°A prim., etc.). 3°B ya está completo.
- **Solape real preexistente:** CLEMENTE ANGELES, lunes, 1°C (14:40-16:10) vs
  5°B (15:45-17:20) — debe resolverlo el colegio.
- **Orden de mérito:** RECONSTRUCCIÓN DE B1 HECHA (25/07, ver "Rediseño del orden de
  mérito" abajo, Fase C). Snapshot oficial de B1 = 528 en prod. Queda solo el check
  visual de `/director/orden-merito/1` en prod (que los 9 reincorporados salgan en su
  puesto). ⚠️ No correr `backfill_orden_merito.php` en prod (desde el 26/07 tiene guard
  propio, pero la advertencia sigue valiendo para versiones desplegadas antes).
- **Retorno de grado de BALTAZAR SHALOM CRISTEL — BLOQUEARÁ EL CIERRE DE B2.**
  Matrícula oficial 190 (Primaria 2° B, la de SIAGIE) + operativa 692 (1° B, donde
  CURSA); retorno activo desde el 21/06/2026. La evaluó la docente de 1° B, pero **esa
  evaluación no existe en las cargas de 1° B**: los promedios se registraron en las
  cargas de 2° B repitiendo la misma nota en cada criterio para no alterar el promedio
  (la 190 tiene 122 criterios así; la 692 tiene los 22 promedios y CERO criterios).
  Consecuencia: la alerta de evaluación incompleta le marca los criterios de 1° B en
  blanco y con la F4 eso **aborta el cierre** mientras el bimestre esté abierto. Hay que
  registrarle la nota o la omisión en las cargas de 1° B antes de cerrar B2, o repetir el
  mismo procedimiento a conciencia. **NO es un duplicado de matrícula: no borrar la 692.**
  Decisión del usuario (26/07): la alerta se deja como está (solo informa) y se resuelve
  operativamente.
  - **Cifras reales medidas el 27/07/2026** (las de "80 blancos" que decía antes esta
    entrada quedaron obsoletas al filtrar `ca.estado = 'activa'` en el fix `af72ac7`):
    en **B1** son **69 blancos** (matrícula 692), y como B1 está cerrado la alerta ahí es
    **informativa**, no bloqueante. En **B2** la alerta **todavía NO lo marca**: solo
    aflora un criterio cuando algún compañero de su sección ya tiene nota en él, y 1° B
    apenas ha calificado el II Bim. **El riesgo sigue en pie** — irá apareciendo conforme
    la docente de 1° B avance, y para cuando toque cerrar B2 estará completo. Volver a
    medir con `ControlOperativoModel::alertasEvaluacionIncompleta(2)` antes de cerrar.
- **Re-subir firma/sello del Director EBR** solo si se recrea el entorno
  (se pierden únicamente si se borra el directorio externo `~/siga_uploads/`).
- **Decisión del colegio pendiente:** regenerar (o no) el ranking B1 tras el
  cambio de umbrales del 10/06 (desempates `num_alto IN (15,16)` y `num_16`).

## Eventos con fecha
- **08/07/2026 — Capacitación docente (PLAN CERRADO):** demos proyectadas desde
  el entorno de desarrollo; práctica de docentes en producción = trabajo REAL del
  II Bim; sin backup/restore. Dos turnos: primaria 12:30pm-2:00pm, secundaria
  7:30pm-9:00pm. Detalle en `docs/decisiones-diferidas.md`.

## Git
- `dev` = rama de trabajo; `main` = producción (auto-deploy en Hostinger).
  **Preguntar SIEMPRE antes de mergear `dev` → `main`.**
- `dev` y `main` sincronizados el 20/07/2026 (ff `8ae3d08..567b7f9`): lote
  SIAGIE completo (módulo web Actas + secundaria + notas autorizadas por
  dirección), calificación extraordinaria, historial de conducta/asistencia
  con imprimible (migr. 043), vista legado B1 de conducta (admin + banner del
  tutor) y selector de bimestre en /admin/conducta. Migraciones 038-043 ya
  en prod.
- **22/07/2026 — deploy de la compuerta de publicación:** migración 044 importada
  a mano en prod y merge `dev`→`main` (`567b7f9..dca4023`, fast-forward). Arrastra
  también el fix SIAGIE de código de 14 dígitos (`e06f49e`). `dev` y `main`
  quedaron sincronizados.
- **25/07/2026 — deploy del rediseño del orden de mérito (Fases A y B):**
  migración 046 importada a mano en prod (phpMyAdmin) ANTES del merge, y merge
  `dev`→`main` (`10d6d51..68968bb`, fast-forward). Arrastra las migraciones
  045/044 que ya estaban en prod más las Fases A (filtro por `tipo`) y B
  (inmutabilidad del oficial + versión rectificada) verificadas en local, y los
  scripts de verificación (`database/verificaciones/`). `dev` y `main`
  sincronizados en `68968bb`. **Queda la Fase C** (reconstrucción de B1 contra el
  documento oficial, depende del documento del usuario).
- **26/07/2026 — `dev` pusheado en `cce5d90`, 17 commits por delante de `main`.**
  Contiene TODO el rediseño 2 del orden de mérito (F1-F6 + F5b + fixes) y el
  aseguramiento de los dos scripts destructivos. Probado en navegador por el usuario
  con dos usuarios padre de prueba (creados por SQL y ya borrados). **NO desplegado: el
  usuario no autorizó el merge a `main`.** No hay migración pendiente para este lote.

## Scripts que escriben en la BD — cuidado (26-27/07/2026)
- **`database/verificaciones/verif_fase_b_orden_merito.php` BORRABA el snapshot oficial
  de B1.** Su paso 4 "autolimpieza" hacía `DELETE` ciego de `orden_merito_snapshot` y
  `orden_merito_rectificado` del periodo 1. Se escribió el 24/07, cuando B1 no tenía
  snapshot, y quedó obsoleto al día siguiente con la Fase C. **Destruyó las 528 filas en
  LOCAL el 26/07.** Se intentó reconstruirlas (misma firma, neutralizando temporalmente
  los 8 `trasladado` con notas B1 y regenerando), pero **esa reconstrucción NO quedó
  persistida** — ver el punto siguiente. Ahora: corre dentro de una transacción
  con ROLLBACK, aborta si detecta el archivo de secretos de producción, y reproduce el
  escenario "sin oficial" dentro de la transacción (antes su primera aserción no probaba
  nada, porque con un oficial presente la llamada devolvía `'rectificado'`).
- **LOCAL tuvo el snapshot oficial de B1 VACÍO — RESUELTO el 27/07/2026.**
  `orden_merito_snapshot` y `orden_merito_rectificado` habían quedado en **0 filas**
  (PROD conservó siempre las 528). No era un fallo del código: sin filas
  `debeUsarSnapshot()` cae al cálculo en vivo de forma limpia y local mostraba **518
  alumnos** en B1. Lo que sí rompía era la CONFIANZA EN LAS PRUEBAS
  (`verif_fase5b` daba un **OK falso** en su paso 2, comparando el vivo contra sí mismo).
  **Reconstruido con `database/reconstruir_snapshot_b1.php`** (ver abajo): 528 filas,
  11 grados, 23 secciones, puestos 1-72, 0 empates pendientes. Los **14 desempates
  resueltos** nunca se perdieron — son lo único no derivable por cálculo.
  - **Fidelidad verificada antes de escribir** (los 3 cambios del rediseño 2 son
    inocuos para B1): 0 de las 12 047 calificaciones de B1 carecen de bloqueo (P2 no
    mueve promedios), hay 0 notas de Ética en B1 (P5 no aplica) y el ranking completo
    calculado con el `ORDER BY` de hoy (`m.id`) vs. el del 25/07 (apellidos) da
    **exactamente los mismos puestos** — los 14 desempates cubren todos los grupos
    irreducibles, así que el apellido nunca llegaba a dirimir. Tampoco hubo
    rectificaciones posteriores (solo 2 extraordinarias, fuera del mérito por diseño).
  - **Corrección de cifras (medidas el 27/07):** el roster en vivo de B1 da hoy **518**
    (no "520/519") y la regla Fase C reincorpora **10**, no 9: los 8 `trasladado`, la
    541 y además la **357 (HUAMAN VIENRICH CATALEYA)**, que también es `retirado`.
    518 + 10 = 528, y la firma (528 filas, puestos 1-72) coincide con la de prod.
  - `backfill_orden_merito.php` NO sirve para B1 (regla general → 518).
- **`database/backfill_orden_merito.php`** ahora salta los periodos con snapshot oficial
  ya PUBLICADO salvo `--forzar`.
- **`database/reconstruir_snapshot_b1.php` (nuevo, 27/07):** reconstruye el oficial de
  B1 con la regla ESPECIAL de la Fase C (roster sin filtro de tipo). Guardas: aborta si
  detecta el archivo de secretos de PROD; **simula por defecto** (`--confirmar` para
  escribir); transacción; y antes del COMMIT verifica la FIRMA del documento
  (528 filas / puestos 1-72 / 0 empates / 0 sin puesto de sección) — si no coincide,
  ROLLBACK y aborta, prefiriendo dejar local sin snapshot antes que grabar un documento
  distinto del de producción. Idempotente (verificado con 2 corridas). Reutiliza la
  cascada del modelo por reflexión; solo duplica el SQL del roster, a propósito: meter
  la regla Fase C dentro de `OrdenMeritoModel` abriría la puerta a generar rankings sin
  filtro de tipo por accidente.
- **`verif_fase_a_orden_merito.php` leía el ranking snapshot-aware (corregido 27/07).**
  Escrito el 24/07, un día ANTES de la Fase C. Al volver a existir el snapshot de B1
  —cuyo roster incluye trasladados y retirados por la regla especial— sus casos 541 y
  308 salían "EN RANKING" contra su expectativa "FUERA". No era un fallo del código:
  en PROD reportaba lo mismo desde el 25/07, y en local el snapshot vacío lo hacía pasar
  por la razón equivocada. Ahora lee `rankingGradoLive` por reflexión (igual que
  `verif_fase5b` y `gradosConEmpatesPendientes`): lo que la Fase A verifica es el FILTRO
  del roster en vivo, no el documento congelado. Los 6 casos vuelven a coincidir.
- **Regla general:** ningún script de `database/` debe "limpiar" con DELETE lo que no
  creó. Si escribe para probar, que use transacción + rollback.
