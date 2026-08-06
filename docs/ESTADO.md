# ESTADO vivo del proyecto

> Único lugar donde se registran pendientes, migraciones y planes con fecha.
> Actualizar aquí (no en CLAUDE.md). Última revisión: **05/08/2026**.

## Migraciones
- **`047_retorno_grado_asistencia_solapada`** (05/08): corrección de DATOS (no toca
  esquema). Borra la fila de `inasistencias` que quedó en la matrícula **OFICIAL** de
  un retorno de grado cuando la **OPERATIVA** ya tiene fila del mismo bimestre. Con
  el solape, `getDelBimestreUnion` —que SUMA las dos fuentes— mostraba el **doble de
  inasistencias** en la boleta (caso real: 4 faltas en vez de 2 en B2). Ancla por el
  vínculo `retornos_grado`, **no por ids**; se autolimita al solape y exige
  `p.fecha_fin >= r.fecha_retorno`, así que **no puede** tocar un bimestre anterior al
  retorno. Idempotente (verificada con 2 corridas: 1 fila y luego 0). Probada
  ejecutando el archivo real en transacción con ROLLBACK.
  **APLICADA EN LOCAL** (verificado el 05/08: `inasistencias` pasó de 1053 a 1052 y
  la fila de la matrícula oficial en B2 ya no existe). **PENDIENTE DE CONFIRMAR EN
  PROD.** Antes de correrla, ejecutar el PREVIEW del propio archivo: debe devolver
  **exactamente 1 fila** (si devuelve 0, ya está aplicada).
  Ver `docs/modulos/retorno-grado.md`.
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
  - **Al 04/08/2026 el lote está LISTO para desplegar** y las dos condiciones duras
    están en verde (ver "Cierre de B2 — SECUENCIA CORRECTA"). Falta solo la
    autorización explícita del usuario para mergear `dev` → `main`.
- **Efecto colateral del guard P4 (llega con el deploy del rediseño 2) — REABRIR UN
  BIMESTRE YA CERRADO SE VUELVE UNA PUERTA DE UN SOLO SENTIDO.** `cerrar()` exige
  ahora `alertasEvaluacionIncompleta = 0`, y esa alerta se evalúa sobre bimestres
  `activo`. Un bimestre que se cerró ANTES de que existiera el guard puede no
  cumplirlo: **B1 tiene hoy 12 alumnos con blancos sin motivo**, así que reabrirlo lo
  dejaría imposible de re-cerrar hasta resolverlos uno a uno (nota u omisión desde el
  módulo del docente). No es un defecto —es la regla funcionando— pero es una
  restricción que HOY no existe y que aparece en el instante del merge.
  Antes de reabrir B1 (p. ej. para una rectificación), medir primero con
  `alerta_evaluacion_incompleta.sql` cambiando a `@periodo := 1`.
- **La superficie de mérito para familias entra OSCURA (medido el 04/08/2026):** en
  prod hay **0 usuarios con rol Padre** (35 docentes, 1 admin, 1 registro académico,
  1 Director EBR). `/padre/orden-merito` y `/padre/ranking-seccion` (fase 6) solo son
  alcanzables por admin/RA, que están en el `requireRole` del controlador. Baja el
  riesgo del deploy, pero significa que **la parte más nueva del lote no la va a
  estrenar nadie** hasta que exista el módulo de logins para apoderados.
- **Compuerta de publicación: EN PRODUCCIÓN desde el 22/07/2026** (migración 044
  + merge `dev`→`main` `dca4023`). Cerrar ya no publica; se publica por nivel con
  fecha/hora desde `/admin/control`. Regla, decisiones y verificación en
  `docs/modulos/boletas.md`. El diseño viejo de `docs/decisiones-diferidas.md`
  (`periodos.publicado`) quedó OBSOLETO: no alcanzaba un booleano.
  - **Pendiente relacionado:** el **logro anual** todavía usa "último bimestre
    cerrado"; debe exigir **año académico cerrado**. Se dejó fuera a propósito
    (decisión #9): el usuario explicará antes la situación del cierre de fin de año.
- **LOS 4 REGISTROS DEL BIMESTRE Y EL CONTRATO DEL CIERRE — PLAN APROBADO, SIN
  IMPLEMENTAR (04/08/2026).** Se ejecuta **después de cerrar y publicar B2**, para que
  el primer bimestre bajo las reglas nuevas sea B3. Plan completo con fases, riesgos y
  preguntas abiertas: **`docs/modulos/cierre-cuatro-registros.md`**.
  - **Origen:** al verificar la regla del colegio ("los 4 registros aprobados y
    bloqueados antes de cerrar") se descubrió que **conducta y asistencia están fuera
    del contrato del cierre** (ni se exigen ni se fuerzan), y que la compuerta temporal
    está escrita **5 veces en 3 regímenes distintos** (3 en PHP + 2 columnas SQL) —
    transversales no tiene ninguna.
  - **Decisiones cerradas del usuario (no re-preguntar):** D1 el cierre **EXIGE**
    (aborta) conducta y asistencia bloqueadas —académicas y transversales se siguen
    forzando—; D2 `limite_notas` sigue siendo **una sola fecha**, sin migración;
    D3 transversales **sí** pasa a respetar la compuerta; D4 **no** se puede bloquear
    una sección de asistencia sin ninguna fila registrada; **D5** (04/08) el universo del
    guard es **TODAS las secciones del año** (sin filtrar por tutor ni por nómina);
    **D6** (04/08) conducta se exige con **las dos etapas** (`ra_bloqueado_en` **y**
    `tutor_cerrado_en`).
  - **Sin migración.** Orden: F1 punto único → F3 guard de sección vacía → F2 guard del
    cierre → F4 transversales (esta última **exige avisar antes a los tutores**).
  - **Revisión del plan (04/08, mismo día):** el riesgo de los periodos `pendiente` en F1
    quedó **descartado con evidencia** (un `pendiente` nunca llega a
    `periodoEstaBloqueado`); D5 **obliga a SQL nuevo** para el guard (los dos resúmenes
    existentes no recorren el universo canónico); F3 **no puede** reusar
    `getProgresoPorSeccion` (filtra `m.estado='aprobada'`); y en local
    `cierres_asistencia` está **vacía**, así que el escenario de prueba hay que
    construirlo. Detalle en el doc del plan.
- **BOLETA CON TODAS LAS COMPETENCIAS DEL PLAN — IMPLEMENTADO Y VERIFICADO EN LOCAL
  (05/08/2026), EN `dev`, SIN DESPLEGAR. Sin migración.** La boleta lista **todas** las
  competencias que la sección dicta, tengan o no nota, con **guion** donde no hay dato.
  Qué se construyó, trampas y cifras: **`docs/modulos/boleta-competencias-completas.md`
  §10** (manda esa sección; §1-§7 son el plan original). Regla del módulo en
  `docs/modulos/boletas.md`.
  - **El universo son las CARGAS ACTIVAS de la sección**, y eso produce **solas** las
    tres exclusiones que pidió el usuario, sin ninguna excepción hardcodeada: sin
    Ed. Religiosa en secundaria (la evalúa Ética y Valores), y 5.º sin Arte y Cultura ni
    EPT. **Regalo medido:** el Taller de Pre-Cálculo solo se dicta en 5.º, así que
    tampoco sale en 1.º-4.º. Primaria: 0 huecos, y ahí Ed. Religiosa **sí** se muestra.
  - **Decisiones del usuario (no re-preguntar):** en un retorno de grado el plan sale de
    la matrícula **OPERATIVA**; la conclusión descriptiva **también lleva guion**; aplica
    a la boleta **impresa y digital** (la digital no necesitó cambios).
  - **Resultado:** primaria 27 competencias/9 áreas · secundaria 1.º-4.º 29/12 · 5.º
    27/11. El nº de filas ya **no varía entre alumnos de la misma sección**. Equivalencia
    probada sobre **1943 filas de nota, 0 perdidas**; retorno #1 probado (0 perdidas).
    Verificación: `verif_plan_completo_boleta.php` (solo lectura, corre en prod).
  - ⚠️ **Regresión que vigila la verificación:** los **exonerados** perdían el `EXO` (con
    el esqueleto sembrado, `inyectarEnAreas` caía siempre en su rama `else`). Corregido.
  - ⚠️ **Defecto preexistente corregido de paso:** las vistas separaban el bloque
    transversal buscando `'transversal'`, pero el área de **secundaria** se rotula
    `Comp. Transv.` → en secundaria nunca se movía al final ni recibía su estilo (y
    quedaba **antes** de Ética, `orden 90`). Ahora se detecta por `'transv'`.
  - ⚠️ **Contrapartida del universo por cargas:** un área sin carga por olvido
    desaparecería del documento en silencio. El bloque 1 de la verificación lo vigila.
  - **La CONDUCTA también lleva guion** en sus celdas vacías (05/08). Numeral y
    conclusión no le aplican —es siempre literal—, así que van con guion permanente.
  - **SEÑAL DE BORRADOR — PUNTO ÚNICO (05/08).** La marca de agua la pinta el
    DOCUMENTO (`boleta/_marca-borrador.php`, incluido por `boleta/alumno.php` al recibir
    `$vistaPrevia`), no los wrappers. **Corrige una regresión del mismo día:** al quitar
    el banner, la marca quedó en el wrapper de la vista previa de RA y en el del ZIP, y
    la **boleta impresa del docente** (`/docente/boleta/{id}/imprimir`, botón de la
    nómina) se quedó **sin ninguna señal**. El texto es único (`BOLETA_LEYENDA_BORRADOR`
    en `helpers.php`) y lo comparte la digital; cambia la forma, no el mensaje.
    Verificado en las **7 entradas** (4 en borrador → 1 marca cada una; 3 oficiales → 0).
    - **La boleta DIGITAL también lleva marca (05/08), y es control de fuga:** una captura
      de pantalla o una foto al monitor sacaba notas de un bimestre sin cerrar **sin nada
      que dijera que son provisionales**. Variante `--pantalla`: **fija en el viewport**
      (la digital se recorre con scroll; anclada al contenido dejaría sin marcar justo las
      capturas de la zona de notas) y **dimensionada en `vw` con `clamp()`**, porque los
      `pt` de A4 desbordarían ~3× en un móvil y darían scroll horizontal. Medido: a `12vw`
      proyecta 205 px en una pantalla de 320 px (+115 de margen). Opacidad 0.10.
      `pointer-events: none` es crítico en táctil. **No impide la captura: la etiqueta.**
  - **DESCARGA DE BORRADORES EN ZIP (05/08, pedido del usuario).** Botón
    **📄 Borradores** por sección → `/admin/boletas-publicas/{id}/archivar-borrador`.
    Mismo mecanismo que Archivar (un PDF por alumno, carpetas `NIVEL/GRADO_SECCION`)
    pero con el documento de la vista previa: umbral `'todos'`, sin QR ni firma, con
    marca de agua **dentro de cada PDF** y sufijo `_BORRADOR` en archivo y ZIP. **Sin
    guard de bimestre cerrado**: existe para el bimestre abierto. Su destino es el
    **Drive institucional**, para recoger el visto bueno de los docentes antes de
    cerrar. Verificado en servidor (3 boletas → 3 marcas, 0 QR, ZIP correcto) y que el
    modo Archivar sigue intacto. **Falta probar la descarga real en el navegador.**
    Ver `docs/modulos/boletas.md`.
  - **BANNER DE BORRADOR ELIMINADO (05/08).** En la vista previa de RA las firmas se
    fueron a una segunda hoja (visto en Secundaria 4.º A): el banner costaba **~6 mm**,
    dos filas de tabla. Queda como única señal la **marca de agua diagonal**, reforzada
    de `#555`/8% a `#3f3f3f`/16% — el documento **se imprime en papel con el bimestre
    abierto**, así que la señal debe sobrevivir a la impresora. Decisión del usuario
    sobre 4 alternativas. Ver `docs/modulos/boletas.md`.
  - 🔴 **PENDIENTE ANTES DE DESPLEGAR: checklist de impresión en navegador** (§8.3 del
    doc). La restricción dura es **UNA hoja A4 vertical**: el máximo de filas no sube
    (29 → 29) y el peor incremento es +5 (Primaria 2.º A), pero eso **no está probado en
    papel**. Toca SASS (`gulp` ya lo compiló en local).
    - **El alto ya no lo fijan las filas sino las CONCLUSIONES DESCRIPTIVAS** (2 líneas
      por celda, `.conclusion-clip`): el nº de filas es fijo por sección, el alto no.
      Peor caso medido en Secundaria 4.º A (la sección del incidente): matrícula **556**
      (ROSALES STEPHANO), **6 filas con conclusión**, hasta 233 caracteres. Es la boleta
      que hay que mirar para dar por buena esa sección.
- **NOTAS DE BIMESTRES CERRADOS PARA QUIEN LLEGÓ DESPUÉS — PLAN DE IMPLEMENTACIÓN LISTO,
  SIN IMPLEMENTAR (05/08/2026).** Plan completo con fases, archivos y SQL:
  **`docs/modulos/registro-retroactivo-notas.md`** (empezar por §6 **F0**).
  - **Lleva migración `048`** (tabla `calificaciones_retroactivas` + `DROP notas_externas`)
    → al desplegar hay que aplicarla a mano en prod ANTES del merge, como la 044 y la 045.
  - 🔴 **F0 es BLOQUEANTE y de solo lectura:** contar en PROD las 5 tablas de los
    mecanismos a unificar. Si alguna trae filas, la migración cambia y el `DROP` deja de
    ser seguro.
  - **F1 (asistencia en guion) es independiente y desplegable sola**, sin migración.
    **No desplegar F3 sin F4**: RA registraría notas que no aparecen en ningún documento.
  - **El caso existe: 6 estudiantes** con notas de B2 y ninguna de B1 (690, 691, 693,
    694, 695, 696; llegaron entre el 08/06 y el 13/07). ⚠️ **`matriculas.tipo` no sirve
    para detectarlos** —la mitad son `continuador`—; el anclaje es la ausencia de notas.
    No confundir con el lote `traslado_entrada` del 19/05 (~180 matrículas con B1
    completo, flag mal puesto).
  - **Buena parte ya está resuelta:** la **calificación extraordinaria** (migración 042,
    EN PROD desde el 16/07) ya permite registrar nota en competencia de bimestre cerrado
    a cualquier alumno sin nota, con motivo, y ofrece 26-28 de las ~27-29 competencias
    del plan (faltan solo las transversales). Falta: **literal** (pide numeral),
    **captura en lote** (hoy es de una en una, 26-28 pasadas) y **trazabilidad del origen**.
  - **Decisiones cerradas:** literal puro con **numeral en guion** (no se inventa el
    número); captura en **grilla** por alumno y bimestre; la boleta **declara** el origen
    con una nota al pie; asistencia del bimestre no cursado en **guion**.
  - 🔴 **Hallazgo con impacto HOY: la boleta imprime `0 faltas` en un bimestre que el
    alumno no cursó** (medido en la 694). `sin_registro` solo mira el umbral del bimestre
    y el estado `pendiente`, nunca si esa matrícula tiene filas de asistencia. Es el mismo
    dato falso del 04/08 con B2 vacío, por otra causa. **Es la fase F1 y es independiente
    del resto: se puede hacer ya, sin migración.**
  - ⚠️ **Prohibido `nota_numerica NULL` en `calificaciones`:** 45 usos en 11 archivos, de
    los que **26 son promedios, umbrales o desempates** que un NULL altera EN SILENCIO.
    De ahí que el plan proponga tabla aparte unida al leer (patrón ya probado en el
    retorno de grado y en `notas_autorizadas_siagie`).
  - **Decisiones del 05/08 (D6-D9):** la **extraordinaria y el registro retroactivo SE
    UNIFICAN** (un solo punto de entrada; el flujo de la extraordinaria se retira);
    **`notas_externas` se elimina** (su función la absorbe el proceso nuevo); las
    **transversales se registran OBLIGATORIAMENTE**; conducta y asistencia **opcionales**.
    - **La unificación no arrastra datos:** medido en local, los 5 mecanismos están en
      **0 filas** (extraordinarias, criterios extraordinarios, rectificaciones
      extraordinarias, `notas_externas`, `notas_autorizadas_siagie`). 🔴 **Verificar esas
      5 cifras en PROD antes de tocar nada**: si allí hay extraordinarias, se migran en la
      misma migración.
    - **Modelo unificado:** `nota_literal` SIEMPRE + `nota_numerica` **NULL** cuando viene
      de otro colegio (boleta: `— / A`) y con número cuando es evaluación real nuestra.
    - **Transversales:** hoy quedan fuera del insertable porque se muestran AGREGADAS
      desde el cierre del tutor y una fila cruda no llega a boleta. La tabla aparte lo
      resuelve sola (se une al leer, sin pasar por la agregación) — pero hay que evitar
      que se dupliquen cuando el alumno sí tiene agregación.
  - **Abierto (diferido por el usuario):** si van al SIAGIE. No bloquea F1 ni F2; conviene
    resolverlo antes de F4.
- ✅ **EXONERAR A UN ALUMNO QUE YA TIENE NOTAS — IMPLEMENTADO EN LOCAL (05/08/2026), EN
  `dev`, SIN MIGRACIÓN.** Deroga el candado del 07/07, que dejaba sin salida el caso real
  (estudiante con notas en un bimestre CERRADO y otro abierto): miraba todo el año y las
  notas del cerrado no se pueden borrar (`periodoEstaBloqueado`), así que su "elimina las
  notas primero" **no era ejecutable**. Ahora el aviso es **franqueable con confirmación
  explícita** (`confirmar_notas`). Regla completa en `docs/modulos/matriculas.md`.
  - **Decisión del usuario: EXO en los CUATRO bimestres**, incluidos los ya cursados.
    Las notas **no se borran** (reversible; al revocar reaparecen). Probado en transacción
    con rollback: `B1=A B2=A` pasa a `EXO EXO EXO EXO`, anual EXO, 4 notas intactas en BD
    y snapshot de B1 en 528 filas.
  - ⚠️ Asumido: la boleta de un bimestre ya entregado cambia hacia atrás y el acta del
    SIAGIE conserva la nota (divergencia a gestionar fuera de SIGA).
  - 🔴 **PENDIENTE ABIERTO: el orden de mérito EN VIVO sigue promediando las notas del
    área exonerada** (las queries del ranking no miran `exoneraciones`). Medido: 17.17 con
    el área vs 17.19 sin ella. Hoy es inocuo (la única exoneración vigente tiene 0 notas);
    **se activa con la primera exoneración sobre notas existentes**. Decisión pendiente
    del usuario: excluir del mérito las áreas exoneradas.
- **Staging `dev.sigacociap.net`** (diferido): subdominio alimentado por `dev`,
  BD propia, secretos fuera del repo.
- **Modo mantenimiento** (diferido, opcional): pantalla 503 + lista blanca staff.
- **CSP:** pasada dedicada — auditar estilos inline (`style="--pct:..."`) y el QR
  antes de aplicar `Content-Security-Policy`.
- ~~**Limpieza menor:** `.gitignore` + `AuthMiddleware`~~ **CERRADO (verificado
  29/07/2026).** Las reglas obsoletas de `public/assets/img/firmas/` ya no están en
  el `.gitignore` (solo queda `/storage/firmas/*.png`, que es la correcta), y
  `AuthMiddleware` **se eliminó** en el commit `eb0e9cf` (20/06/2026): la carpeta
  `app/Middleware/` ya no existe. La auth sigue siendo por controlador — invariante
  registrado en `CLAUDE.md` (Convenciones de código).
- **Nómina detallada admin/RA — etapa 2** (resumen estadístico); la etapa 1
  (nómina imprimible global con filtros) está implementada. Ver `docs/modulos/admin.md`.
- **Búsqueda del index de matrículas** no matchea códigos provisionales `P…`
  (cae en la rama de nombre). Ajuste chico en `construirFiltros` si se pide.
- **"Reemplazar docente" en sección unidocente** no actualiza `secciones.tutor_id`
  ni opera sobre todas las cargas del tutor → el entrante pierde `es_aula`
  (vista consolidada, Tutoría/Conducta).
- **Recreos:** no modelados (hoy son el hueco entre bloques). Primaria tiene 2 y
  secundaria 1 en horas distintas; chocan con el eje de fila única del imprimible.
- **Limpieza de `bloques_horario` (no urgente, hallazgo 29/07/2026):** la config de 2026
  tiene **9 bloques basura** con duración de 1 minuto y horarios imposibles (01:00-01:01,
  02:02-02:03, 03:02-03:03…), **todos con 0 sesiones**; y desde el arreglo del solape de
  DPCC quedó huérfano el bloque `15:45-17:20`. Nada de esto afecta a nadie hoy — barrerlo
  cuando se toque el módulo de horarios.
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
  ya se llenan. Detalle en `docs/modulos/export-siagie.md`.
- **EXCEPCIONES DE HOJA — IMPLEMENTADAS (27/07/2026, en `dev`, sin migración).**
  Reglas del colegio confirmadas por el usuario; viven en
  `LlenadorSiagie::EXCEPCIONES_HOJA` (se descartó la tabla de datos: son reglas
  curriculares estables, no configuración por bimestre). Regla completa en
  `docs/modulos/export-siagie.md` §"Excepciones de hoja".
  - **`035-EREL` ← Ética y Valores, TODOS los grados de secundaria.** El área
    Ed. Religiosa tiene 0 cargas; evalúa el tutor. La nota se DUPLICA en las 2
    columnas. Exonerados → EXO sin traducción (la exoneración ya está contra esa área).
  - **`032-ETRA` ← GAMA (CT4), SOLO 5°.** En 5° no se dicta EPT (0 cargas; verificado:
    1°=3, 2°=2, 3°=2, 4°=2, 5°=0); sus horas las ocupa el Taller de Pre-Cálculo, que
    **no se reporta al SIAGIE** (decisión del colegio). GAMA queda escrita 2 veces en
    5°: hoja `0007` + hoja `032`.
  - ⚠️ **id 57 = GAMA; código C57 = Ética (id 127).** Las reglas anclan por
    `nombre_boleta`/`codigo_minedu`, nunca por id.
  - **VERIFICADO CON ACTA REAL DE 5° (29/07/2026, `S5B.xlsx`) — pendiente CERRADO.**
    El libro **sí trae la hoja**; su tab real es **`032-ETRA`** (la doc decía
    `032-EPT`, que era una abreviatura asumida — irrelevante para el código, que
    matchea por el código `032` del tab). Tiene **una sola columna**, así que GAMA no
    se duplica ahí. Su leyenda es **`01 = Gestiona proyectos de emprendimiento
    económico o social`**, o sea la competencia de **EPT (C53)**, NO la de GAMA:
    **la excepción es NECESARIA**, sin ella esa columna saldría en blanco en silencio.
    `CT4` resuelve a una sola competencia, así que no cae en la degradación segura.
    Detalle en `docs/modulos/export-siagie.md`. Sigue siendo buena práctica correr
    `--simular` sobre la primera acta de B2 antes de subirla.
- **VÍNCULOS Y COBERTURA — IMPLEMENTADO (28/07/2026, en `dev`, sin migración).**
  Etapa 1 del gestor de vínculos SIGA↔SIAGIE. Detalle en
  `docs/modulos/export-siagie.md` §"Vínculos y cobertura".
  - **`/admin/actas-siagie/vinculos`** (solo lectura): áreas con notas y SIN destino,
    vínculos configurados, excepciones de hoja resueltas y colisiones de código.
    La tabla parte de `areas`, NO de `calificaciones`: un vínculo existe aunque el
    bimestre no tenga notas (si no, Ética y Ed. Religiosa desaparecían justo cuando
    hacía falta auditarlas). El índice de hojas ocupadas va por **nivel + código**:
    sin el nivel, la regla `035` de secundaria marcaba como reemplazada a la
    **Ed. Religiosa de PRIMARIA**, que se llena con normalidad (381 notas en B1).
    **Primaria no se toca en nada** — verificado en los 6 grados.
  - **`codigo_siagie` editable en Currículo** (antes solo por migración) con guardas
    de formato y de colisión → **activar un taller que el SIAGIE ya reconozca ya no
    necesita despliegue**.
  - **Hallazgo medido:** en B1 se perdieron **321 notas bloqueadas** de talleres
    (Raz. Mat. 272 + Pre-Cálculo 49) que nunca llegaron al acta, en silencio. En B2
    ya van 24 del Taller de Raz. Mat.
  - ⚠️ **BLOQUEO DE FONDO (29/07/2026): los talleres NO tienen hoja en el SIAGIE.**
    Al ir a asignarles el `codigo_siagie` se verificó, leyendo los dos RegNotas
    reales de B1 (`S1A.xlsx` de 1°A y `S5B.xlsx` de 5°B), que **ambos libros traen
    las MISMAS 15 hojas y ninguna es de taller** — y 1°A es una sección donde SÍ se
    dicta el Taller de Raz. Mat. **Asignar el código no resolvería nada: no hay hoja
    que llenar.** Lo que falta no está en SIGA sino en el **plan de estudios
    registrado en el SIAGIE** → es una gestión del colegio ante SIAGIE/UGEL, no un
    cambio de código. Alcance (local, B1 completo; confirmar en prod): Raz. Mat.
    = 1° a 5°, 11 secciones, 273 notas; Pre-Cálculo = 5° A y B, 49 notas.
    **CAUSA RAÍZ Y DECISIONES (29/07/2026, usuario):** hay una **aprobación de talleres
    PENDIENTE en la UGEL de Huaraz** y por eso el SIAGIE no habilita esas hojas.
    **Taller de Raz. Mat. → SE DARÁ DE ALTA (sí o sí se registrará en el SIAGIE):**
    cuando la UGEL apruebe, el RegNotas traerá su hoja y bastará teclear su
    `codigo_siagie` en Currículo, sin despliegue; hasta entonces sus notas viven solo
    en SIGA y **no son un olvido que perseguir**. **Taller de Pre-Cálculo → NO se
    reporta** (decisión firme). La opción "área anfitriona" (etapa 2) queda descartada
    de hecho. Detalle en `docs/modulos/export-siagie.md`.
  **Diferido:**
  - **Taller SIN hoja propia** (reportar bajo un área anfitriona): es el caso
    peligroso — sus 3 competencias son homónimas de Matemática (C54↔C44, C55↔C47,
    C56↔C45) y exigiría invertir la regla "ante homónimos gana la competencia de la
    hoja", que es la que hoy protege el llenado de Matemática e Inglés. Requeriría el
    gestor de vínculos completo (etapa 2, columna→competencia).
  - **Selector de talleres por nómina** (efímero, sin flag persistente) — etapa 3.

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
  - **ACTUALIZACIÓN 04/08/2026 — la 541 YA NO ES `retirado`: es `trasladado`**
    (su traslado se consumó; `tipo_anterior='continuador'` intacto). El único
    `retirado` que queda en prod es la **357** (HUAMAN VIENRICH). Reparto actual de
    `matriculas.tipo`: 520 continuador · 9 trasladado · 5 nuevo · 1 retirado.
    - **No mueve nada del mérito:** ambos tipos están excluidos del roster en vivo, y
      el snapshot oficial de B1 está congelado en 528 (los 10 reincorporados de la
      Fase C son exactamente estas 10 matrículas: 191, 281, 307, 308, 333, 357, 541,
      581, 613, 646). 518 en vivo + 10 = 528 ✓.
    - **Sí cambia su BOLETA:** como `trasladado` la 541 pasa a calificar para la
      última boleta **OFICIAL** con estructura anual completa vía gestión, donde como
      `retirado` salía forzada a BORRADOR. Ver `docs/modulos/boletas.md`.
  - ✅ **Condición de borrado de los backups CUMPLIDA (verificado 04/08/2026):** la
    conducta de B2 tiene **23 cierres** (todas las secciones) y la sección de la 541
    —3° A, `seccion_id=18`— está entre ellas. **Ya se pueden hacer los `DROP TABLE`
    de `_bkp_conducta_resp_541` y `_bkp_calif_conducta_541` en prod** (siguen ahí).
- ✅ **ASISTENCIA DE B2 — REGISTRADA Y BLOQUEADA EN PROD (05/08/2026). Ya NO bloquea el
  cierre.** El usuario amplió `limite_notas` y capturó las 23 secciones entre el 04/08
  16:29 y el 05/08 00:01. **Verificado el 05/08** sobre la copia local sincronizada:
  **525 filas** en `inasistencias` de B2 contra un roster canónico de **524** →
  **0 huecos**; **23 de 23 secciones** con cierre vivo, sin duplicados; 352 alumnos con
  alguna incidencia y 173 en cero absoluto (que es el dato válido "registrado sin
  incidencias", distinto de "sin registro"). `verif_roster_asistencia.php` da **OK** en
  sus 3 bloques. Los 7 cierres anulados entre las 18:10 y las 23:52 corresponden a
  rehacer las secciones 1-6 tras aplicar el fix del roster.
  - ⚠️ **Al terminar, `limite_notas` quedó en `2026-08-04 23:59` → `periodoEditable(2)`
    es `false` otra vez.** Cualquier corrección de asistencia, conducta o notas de B2
    exige volver a ampliar el plazo (y eso reabre la calificación docente: re-medir el
    termómetro antes de cerrar).
  - 🔴 **SECUELA ABIERTA — DOBLE CONTEO EN LA BOLETA DE BALTAZAR PINTO, SHALOM CRISTEL
    (matrícula oficial 190 / operativa 692, retorno #1 del 21/06/2026).** Ambas matrículas
    quedaron con `faltas=2` en B2, y `getDelBimestreUnion` **suma las dos fuentes** → su
    boleta muestra **4 faltas** en vez de 2. Verificado ejecutando el modelo real
    (`UNION B1 -> 2` correcto, `UNION B2 -> 4` incorrecto).
    - **Origen:** la fila de la 190 se escribió el 04/08 a las **16:40:06**, con el roster
      VIEJO todavía activo. Rehacer la sección con el roster nuevo no la borró.
    - **La UI NO puede corregirlo:** con el roster nuevo la 190 no está en la grilla y
      `matriculaEnRoster` rechaza toda escritura sobre ella (403). Solo por SQL.
    - **REGLA (confirmada por el usuario el 05/08):** todo registro va a la matrícula
      **OPERATIVA**; el documento se emite con la **OFICIAL**. El corte es por bimestre:
      la fila de **B1 en la 190 es CORRECTA** (el retorno es del 21/06 y B1 cierra el
      16/06) y **no se toca**; la de **B2 sobra**.
    - **ACCIÓN en prod (antes de cerrar/publicar B2): aplicar la migración
      `047_retorno_grado_asistencia_solapada.sql`**, que trae PREVIEW, el DELETE
      acotado al solape y las consultas de verificación. Correr el PREVIEW primero:
      debe devolver exactamente 1 fila.
    - Las otras **11 filas fuera del roster son todas de B1** (trasladados/retirados),
      cada una de una sola matrícula → no duplican nada. No tocarlas.
    - Detalle de la trampa y consulta de guardia permanente: memoria
      `project_retorno_grado_doble_conteo`.
- **RETORNO DE GRADO — REGLA A IMPLEMENTADA (05/08/2026, en `dev`, SIN MIGRACIÓN).**
  Se evalúa en la matrícula **operativa**, se documenta con la **oficial**, y los
  datos **no se copian ni se mueven**. Doc completo: `docs/modulos/retorno-grado.md`.
  - **F1 — `BoletaPublicaModel` conoce el retorno.** Dos constantes privadas
    (`SQL_EXCLUIR_OPERATIVA`, `SQL_TIENE_BLOQUEOS`) aplicadas a las 3 consultas que
    alimentan índice, vista previa, impresión masiva y archivar. Corrige un defecto
    **preexistente desde el 21/06**: en B1 el estudiante salía **dos veces**
    (517→**516**) y en B2 **desaparecía** de su sección oficial (2° B: 18→**19**).
    El contador de 1° B en B1 pasó de mentir (19 aprobables / 18 generadas) a 18/18.
  - **F2 — candado del bimestre en curso + fin de la copia.** El retorno se bloquea
    si la oficial ya tiene notas, criterios u omisiones en un periodo `activo`
    (`evaluacionEnBimestreActivo`, en `create()` y `store()`). Se eliminó el
    `INSERT IGNORE` que duplicaba todas las calificaciones; asistencia y conducta
    de los bimestres **activos** ahora se **MUEVEN** a la operativa. El retorno real
    del 21/06 **pasa** el candado (no tenía nada en B2).
  - **F3 — token único.** `BoletaController::resolveToken` rechaza (404) el token de
    una matrícula operativa. Medido: **1 token de 531** deja de resolver, y nunca se
    generó boleta ni se consultó con él. Los QR ya emitidos no se ven afectados: se
    anclan a la matrícula IDENTIDAD, que en un retorno es siempre la oficial.
  - **Verificación:** `database/verificaciones/verif_retorno_grado.php` (solo lectura,
    corre en prod). Su bloque 1 prueba la **equivalencia** con la lógica anterior:
    B1 `viejo=517 nuevo=516 (sale 692)`, B2 `viejo=518 nuevo=518 (sale 692, entra 190)`,
    **0 matrículas ajenas afectadas**. El bloque 5 seguirá FALLANDO hasta que se
    aplique el `DELETE` de F0 en prod — es la señal de que hace su trabajo.
  - **Decisiones del usuario (no re-preguntar):** la copia de B1 **NO se borra** (es la
    base probatoria del snapshot de mérito publicado: sin ella el promedio 12.05 deja
    de ser reproducible); el token de la operativa se da de baja; la Regla A rige de
    aquí en adelante y **no se corrige el snapshot de B1**, que queda con la
    estudiante en 1° B por el candado 046.
  - **Pendiente relacionado:** la regla del retorno está escrita a mano en ~15 sitios.
    Unificarla en un punto único es un refactor con nombre propio, fuera de este lote.

  **Diagnóstico original (04/08/2026):** `inasistencias` tenía **528 filas en B1 y 0 en
  B2**; `cierres_asistencia`, 23 secciones en B1 y **0 en B2**. Cerrar y publicar así
  habría mandado a las familias asistencia **en ceros**, que es un dato FALSO, no ausente
  (la boleta pinta una columna por bimestre cerrado y suma lo que encuentre).
  - **Causa de que no se pudiera registrar:** `limite_notas` de B2 = **04/08/2026
    04:00**, ya vencido. El bimestre sigue `activo`, pero `AsistenciaModel::periodoEditable`
    exige `activo` **Y** estar dentro del plazo → la captura se cerró sola.
    ⚠️ **El registro de asistencia NO requiere el bimestre cerrado — requiere lo
    contrario.** Cerrarlo lo deja en solo lectura para siempre.
  - **Mismo plazo corta también notas y conducta** (`CalificacionModel::periodoEstaBloqueado`,
    `ConductaModel`), no solo asistencia.
  - **Secuencia ejecutada:** ampliar `limite_notas` desde `/director/anios/1` →
    desplegar el fix del roster → registrar las incidencias de las 23 secciones en
    `/admin/asistencia` → bloquear cada sección. **Cumplida.**
  - Que esto no se repita es el objeto del plan `docs/modulos/cierre-cuatro-registros.md`.
- ✅ **ROSTER DE ASISTENCIA ≠ ROSTER DE NOTAS — EN PRODUCCIÓN (commit `de449e2`, pusheado
  el 04/08/2026).** `/admin/asistencia` filtraba `m.estado='aprobada'` e ignoraba `tipo` y el
  retorno de grado, así que **los `pendiente` y `desactivado` no aparecían en la grilla**:
  nadie podía registrarles faltas y su boleta salía con 0 inasistencias (dato falso). A
  la vez mostraba la matrícula **oficial de un retorno activo**, o sea el grado donde la
  estudiante ya no está. Es el mismo arreglo que conducta recibió el 09/07 y que a
  asistencia se le quedó pendiente. Detalle en `docs/modulos/admin.md`.
  - **Impacto medido:** 6 matrículas entran (todas `pendiente`: 220, 470, 696, 690, 695,
    693) y 1 sale (la 190, oficial del retorno #1). **Las 6 quedaron registradas en B2**,
    o sea el fix cumplió su objetivo. `verif_roster_asistencia.php` (solo lectura, corre
    en prod) da OK en las 23 secciones.
  - ⚠️ **El orden se respetó a medias y dejó cola:** el registro empezó a las 16:29 con el
    roster VIEJO; a las 18:10 se anularon las secciones 1-6 y se rehicieron con el nuevo.
    Rehacer NO borra las filas que el roster nuevo dejó fuera → de ahí el doble conteo de
    la matrícula 190 (ver la entrada de asistencia de B2, arriba). **Lección: al cambiar
    un roster, rehacer no basta; hay que barrer las filas huérfanas.**
  - Decisión del usuario: aplica a **todos los periodos, incluidos los bloqueados**, así
    que el imprimible oficial de B1 se recalcula con el roster nuevo. Sin migración.
- ✅ **DECISIÓN CERRADA — ÉTICA Y VALORES ENTRA al orden de mérito en TODA secundaria,
  5.º incluido (05/08/2026). IMPLEMENTADO en `dev`, sin migración.**
  - **Razón:** Ética **no es tutoría**. Es la nota del área-curso *Educación Religiosa de
    secundaria*, que no tiene cargas propias (área 14: 0 cargas, 0 notas) y la evalúa el
    tutor por su carga TOE. Sin la excepción, **el mismo curso pesaba en primaria y no en
    secundaria** — una asimetría solo técnica.
  - **Deroga la regla del 04/08 que la sacaba de 5.º.** Aquella listaba «Ética y Valores»
    y «Educación Religiosa» como áreas distintas, siendo la misma. Además Ética **sí se
    dicta en 5.º** (50 notas en B2, bloqueadas), así que excluirla solo de ese grado
    habría exigido una excepción por grado hardcodeada en el SQL.
  - **Qué se tocó:** las 2 queries de `OrdenMeritoModel` (excepción por `nombre_boleta`);
    `verif_universo_merito.php` (Ética sale de las prohibidas de 5.º, sus 2 consultas
    replican la excepción y se añadió un **guard anti-duplicado** de Ed. Religiosa para
    los 5 grados); comentario de `ControlOperativoModel` (su filtro **ya** incluía Ética
    → convergió solo). `alerta_evaluacion_incompleta.sql` no necesitó cambios.
  - **Impacto medido con el MOTOR REAL (no solo promedio):** primaria **0 cambios**;
    secundaria mueve 29/18/7/9/13 puestos por grado (1.º a 5.º) con salto máximo **3**;
    **ningún primer puesto cambia** → la media beca no se altera. Tras el cambio:
    **0 empates pendientes** y **0 alumnos con evaluación incompleta** en B2.
    - ⚠️ La medición del 04/08 (76 puestos, salto 9, un primer puesto cambiando) era
      **incorrecta**: ordenaba solo por promedio y resolvía el área con `comp.area_id` en
      vez de `COALESCE(sa.area_id, comp.area_id)`, descartando las áreas con subáreas.
  - **B1 intacto por tres vías:** 0 notas de Ética; snapshot publicado e inmutable
    (candado 046); los lectores usan el snapshot (528 filas), no el cálculo en vivo.
  - **Alias del área 14 limpiado por el usuario** el 05/08 (`alias_boleta` de
    «(Ética y Valores)» a NULL): cierra el paso 3 del plan de encendido del 07/07 y
    elimina la ambigüedad que originó la regla errónea.
  - ⚠️ **Refuerzo recomendado, no hecho:** desactivar el área *Ed. Religiosa* de
    secundaria en `/admin/curriculum`. Ahora que Ética cuenta, una carga sobre esa área
    haría contar el **mismo curso dos veces**; hoy solo lo vigila el guard nuevo.
  - **Esto NO alinea SIGA con el SIAGIE** y no lo pretende: quedan 3 divergencias (GAMA
    va al acta y no al mérito; los 2 talleres cuentan en el mérito y no tienen hoja).
- **FORMATO OFICIAL EN TODAS LAS BOLETAS — CORREGIDO EN LOCAL EL 04/08/2026, SIN
  DESPLEGAR.** La regla de formato del 09/07 (las 4 columnas de bimestre siempre) se había
  aplicado solo a `/boleta/ver/{token}` y a la boleta del trasladado: la **impresión masiva**
  (`/admin/boletas-publicas/{id}/boletas-alumno`), el **ZIP de archivo** y la **digital de
  familias** llamaban a `armar()` sin el 4.º parámetro y colapsaban columnas. El papel que
  RA firma y entrega salía con **1 columna** mientras la misma boleta por QR salía con 4,
  siendo el mismo componente `boleta/alumno.php`. Ahora **las 9 entradas** pasan
  `estructuraCompleta = true`. Detalle en `docs/modulos/boletas.md`.
  - **No es una fuga:** el flag gobierna la estructura, no los datos. Verificado con
    `verif_estructura_boleta.php` (solo lectura, corre en prod): con `'oficial'` hay 4
    columnas y solo aportan notas los bimestres cerrados **y** publicados, aunque B2 ya
    tenga notas. Su paso 3 compara los datos con y sin el flag y exige que sean idénticos.
  - **Decidirlo antes de imprimir B2:** si ya se entregó papel de B1 con una columna, el de
    B2 saldrá con formato distinto al de B1. Sin migración.
  - **La TABLA DE ASISTENCIA también** (mismo día, tras revisar las 4 vistas donde se
    dibuja): siempre 4 columnas, en boleta oficial y digital. Cada columna lleva
    `sin_registro`, que se pinta con **guion apagado** —nunca `0`— cuando el bimestre es
    `pendiente` o no corresponde al umbral. Cuando es `true` **no se consulta** la
    asistencia, así que la columna vacía no sale de datos que ese umbral no debe ver. Se
    añadió `.bd-asistencia__num--pendiente` al SASS de la digital, que no tenía ningún
    estado para "sin dato" y habría pintado ceros. `admin/asistencia/imprimir.php` y
    `seccion.php` NO se tocan: son otro documento (alumnos × contadores de una sección).
  - **EMITIR el documento oficial ahora exige el bimestre CERRADO.** En
    `/admin/boletas-publicas/{id}`, "🖨 Boletas" y "🗂 Archivar" se condicionaban solo a
    "hay ≥1 competencia bloqueada", así que se podía imprimir un lote entero **con la
    columna del bimestre vacía**. Dos capas: botones inertes con aviso en la vista +
    guard en `boletasAlumno()`/`archivar()` (son rutas GET que quedan en marcadores). La
    **vista previa NO se bloquea** —es la herramienta para decidir el Hito A— ni el
    enlace por token de cada alumno. Criterio en `periodoEsOficial()`, vía
    `boleta_estado_bimestre()`. ⚠️ Es **cerrado, no publicado** (`'archivo'` ignora la
    044 a propósito), y el **Hito A tampoco habilita** (da `'borrador'`, no `'oficial'`).
  - **En el índice `/admin/boletas-publicas`, los bimestres `pendiente` no se abren:**
    tarjeta inerte con badge "No iniciado" + guard en `porPeriodo()`. El **activo** sigue
    accesible (ahí vive la vista previa). Hubo que añadir `.bp-periodo-card.is-disabled`
    (el `.btn.is-disabled` existente exige la clase `.btn`) y `p.estado` a la query.
- **ASISTENCIA EN LA VISTA PREVIA DE BOLETAS — CORREGIDO EN LOCAL EL 04/08/2026, SIN
  DESPLEGAR (posterior al deploy `de449e2`).** En
  `/admin/boletas-publicas/{id}/vista-previa` no aparecía la asistencia del bimestre en
  curso pese a tener las secciones bloqueadas: el cuadro se filtraba por
  `periodos.estado='cerrado'`, y **bloquear el registro de una sección
  (`cierres_asistencia`) NO cierra el bimestre**. La asistencia era además el único de
  los tres bloques por periodo que no honraba la excepción de la vista previa (notas y
  conducta sí). Ahora usa `periodoAportaNotas`, el mismo umbral de las notas.
  - Decisiones del usuario: alcance `'todos'` **y `'borrador'`**; los bimestres
    `pendiente` se pintan apagados (`--pendiente`, guion) en vez de con ceros; el total
    **suma el bimestre en curso**.
  - **`'oficial'` y `'archivo'` NO cambian** (equivalencia exacta verificada): las
    familias y el impreso siguen viendo solo bimestres cerrados —y publicados, en
    `'oficial'`—. Verificado con `verif_asistencia_boleta.php`, que simula el Hito A en
    transacción con ROLLBACK. Sin migración, sin Gulp (la clase CSS ya existía).
- **ORDEN ALFABÉTICO: LA Ñ IBA ANTES QUE LA N — CORREGIDO EN LOCAL EL 04/08/2026, SIN
  DESPLEGAR.** Detectado por el usuario en la grilla de 4° A primaria (ÑIQUEN PAJUELO
  salía antes que NOLASCO REYES). Causa: las columnas de `personas` son
  `utf8mb4_unicode_ci`, que equipara Ñ ≡ N. Arreglado con `COLLATE utf8mb4_spanish_ci`
  en los **30 `ORDER BY`** de 19 archivos, con punto único `COLLATE_ES` /
  `orden_alfabetico()` en `helpers.php`. Detalle y alternativas descartadas en
  `docs/modulos/ui.md`.
  - **Impacto:** 58 personas con Ñ, pero **solo 4° A de primaria** cambia de orden entre
    las 23 secciones. Actas SIAGIE y orden de mérito **no se ven afectados** (el matcher
    normaliza Ñ→N en PHP; el mérito ya no desempata por apellido).
  - **NO se cambió la colación de las columnas** a propósito: rompería la búsqueda
    tolerante a la ñ (hoy "NUNUVERO" encuentra a NUÑUVERO) y arriesga
    `Illegal mix of collations`. Sin migración.
  - Va en el **mismo deploy** que el roster de asistencia (decisión del usuario).
- **Validar en móvil real** el botón "✕ Cerrar" de documentos en ventana nueva
  (Chrome Android / Safari iOS): abrir varias boletas seguidas y confirmar que la
  pestaña se cierra y no se acumulan.
- **Digitar horarios reales en prod:** 1°A secundaria (11 cursos "sin horario
  propio" tras la migración 031) y las áreas sin bloques reales tras la 030
  (CyT/Matemática primaria 4°-6°, Arte y Cultura 1°A prim., etc.). 3°B ya está completo.
- ~~**Solape de CLEMENTE ANGELES (DPCC, lunes)**~~ **RESUELTO EN PROD EL 29/07/2026**
  (corregido por el usuario desde la UI; confirmó que el horario quedó bien). Se deja el
  diagnóstico porque el patrón puede repetirse. El dato anterior tenía las secciones
  invertidas. Real:
  **5° B 14:40-16:10** (correcto) vs **1° C 15:45-17:20** (bloque nº111, 95 min, FUERA
  de la grilla) → se pisan **25 min**. Son **dos** solapes: el docente y también la
  **sección 1° C**, que a esa hora tiene Matemática con BUENO. **Horario correcto
  (usuario):** DPCC 1° C = lunes **16:35-17:20** + jueves 13:10-13:55; 5° B = lunes
  14:40-16:10. El jueves y 5° B ya están bien → **la corrección es UNA sesión**: mover
  el lunes de 1° C al bloque 16:35-17:20. Franja destino verificada libre para sección y
  docente. Al guardar, `horas_semanales` baja de 3 a **2**, igualando a las otras 10
  cargas de DPCC (90 min); eso es lo correcto, no una pérdida. Se hace **por la UI**
  (`/director/cargas` → editar la carga), no por SQL. Detalle completo en
  `docs/modulos/horarios.md`.
- **Orden de mérito: RECONSTRUCCIÓN DE B1 HECHA Y VERIFICADA EN PROD (29/07/2026).**
  El snapshot oficial de B1 se reconstruyó el 25/07 (Fase C, ver "Rediseño del orden de
  mérito" abajo) y el **check quedó cerrado el 29/07**: la firma en prod da
  **528 filas / puestos 1-72 / 11 grados / 23 secciones** y los **10 reincorporados**
  (8 `trasladado` + 2 `retirado`: matrículas 333, 308, **357**, **541**, 581, 191, 613,
  307, 646, 281) salen cada uno en su puesto de grado y de sección. Eran 10, no 9 —
  la 357 (HUAMAN VIENRICH) también es `retirado`.
  Los lectores del snapshot (`OrdenMeritoModel::rankingGradoDesdeSnapshot` y
  `rankingPorSeccionDesdeSnapshot`) unen `matriculas` solo para llegar a la persona:
  **no re-filtran por `tipo` ni por `estado`**, por eso los reincorporados se pintan.
  ⚠️ No correr `backfill_orden_merito.php` en prod (desde el 26/07 tiene guard
  propio, pero la advertencia sigue valiendo para versiones desplegadas antes).
- **Cierre de B2 — SECUENCIA CORRECTA (fijada el 27/07/2026).** Los dos prerrequisitos
  del cierre (F4) NO se comportan igual, así que el orden importa:
  **docentes terminan de calificar y bloquear → deploy del rediseño 2 → medir →
  resolver → cerrar.**
  - ✅ **MEDICIÓN DEL 04/08/2026 (BD local sincronizada con PROD ese día) — LAS DOS
    CONDICIONES DURAS EN VERDE:**

    | Condición | Valor | Antes |
    |---|---|---|
    | Termómetro de bloqueos | **B1 = 0 · B2 = 0** | B2 = 102 (28/07) |
    | Alerta de evaluación incompleta B2 | **0 alumnos / 0 blancos** | 19/19 (27/07) |
    | Empates pendientes B2 | **1 grado: Secundaria 4°** | sin medir |
    | Empates pendientes B1 | 0 | 0 |

    - B2 tiene **28 270 calificaciones y 1 283 bloqueos, TODOS con `origen='docente'`**
      (ninguno forzado por cierre) → los docentes cerraron su parte en la fecha.
    - La alerta se midió por **dos vías coincidentes**: el SQL de
      `database/verificaciones/alerta_evaluacion_incompleta.sql` y el método PHP real
      `ControlOperativoModel::alertasEvaluacionIncompleta(2)`.
    - El empate de **Secundaria 4°** se midió **con el código de `dev`** (rediseño 2)
      contra datos de prod → **es lo que se verá después del deploy**, no un piso móvil.
      Se resuelve en la Fase 3 del runbook, DESPUÉS de mergear.
    - ⚠️ **Con el termómetro en 0, el hueco del guard de empates no muerde** (ver abajo):
      el universo validado y el congelado coinciden.
    - **El empate de Secundaria 4° lo resolvió el usuario el 04/08 a las 10:36** →
      B2 queda con **0 empates reales**. Al resolverlo afloró el bug de la card
      (ver el punto siguiente).
  - 🐞 **CARD DE EMPATES DE `/admin/control` — FANTASMAS IRRESOLUBLES (CORREGIDO el
    04/08/2026).** La card mostraba empates que ya estaban resueltos —o que nunca
    existieron— y no se limpiaba nunca.
    - **Causa:** el Centro de Control tenía su **propia copia de la cascada**
      (`ControlOperativoModel::detectarGruposIrreducibles`, nacida el 08/06 a las 08:36)
      que se quedó congelada en la tupla de 3 conteos (`num_c|num_b|num_ad`) y nunca
      incorporó los criterios de regularidad alta `num_alto`/`num_16`, que el motor
      real ganó **ese mismo día a las 12:59** (`d41c548`). Dos meses divergiendo.
    - **Por qué era irresoluble:** la pantalla donde se resuelve
      (`/director/orden-merito/{periodo}/desempate/{grado}`) usa el motor REAL, que no
      considera empatados a esos alumnos → nunca los ofrecía. Caso medido: Secundaria 4°
      B2, grupo {464, 652} — misma tupla de 3, pero `num_16` = 7 vs 2, así que el motor
      real los ordena solo (puestos 30 y 31).
    - **Alcance medido antes del arreglo:** B1 mostraba **7 grados** "pendientes" con
      **0 reales** (sus 14 desempates estaban resueltos desde junio); B2 mostraba 6 con
      0 reales.
    - **NO era una regresión del lote:** el método era byte-idéntico al de `origin/main`
      → el bug estaba **vivo en producción desde el 08/06/2026**. Tampoco afectaba a
      ningún dato: el guard del cierre, el snapshot y la boleta siempre usaron el motor
      real. Era un problema de confianza en la UI.
    - **Arreglo (opción A):** se borró la copia; `empatesPendientes` ahora **delega** en
      `OrdenMeritoModel::gradosConEmpatesPendientesDetalle`, punto único nuevo que el
      guard del cierre también consume (vía el wrapper de strings, cuyo contrato NO
      cambió). Se eliminó de paso la dependencia huérfana `DesempateMeritoModel` del
      Centro de Control. Sin migración; sin cambios de SASS.
    - **Verificación:** `database/verificaciones/verif_empates_card_control.php`
      (transacción + ROLLBACK). Retira temporalmente las resoluciones para tener
      empates de verdad y comprueba que card y motor real coinciden en grados **y**
      conteos: B1 → 3 grados / 4 grupos, B2 → 4 grados / 6 grupos; con las
      resoluciones puestas, ambos 0. Rollback verificado (14 y 7 resoluciones,
      42 filas de detalle).
  - 📋 **RUNBOOK EJECUTABLE: `docs/runbooks/cierre-de-bimestre.md`** (29/07/2026).
    Fases 0-6 con checklists, las consultas de prod ya probadas (termómetro, desglose
    por docente, verificación post-cierre), criterios de aborto y prohibiciones. Escrito
    para B2 y reutilizable en B3/B4 cambiando `@periodo`. **El día del cierre, seguir
    ese documento** en vez de reconstruir la secuencia de memoria.
  - **FECHA DURA: el cierre de notas de los docentes es el 31/07/2026** (dato del
    usuario, 28/07). **Decisión del 28/07: NO medir todavía** la alerta de evaluación
    incompleta ni perseguir docentes — se les deja terminar. Medir **después del
    31/07**, cuando las cifras dejen de ser un piso móvil. La herramienta está lista y
    no requiere edición (`alerta_evaluacion_incompleta.sql` ya trae `@periodo := 2`).
  - La **alerta de evaluación incompleta es estable**: su cálculo no mira
    `bloqueos_competencia` (depende de criterios con nota, cargas activas, omisiones y
    exoneraciones). Se puede medir HOY contra prod y el trabajo de resolverla —registrar
    la nota o la omisión desde el módulo del docente— vale igual antes o después del
    deploy. Herramienta: `database/verificaciones/alerta_evaluacion_incompleta.sql`
    (phpMyAdmin, solo lectura; el Centro de control ya la muestra pero está en `dev`).
  - Los **empates NO son estables**: P2 del rediseño 2 reduce el universo del cálculo en
    vivo a competencias BLOQUEADAS, así que cambian con el deploy; y una resolución se
    ancla al conjunto EXACTO de matrículas (`grupo_clave`), de modo que si el grupo
    cambia deja de cubrirlo y el empate reaparece. **Resolver empates va DESPUÉS del
    deploy y con todo bloqueado** (al cerrar, el propio cierre fuerza los bloqueos, así
    que el universo converge). Se consultan en `/director/orden-merito/{periodo}`, que
    ya lista los bimestres `activo` y está en prod desde el 17/06.
  - **Al 27/07 no hay ninguna decisión de desempate tomada para B2** (confirmado por el
    usuario): el bimestre no se ha cerrado, así que no hay trabajo que rehacer.
  - **OJO — LOCAL NO SIRVE para dimensionar esto:** B2 en local tiene **77
    calificaciones y 7 criterios** (B1 tiene 12 049 y 2 398). Los "19 alumnos de 4° A" y
    el "empate de Secundaria 1°" que se miden en local son artefactos del dataset de
    pruebas — el empate son 22 alumnos con `N=1`, una sola competencia calificada. Toda
    cifra de bloqueadores de B2 debe salir de PRODUCCIÓN.
  - La alerta **solo aflora un criterio cuando algún compañero de la sección ya tiene
    nota en él**: lo que se mida a mitad de bimestre es un PISO, no un total.
  - **TERMÓMETRO DE BLOQUEOS — medido en PROD el 28/07/2026: B1 = 0, B2 = 102.**
    *(SUPERADO: el 04/08 da B2 = 0 — ver la medición de arriba. Se conserva la
    definición porque es la consulta que hay que repetir en cada cierre.)*
    Cuenta pares carga+competencia **con notas y sin fila en `bloqueos_competencia`**
    (`LEFT JOIN … WHERE bc.id IS NULL`, agrupado por `periodo_id`). Es el indicador de
    "listos para cerrar": **cuando dé 0, los docentes terminaron.** También es un piso
    (no ve lo aún no calificado), y tiene variante que desglosa por docente/sección
    (join a `cargas_academicas` + `usuarios`/`personas`) para saber a quién apurar.
    El **B1 = 0 confirma de forma independiente** que el snapshot oficial de 528 no
    arrastra notas sin bloqueo → P2 del rediseño 2 no le mueve un puesto.
  - ⚠️ **HUECO DEL GUARD DE EMPATES (hallazgo del 28/07/2026 — NO corregido, por
    decisión).** En `Director/PeriodoController::cerrar` el guard de empates corre en
    `:124`, pero `bloquearCompetenciasPendientes` está en `:155` y `registrarRanking`
    en `:173`. Como `gradosConEmpatesPendientes` (`OrdenMeritoModel:666`) y
    `calcularFilasRanking` (`:417`) hacen `INNER JOIN bloqueos_competencia`, **el guard
    valida un universo más chico que el que se congela**: cerrar con pares sin bloquear
    puede PETRIFICAR empates que nadie vio. Lo que `orden-merito-rediseno.md` llama
    "diferencia consciente" (el cierre no valida P3 porque él mismo fuerza los bloqueos)
    es justamente el origen del hueco: forzarlos DESPUÉS de validar hace que el conjunto
    validado no sea el congelado.
    - **Gravedad baja y REVERSIBLE mientras B2 no se publique:** sin publicación el
      candado 046 no se activa, `registrarRanking` sigue escribiendo el OFICIAL y basta
      reabrir → resolver → re-cerrar (costo: las boletas vuelven a BORRADOR). La ventana
      irreversible se abre al **publicar**.
    - **Decisión (28/07): opción C — no se toca el código.** Regla operativa:
      **exigir que el termómetro dé 0 ANTES de pulsar Cerrar**; con 0 el hueco no
      existe. Se descartó A (guard previo "0 sin bloquear"): mataría la válvula de
      escape del bloqueo forzado, útil si un docente de licencia nunca bloquea.
    - **Pendiente para DESPUÉS del cierre de B2 — opción B:** mover el guard de empates
      a después del bloqueo forzado, dentro de la transacción y con rollback. Es la
      corrección estructural correcta; no se estrena bajo presión en el cierre.
- ~~**Retorno de grado de BALTAZAR SHALOM CRISTEL — BLOQUEARÁ EL CIERRE DE B2.**~~
  **RESUELTO PARA B2 (verificado el 04/08/2026):** la alerta de evaluación incompleta
  de B2 da **0** y la matrícula **692 ya no aparece** en el detalle por alumno. El
  riesgo que se anticipó abajo no llegó a materializarse como bloqueo del cierre.
  - ⚠️ **En B1 SIGUE ABIERTO y ahora tiene consecuencia:** B1 arroja **12 alumnos**
    con blancos sin motivo (692 entre ellos, con 69 blancos). Mientras B1 esté
    **cerrado** la alerta ahí es solo informativa (fix `af72ac7`), pero el guard P4
    llega con este lote → **si alguna vez se REABRE B1, no se podrá volver a cerrar**
    hasta resolver esos 12. Tenerlo presente antes de reabrir B1 para una
    rectificación. Ver "Efecto colateral del guard P4" en Pendientes de desarrollo.
  - Se conserva el diagnóstico completo porque el patrón (evaluación registrada en las
    cargas del grado oficial en vez de las del operativo) puede repetirse en B3/B4.

  **Diagnóstico original (26-27/07/2026):**
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
- ~~**31/07/2026 — CIERRE DE NOTAS DE LOS DOCENTES (II Bimestre).**~~ **CUMPLIDA.**
  Era la fecha límite para que terminaran de calificar y **bloquear**. Medido el
  **04/08/2026**: termómetro de bloqueos **B2 = 0** (1 283 bloqueos, todos con
  `origen='docente'`) y alerta de evaluación incompleta **B2 = 0**. Los docentes
  cumplieron. Siguiente paso de la secuencia: **deploy** → resolver el empate de
  Secundaria 4° → cerrar. Detalle en "Cierre de B2 — SECUENCIA CORRECTA", en
  Pendientes operativos, y el procedimiento en
  `docs/runbooks/cierre-de-bimestre.md`.
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
- **29/07/2026 — foto verificada: `dev` en `25599ba`, `main` en `68968bb`,
  28 commits por delante.** El lote acumulado es: rediseño 2 del orden de mérito +
  excepciones de hoja SIAGIE (Ética→EREL, GAMA→EPT de 5°) + vínculos/cobertura con
  `codigo_siagie` editable. **Sigue SIN migración pendiente** → el deploy es merge +
  push, sin tocar la BD de prod. **NO autorizado todavía**; va después del 31/07
  (cierre de notas de los docentes) y ANTES de medir/resolver empates.
- **04/08/2026 — foto verificada: `dev` en `c8c218d` (= `origin/dev`), `origin/main`
  en `68968bb`, 39 commits por delante.** Suma al lote del 29/07 el **semáforo de las
  cards del dashboard docente** (`05ac6ea`: gris "todavía no te toca" / ámbar / verde,
  en Transversales y Conducta) y el merge de `origin/dev`. Árbol de trabajo LIMPIO.
  **`origin/main` es ancestro directo de `dev` → el merge sería fast-forward.**
  Sigue SIN migración pendiente. Verificado el mismo día: `gulp build` reproduce
  `public/css/app.css` y `public/js/` **idénticos** a lo commiteado (sin CSS
  desincronizado), y los 29 archivos PHP del lote pasan `php -l`.
  - ⚠️ **`main` LOCAL estaba 6 commits detrás de `origin/main`.** Un
    `git checkout main && git merge dev` sin actualizar antes NO da el fast-forward
    esperado. **RESUELTO el 04/08:** se puso `main` al día (`10d6d51`→`68968bb`) y se
    mergeó `dev` por fast-forward → **`main` local en `0e250d1`, SIN PUSHEAR**
    (producción sigue en `68968bb`; el auto-deploy no se disparó).
  - **04/08 (después del merge local) — `dev` en `495cb3d`:** suma el fix de la card
    de empates de `/admin/control` (ver "CARD DE EMPATES" en Pendientes operativos).
    `main` local quedó **un commit por detrás de `dev`** → hay que repetir el
    fast-forward antes de pushear. Sigue sin migración.
  - **Verificado contra la copia fresca de prod (04/08):** las 4 verificaciones del
    mérito pasan (fase A 6/6 · fase B candado 046 + rollback · fase 1 snapshot 528 ·
    fase 5b control discrimina 518≠528) y **no dejan rastro** — tras correrlas el
    snapshot sigue en 528 filas, 0 rectificados y 14 desempates.
- **04/08/2026 — DEPLOY EJECUTADO: `origin/main` pasó de `68968bb` a `de449e2`.**
  Entró a producción el lote acumulado: rediseño 2 del orden de mérito completo,
  excepciones de hoja SIAGIE, vínculos/cobertura con `codigo_siagie` editable, semáforo
  de las cards del dashboard docente, fix de la card de empates, fix del orden
  alfabético con la Ñ y **el fix del roster de asistencia** (que habilitó la captura de
  B2 esa misma tarde). Sin migración.
- **05/08/2026 — foto verificada: `dev` = `origin/dev` = `fae5481`, `origin/main` =
  `de449e2` → 4 commits SIN desplegar.** Árbol limpio. Son `7e40a3d` (la asistencia de
  la boleta usa el mismo umbral que las notas), `c2865e2` (formato oficial —las 4
  columnas— en las 9 entradas de boleta), `ab3966e` (los bimestres `pendiente` no se
  abren desde el índice) y `fae5481` (docs + **la exclusión de Ética del mérito**).
  Sigue sin migración → el deploy sería merge + push.
  - **Sobre Ética:** el commit `fae5481` la EXCLUÍA del mérito, pero la decisión se cerró
    al revés el 05/08 y ya está implementada encima. El lote a desplegar deja el
    comportamiento final: **Ética CUENTA en toda secundaria**. Producción, que nunca
    recibió la exclusión, converge con el estado correcto al desplegarse el lote.
  - ⚠️ **`main` LOCAL quedó en `0e250d1`, por DETRÁS de `origin/main`.** Es la trampa de
    siempre: actualizar `main` antes de mergear, o el fast-forward no sale.

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
