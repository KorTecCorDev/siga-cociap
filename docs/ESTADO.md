# ESTADO vivo del proyecto

> Único lugar donde se registran pendientes, migraciones y planes con fecha.
> Actualizar aquí (no en CLAUDE.md). Última revisión: **07/08/2026**.

## Migraciones
- **`051_limpieza_bloqueos_transversales_fantasma`** (06/08): corrección de DATOS (no toca
  esquema). Borra los bloqueos transversales que el **cierre forzado** creó en **B2** sobre
  cargas que ningún docente puede bloquear: **46 en 23 cargas TOE + 84 en 42 cargas
  no-dueñas** de secciones unidocentes = **130**, con **CERO olvidos reales**.
  ✅ **APLICADA EN PRODUCCIÓN EL 06/08/2026**, después del deploy `cf8bdb2` que subió el
  fix F1 (el orden se respetó: sin F1 arriba, el siguiente cierre los recrearía).
  **NO aplicada en local a propósito**: allí siguen los 130 para poder reproducir el
  escenario.
  - **Evidencia capturada EN PROD** (huella `u761410128_siga_cociap` · Linux ·
    **MariaDB 11.8.8**): PASO 1.b **46/23 + 84/42 y C_OLVIDO_REAL sin ninguna fila** ·
    1.c **0 notas / 0 criterios** colgando · 1.d 690 y 23 → PASO 2 **"130 filas
    eliminadas"** + COMMIT → PASO 3 en conexión nueva: **0/0/0**, 690 y 23 **intactos**,
    **0** notas sin bloqueo y B1 en **774**.
  - 🔎 **UNA HIPÓTESIS QUE LOS HECHOS DESMINTIERON.** Antes de aplicarla se advirtió que
    en prod podía no haber **nada** que borrar, razonando que B2 seguía ABIERTO y que los
    fantasmas los crea el cierre. **FALSO: estaban los 130, exactamente los mismos que en
    local.** **Lección: el estado ACTUAL de un periodo no dice nada sobre los procesos que
    ya corrieron sobre él; eso solo lo responden los datos.**
    - 🔴 **CORREGIDO EL 07/08/2026 — LA EXPLICACIÓN QUE SE DIO AQUÍ ERA FALSA.** Se
      concluyó que "en prod el cierre forzado de B2 sí llegó a correr y el bimestre se
      reabrió después". **No fue el cierre: fue el HITO A.** Medido con local ya
      sincronizada con prod: `periodos.boletas_aprobadas_en` de B2 =
      **`2026-08-05 20:09:33`**, `estado='activo'`, **sin ninguna fila de B2 en
      `orden_merito_snapshot`** y **sin reaperturas posteriores al 16/06** (reabrir exige
      motivo y SIEMPRE deja fila en `reaperturas_periodo`). **B2 nunca se ha cerrado.**
    - **Por qué el error era fácil:** `bloquearCompetenciasPendientes` tiene **dos**
      llamadores, no uno — `cerrar()` y `aprobarBoletasBimestre()` (**Hito A**). El Hito A
      fuerza los MISMOS bloqueos con `origen='cierre'` pero **deja el periodo `activo`**, y
      `anularAprobacionBoletas` lo revierte **sin tocar los bloqueos** (lo dice su propio
      comentario). Cronología que encaja al segundo: Hito A el **05/08 20:09** → nacen los
      130 → F1 a producción el **06/08** (`cf8bdb2`) → la `051` los limpia.
    - **La conclusión operativa NO cambia** (F1 antes que la 051, y los 130 fuera), pero la
      secuencia documentada vigilaba la puerta equivocada: **pulsar el Hito A también los
      recreaba**. Con F1 en prod ya no. El runbook incorpora el Hito A en su **Fase 0.2**,
      con la consulta que distingue un Hito A de un cierre real.
  - ⚠️ **`SELECT ROW_COUNT()` DEVUELVE 0 EN phpMyAdmin y NO significa que el DELETE
    fallara.** Ejecuta las sentencias por separado, así que el contador ya no refleja al
    DELETE. La cifra buena es la del propio DELETE (**"130 filas eliminadas"**) y quien
    manda es el PASO 3. Pasó tal cual al aplicarla. **Vale para toda migración futura que
    copie este patrón.**
  - **Aborta** si aparece un solo `C_OLVIDO_REAL` (sería un bloqueo legítimo) o si alguna
    de esas cargas tiene notas o criterios transversales colgando.
  - Ancla el periodo por `numero = 2` + año activo, **nunca por `id`**. **B1 no se toca**
    (decisión del usuario), aunque 84 de sus forzadas sean el mismo defecto.
    Idempotente. Reversible con el PASO 4.
  - Ver `docs/modulos/transversales-visibilidad-tutor.md` §5.
- **`050_etica_b1_extraordinaria`** (06/08): registra **15 (literal A) como CALIFICACIÓN
  EXTRAORDINARIA** de Ética y Valores en el **I Bimestre** a los **275** estudiantes de
  secundaria **que cursaron B1**.
  ✅ **APLICADA EN PRODUCCIÓN el 06/08/2026 a las 20:01:10**, con la verificación posterior
  capturada ALLÍ (la regla que dejó la 048). **NO aplicada en local**, a propósito: local
  queda como copia del estado previo hasta la próxima sincronización.
  - **Evidencia en prod:** 11 criterios · 275 notas de criterio · 275 calificaciones · 275
    marcadas extraordinarias · **0** con nota distinta de 15 · 275 filas de auditoría;
    `MOTIVO OK` (370 caracteres / 378 bytes, sin mojibake); snapshot oficial de B1 **intacto
    en 528 / puestos 1-72 / 11 grados / 23 secciones**; **0** notas que entren al mérito; e
    integridad en **0 · 0 · 0** (ninguna matrícula con dos notas, ninguna nota en carga
    ajena a su sección, ninguna sin bloqueo).
  - **Contraste cruzado independiente:** los pares *bloqueados y vacíos* de B1 bajaron
    **exactamente 11** (890 → 879 con la consulta sin filtrar; equivale a **116 → 105** con
    la definición correcta del runbook). Los 11 pares de Ética dejaron de estar vacíos.
  - **PROCEDIMIENTO QUE FUNCIONÓ, y conviene repetir:** PASO 1 en tres envíos de solo
    lectura → **ENSAYO EN LA PROPIA PRODUCCIÓN** con `START TRANSACTION … ROLLBACK`
    (mismas cifras, sin escribir) → confirmación de que el rollback limpió (0·0·0) y de que
    las 4 tablas son **InnoDB** → envío definitivo idéntico terminado en `COMMIT` →
    verificación **en conexión nueva** (lo único que prueba que el COMMIT persistió; las
    SELECT de dentro del bloque no lo prueban).
  - **Por qué existe:** en B1 nadie evaluó Ética —los tutores no remitieron a tiempo— y por
    acuerdo de dirección se consignó 15 (A) uniforme, **ya cargado a mano en el SIAGIE** en
    las dos competencias de Ed. Religiosa (vínculo `035-EREL`). Esto alinea SIGA con el acta.
  - 🔴 **HALLAZGO GRAVE — B1 SE CERRÓ CON ÉTICA BLOQUEADA Y VACÍA.** El **20/07/2026 entre
    la 01:44:33 y la 01:45:33** se bloqueó la competencia en las **11 secciones** de
    secundaria (`origen='docente'`, 11 filas en 60 segundos) **con CERO notas**, y horas
    después se cerró B1. El sistema quedó afirmando que Ética estaba evaluada y terminada.
    **Ningún indicador del cierre podía detectarlo:** el **termómetro** cuenta pares *con
    notas y sin bloqueo*, así que un par **sin notas** nunca aparece (por eso B1 daba 0); y
    la **alerta de evaluación incompleta** solo aflora un criterio cuando algún compañero de
    sección ya tiene nota en él, así que si **nadie** la tiene no hay con qué comparar.
    Es un punto ciego real del contrato del cierre → refuerza el plan de los 4 registros.
  - **Universo = 275, anclado en "tiene ≥1 nota en B1"**, no en `tipo` ni en ids. Coincide
    **exactamente** con los 275 de secundaria del snapshot oficial de B1, o sea reproduce el
    roster que se congeló. Desglose: 1.º 72 · 2.º 52 · 3.º 47 · 4.º 55 · 5.º 49. Deja fuera
    a **693, 694, 695 y 696** (llegaron entre junio y julio, 0 notas en B1): darles nota
    sería inventarles un bimestre. 0 exonerados en secundaria.
  - **EL SNAPSHOT DE B1 NO SE MUEVE**, por tres vías: `OrdenMeritoModel` filtra
    `extraordinaria = 0` en sus 2 queries; el oficial es inmutable (candado 046); y los
    lectores usan el snapshot, no el vivo. **Verificado en el ensayo: 528 filas, puestos
    1-72, 11 grados, 23 secciones, y 0 notas nuevas entrarían al mérito.**
  - **Replica el flujo real** de `guardarExtraordinaria` (leído línea por línea): criterio
    único por carga (11) → nota de criterio → calificación con `extraordinaria=1` → fila de
    auditoría con el **motivo**, que es el único registro permanente de por qué existen.
    Firma **Registro Académico**, resuelto por rol.
  - ✅ **AUDITADA Y ENDURECIDA EL 06/08/2026, antes de aplicarla en prod.** Se contrastó
    contra `RectificacionModel::esInsertable` (la guarda que el flujo real evalúa alumno
    por alumno) y contra el esquema real. **Los 4 arreglos son no-op en local** —las tres
    variantes del universo dan **275**— así que **no invalidan el ensayo**: solo cierran
    huecos que en PROD sí pueden morder.
    - **El filtro de exoneraciones era GLOBAL por matrícula** y sacaba del universo, sin
      decirlo, a quien estuviera exonerado de **cualquier** área. El código acota por
      `area_id`/`subarea_id` **+ `anio_id`**; la migración ahora también. En local no mordía
      (las 2 exoneraciones vivas son de PRIMARIA, área 5), pero una sola exoneración de
      secundaria en prod habría bajado el universo a 274 sin explicación.
    - **No excluía la matrícula OFICIAL de un retorno de grado activo.** Registrar una nota
      es EVALUAR → Regla A: se evalúa en la **operativa**. Es el mismo anclaje de los 9
      rosters y de `alertasEvaluacionIncompleta`. Hoy el único retorno es de primaria, así
      que no mordía; con un retorno en secundaria habría escrito la nota en la sección donde
      la estudiante ya no cursa, y la boleta **suma las dos fuentes**.
    - **El `uq_nota` NO protege del doble registro**: su clave es (matrícula, **carga**,
      periodo, competencia), así que una sección con dos cargas activas del área daría dos
      notas al mismo alumno sin violar nada (`cargas_academicas` no tiene UNIQUE KEY). El
      PASO 1 exige ahora 1 carga por sección y el PASO 3 lo verifica después.
    - **No verificaba los bloqueos.** La boleta solo muestra competencias **bloqueadas**;
      una carga sin bloqueo habría recibido la nota sin mostrarla — la migración
      "funcionando" sin cumplir su objetivo. Medido en local: 11 cargas, 11 secciones,
      11 bloqueos, 0 sin bloquear.
    - **Firmante:** el anclaje del usuario ahora exige `estado = 'activo'` (un RA dado de
      baja con id menor habría firmado las 275 filas). Y el PASO 1 suma un **1.e** que lista
      con motivo a los excluidos por las guardas nuevas: en prod hay que **leerlo**, cada
      fila es un alumno que cursó B1 y no recibirá la nota.
    - **Re-ensayada entera tras los cambios** (`START TRANSACTION … ROLLBACK`): mismas
      cifras que el ensayo original —11 criterios · 275 · 275 · 275, `MOTIVO OK`, snapshot
      en 528/1-72/11/23— más los 3 checks de integridad nuevos en **0**, y local en 0 tras
      el rollback.
    - ✅ **DESCARTADO UN EFECTO COLATERAL QUE PARECÍA REAL:** el criterio nuevo NO agrava
      los **12 blancos sin motivo de B1** (que hoy impedirían re-cerrarlo si se reabriera).
      `alertasEvaluacionIncompleta` filtra `cr.extraordinario = 0`, así que el criterio
      extraordinario le es invisible; los 4 alumnos que llegaron después de B1 no se suman.
      Verificado en el código, no supuesto.
    - **Efecto medible esperado en el runbook:** los **11 pares bloqueados-y-vacíos de
      Ética** dejan de serlo → el conteo de B1 baja de **116 a 105**. El termómetro de
      bloqueos no se mueve (esas cargas ya estaban bloqueadas).
  - 🔎 **HALLAZGO DEL DÍA DE LA APLICACIÓN — prod y local NO corren la misma MariaDB:**
    local **10.4.32**, prod **11.8.8** (visto con la huella nueva del PASO 1.0). Un ensayo
    en local prueba la LÓGICA, no el plan del optimizador. Por eso el PASO 2 pasó a
    ejecutarse **siempre envuelto en transacción**, también en prod: son 4 INSERT
    dependientes y un fallo a mitad dejaría criterios y notas de criterio sin calificación.
    El patrón `NOT EXISTS (SELECT 1 FROM (SELECT * FROM tabla) x)` se comportó igual en
    11.8 — verificado en el ensayo sobre prod, no supuesto.
  - 🔎 **LA LECCIÓN DE TRAZABILIDAD DE LA 048, RESUELTA:** ningún conteo de DATOS distingue
    local de prod (local es copia fiel: mismas 28 270 notas de B2, mismos ids). La huella
    tiene que ser del **SERVIDOR** — `DATABASE()`, `USER()`, `@@hostname`,
    `@@version_compile_os`, `@@datadir` — y ahora es el **PASO 1.0** de la migración.
    Local: `siga_cociap` · `root@localhost` · **Win64**. Prod: `u761410128_siga_cociap` ·
    Linux · Hostinger.
  - ⚠️ **phpMyAdmin IGNORA `USE`:** reselecciona la base según el contexto de la página. Si
    la pestaña SQL cuelga de `information_schema`, todo se ejecuta allí y las consultas
    fallan con `#1109 Tabla desconocida`. Pasó durante esta aplicación. Se arregla entrando
    a la base desde el panel izquierdo y se comprueba con `SELECT DATABASE()`. No es un
    riesgo de escritura: el bloque falla en su primera sentencia y la transacción queda
    vacía.
  - ✅ **ENSAYADA ENTERA EN LOCAL con `START TRANSACTION … ROLLBACK`** (no hay DDL, a
    diferencia de la 048): **11 criterios · 275 notas de criterio · 275 calificaciones,
    todas marcadas y ninguna distinta de 15 · 275 filas de auditoría**, y local quedó en 0
    tras el rollback. **Reversible** con el DELETE acotado del PASO 4.
  - 🔴 **EFECTO VISIBLE INMEDIATO:** B1 está **publicado**, así que al aplicarla la boleta
    digital por token de B1 muestra el 15 a las familias. **No existe forma de registrar el
    dato y diferir su visibilidad.** Decisión del usuario: no hace falta reimprimir el papel
    de B1; basta con que salga en la boleta que se entregue en B2 (documento anual).
  - ⚠️ **Dos trampas encontradas AL ENSAYARLA**, ambas corregidas y anotadas en el archivo:
    - **Anclar el rol por `= 'Registro Académico'` falla si el cliente no envía la tilde en
      UTF-8** → el anclaje resuelve NULL y la migración inserta 0 filas sin decir por qué.
      Pasó de verdad en el primer ensayo. Ahora usa `LIKE 'Registro Acad%'` (ASCII).
    - **El detector de mojibake del motivo daba FALSO POSITIVO:** `motivo` es
      `utf8mb4_unicode_ci`, la colación que equipara **Ã con A** —la misma que hacía Ñ ≡ N
      en el orden alfabético—, así que `INSTR` encontraba la primera 'a'. La comparación va
      ahora **sobre bytes** (`CONVERT(... USING binary)`). Probado en ambos sentidos.
- **`048_limpieza_backups_conducta_541`** (05/08): retira las dos tablas de respaldo que
  dejó la limpieza quirúrgica de conducta del 24/07 (`_bkp_conducta_resp_541` con 10 filas
  y `_bkp_calif_conducta_541` con 0). **La condición acordada se cumplió**: la conducta de
  B2 de su sección (18) está cerrada en sus DOS etapas — cierre id 33, `ra_bloqueado_en`
  24/07 16:14 y `tutor_cerrado_en` 31/07 12:32, sin anular.
  Trae **PASO 1 de verificación (solo lectura) que debe devolver `PUEDE_BORRARSE`**, el
  `DROP` y una verificación posterior. Idempotente (`IF EXISTS`).
  🔴 **IRREVERSIBLE y NO probable con rollback**: `DROP TABLE` es DDL y MySQL hace commit
  implícito. Por eso el archivo insiste en exportar antes (phpMyAdmin → Exportar las 2
  tablas; `mysqldump` solo si hay shell).
  ✅ **APLICADA EN LOS DOS ENTORNOS el 06/08/2026, con evidencia del PASO 3 en cada uno.**
  Se aplicó **primero en LOCAL y después en PROD** (ver la lección de trazabilidad, abajo).
  - **LOCAL** — PASO 3 en **0 filas**; esquema en 51 tablas y **ninguna empieza por `_`**.
    Sin daño colateral: la conducta de la 541 conserva su calificación de **B1 (`AD`)**,
    sus respuestas siguen en 0 (lo esperado desde la limpieza del 24/07), el cierre 33
    intacto y el volumen global sin moverse (5240 respuestas · 1052 calificaciones · 46
    cierres, todos vivos). Al re-ejecutar el PASO 1 sigue dando `PUEDE_BORRARSE` y la 2.ª
    consulta falla con `Table doesn't exist`: **la señal esperada de que el PASO 2 corrió**.
  - **PROD** — el PASO 1 devolvió 1 fila, `PUEDE_BORRARSE`, matrícula 541 / DNI 63361405
    (RODRIGUEZ MENDEZ, GUSTAVO CHRISTIAN), sección 18, `trasladado`, cierre id 33,
    `ra_bloqueado_en` 2026-07-24 16:14:04 y `tutor_cerrado_en` 2026-07-31 12:32:54, sin
    anular. **La constancia dio 10 y 0 filas**, o sea que allí los respaldos seguían vivos
    hasta ese momento; los dos `DROP` se ejecutaron sin error y el **PASO 3 devolvió 0
    filas**.
  - ⚠️ **LECCIÓN DE TRAZABILIDAD — SE MATERIALIZÓ, no es hipotética.** La salida del PASO 1
    es **idéntica en local y en prod** (local es copia de prod: mismos ids y mismas fechas
    al segundo), así que **no sirve para saber contra qué entorno se ejecutó**. Por eso la
    primera corrida se dio por hecha en prod cuando en realidad había caído en **local**, y
    se detectó por un camino indirecto: local ya no tenía las tablas y la constancia de la
    corrida siguiente devolvió 10 y 0 —prueba de que ESE entorno aún las tenía—.
    **Regla: una migración solo se da por aplicada en un entorno cuando se captura su
    PASO 3 ALLÍ**; el veredicto previo no identifica el entorno. Aplica a la `049` y a
    todas las que vengan.
  - ⚠️ **El archivo se ejecutó ENTERO de una pasada** en phpMyAdmin (PASO 1 + 2 + 3 en la
    misma corrida), que es exactamente lo que su propia cabecera advierte que no hay que
    hacer. Salió bien **porque el veredicto era verde**; con un `NO_BORRAR` el `DROP` se
    habría ejecutado igual. La advertencia sigue en pie para la próxima.
  - **Endurecimiento del PASO 1 (06/08, commits `221440f` y `df186f2`), hecho ANTES de
    aplicarla:** juzgaba por `matriculas.id = 541`, contra la regla de anclar por DNI. Un
    id que apuntara a otro estudiante habría devuelto un veredicto **válido sobre la
    sección equivocada**, y un id inexistente devolvía 0 filas, que se lee como "no aplica"
    en vez de "detente". Ahora exige `id AND dni`, devuelve la identidad junto al veredicto
    y define 0 filas como aborto. Además: `ORDER BY anio DESC` en el año activo (`LIMIT 1`
    era no determinista), `LIKE '\_bkp%'` escapado en el PASO 3 (el guion bajo es comodín)
    y el aviso de que **el PASO 1 no protege al PASO 2** — son sentencias sueltas y pegar
    el archivo entero ejecuta el `DROP` igual; no es automatizable porque DDL hace commit
    implícito.
  - **Auditoría previa (06/08, solo lectura):** 0 claves foráneas apuntando a los
    respaldos, **ningún código de la aplicación los lee** (`_bkp` solo aparece en el
    propio `.sql` y en dos docs) y ninguna otra tabla del esquema empieza con `_`. El
    borrado no podía romper nada en runtime: 10 filas, 16 KB.
  - **Aclaración que costó una falsa alarma:** la 541 **sí** tiene una fila viva en
    `calificaciones_conducta`, pero es del **I Bimestre** (literal AD, 23/05). La limpieza
    del 24/07 fue de B2, y por eso `_bkp_calif_conducta_541` estaba en 0. Quedó anotado en
    la cabecera del `.sql` para que nadie repita la investigación.
- **`047_retorno_grado_asistencia_solapada`** (05/08): corrección de DATOS (no toca
  esquema). Borra la fila de `inasistencias` que quedó en la matrícula **OFICIAL** de
  un retorno de grado cuando la **OPERATIVA** ya tiene fila del mismo bimestre. Con
  el solape, `getDelBimestreUnion` —que SUMA las dos fuentes— mostraba el **doble de
  inasistencias** en la boleta (caso real: 4 faltas en vez de 2 en B2). Ancla por el
  vínculo `retornos_grado`, **no por ids**; se autolimita al solape y exige
  `p.fecha_fin >= r.fecha_retorno`, así que **no puede** tocar un bimestre anterior al
  retorno. Idempotente (verificada con 2 corridas: 1 fila y luego 0). Probada
  ejecutando el archivo real en transacción con ROLLBACK.
  **APLICADA EN LOCAL Y PROD** (local verificado el 05/08: `inasistencias` pasó de 1053
  a 1052 y la fila de la matrícula oficial en B2 ya no existe; **PROD el 05/08/2026,
  confirmado por el usuario**, antes del merge `dev`→`main` que desplegó el lote de
  boleta/borradores/exoneraciones).
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
- ✅ **LOS DOS ENTORNOS AL DÍA HASTA LA `051` (verificado en local el 07/08/2026).** La 047
  el 05/08, la 048 el 06/08 en ambos, la 051 en prod el 06/08 y en local el 07/08. La
  **`050`, que estuvo un día solo en prod, YA ESTÁ TAMBIÉN EN LOCAL**: el usuario
  resincronizó la copia. **Huella medida en local** (`siga_cociap` · `root@localhost` ·
  PROBOOK450 · MariaDB 10.4.32 · Win64): **275 extraordinarias de nota 15 en B1** · 11
  criterios extraordinarios · 275 filas de auditoría · B1 pasó de 12 047 a **12 322**
  calificaciones (+275 exactas) · **0 tablas `_bkp`** (048) · transversales de B2 en **690
  `docente` y 0 `cierre`** (051).
  - ⚠️ **Queda obsoleta la regla "una medición local de Ética en B1 da 0 y no es un
    error"** — ahora da 275 en los dos entornos. **Antes de reportar cualquier divergencia
    entre local y la documentación, comprobar la frescura de la copia con un marcador de
    migración**: el 07/08 se reportó como "error de documentación" un `limite_notas` de
    B2 distinto, y era simplemente que la copia local llevaba dos días de retraso. El valor
    bueno es el que dice el doc: **`2026-08-04 23:59`**.
  La **`049`** será la del
  registro retroactivo de notas, aún sin implementar —
  ⚠️ **la 050 y la 051 se numeraron antes que la 049 a propósito**: son independientes y
  corrían primero. Al aplicarlas, el orden lo manda la dependencia, no el número: la `051`
  exigía que el fix F1 estuviera **antes** en producción, y así se hizo (deploy `cf8bdb2`
  y después la migración).
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
- ✅ **Rediseño 2 del orden de mérito — EN PRODUCCIÓN (deploy del 04/08/2026, `de449e2`).**
  Implementado y probado el 26/07. Las 6 fases + una fase extra (F5b) y varios fixes; **sin
  migración nueva**, así que el deploy fue merge + push sin tocar la BD de prod.
  Qué hace cada fase, las desviaciones respecto del plan y los efectos colaterales
  aceptados: `docs/modulos/orden-merito-rediseno.md` **§8** (manda esa sección, no las
  §1-5, que son el plan original). Estado vigente del módulo: `orden-merito.md`.
  Diferencia consciente con el diseño: el cierre **no** valida "0 competencias sin
  bloquear" (P3) porque él mismo las fuerza.
  - Se desplegó el 04/08/2026 con las dos condiciones duras **en verde** (ver "Cierre de
    B2 — SECUENCIA CORRECTA").
- **Efecto colateral del guard P4 (VIGENTE EN PRODUCCIÓN desde el 04/08/2026) — REABRIR
  UN BIMESTRE YA CERRADO ES UNA PUERTA DE UN SOLO SENTIDO.** `cerrar()` exige
  ahora `alertasEvaluacionIncompleta = 0`, y esa alerta se evalúa sobre bimestres
  `activo`. Un bimestre que se cerró ANTES de que existiera el guard puede no
  cumplirlo: **B1 tiene hoy 12 alumnos con blancos sin motivo**, así que reabrirlo lo
  dejaría imposible de re-cerrar hasta resolverlos uno a uno (nota u omisión desde el
  módulo del docente). No es un defecto —es la regla funcionando— pero es una
  restricción que antes no existía y que está activa en producción desde el 04/08.
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
- ✅ **BOLETA CON TODAS LAS COMPETENCIAS DEL PLAN — EN PRODUCCIÓN (implementada y
  verificada en local el 05/08/2026, desplegada ese mismo día en `c8681da`). Sin
  migración.** La boleta lista **todas** las
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
  - ✅ **EL SELLO DEL DIRECTOR YA NO APARECE EN BORRADOR NI EN VISTA PREVIA
    (07/08/2026, en `dev`, una línea, sin migración y sin SASS).** Decisión del usuario al
    revisar la boleta digital: **jamás** en versiones provisionales. Solo el sello; el resto
    del pie no se toca. Detalle en `docs/modulos/boletas.md`.
    - **Estaba registrado como hueco conocido y DIFERIDO** ("si se quiere que el borrador
      digital tampoco muestre el sello, es un ajuste aparte"). Hoy se decidió y se hizo.
    - **Incumplía un contrato ya escrito:** el docblock de `archivarBorrador` define
      `$vistaPrevia = true` como *"sin QR y sin imagen de firma del director"*, y en el
      mismo `digital.php` el **QR sí lo respetaba**. Omisión puntual, no criterio distinto.
    - ⚠️ **El alcance era mayor que el documentado:** la nota lo achacaba a los
      desactivados, pero la entrada más expuesta es la **boleta digital del docente**
      (`vistaPrevia` incluye `estadoBoletaDePeriodo(...) !== 'oficial'`) → con el bimestre
      sin cerrar, **todos** los docentes veían el sello en un documento provisional.
    - **La imprimible ya estaba bien:** son dos assets distintos (`firma_path` en
      `alumno.php`, `sello_path` en `digital.php`) y cada vista pinta uno solo.
    - **Barrido hecho:** los otros 7 documentos con firma o sello (nóminas, actas,
      constancia, horario, informe SIAGIE, resumen) **no tienen modo borrador**.
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
  - 🔴 **PENDIENTE — checklist de impresión en navegador** (§8.3 del doc). ⚠️ **El
    disparador CAMBIÓ: el código ya está en producción, así que esto toca hacerlo ANTES
    DE IMPRIMIR Y ENTREGAR B2**, no antes de desplegar. La restricción dura es **UNA hoja
    A4 vertical**: el máximo de filas no sube (29 → 29) y el peor incremento es +5
    (Primaria 2.º A), pero eso **no está probado en papel**.
    - **El alto ya no lo fijan las filas sino las CONCLUSIONES DESCRIPTIVAS** (2 líneas
      por celda, `.conclusion-clip`): el nº de filas es fijo por sección, el alto no.
      Peor caso medido en Secundaria 4.º A (la sección del incidente): matrícula **556**
      (ROSALES STEPHANO), **6 filas con conclusión**, hasta 233 caracteres. Es la boleta
      que hay que mirar para dar por buena esa sección.
- ✅ **LAS 4 REAPERTURAS DEL PANEL DE BLOQUEOS EXIGEN EL BIMESTRE ACTIVO — EN PRODUCCIÓN
  (06/08/2026, commits `213abc0` y `2122345`, desplegados el mismo día en `83c87f5`).**
  Probado en local por el usuario antes del deploy. Sin migración y sin SASS (reusa
  `.btn:disabled`). Detalle en `docs/modulos/admin.md`.
  - **El defecto:** con el bimestre **cerrado**, los 4 botones destructivos del panel
    (`desbloquear` competencia, `reabrirTransversal`, `reabrirConducta`,
    `reabrirAsistencia`) funcionaban **sin dar error** y sin validar el estado del
    periodo. Solo `limpiarBloqueosCierre` lo exigía.
  - **Por qué importaba:** `periodoEditable`/`periodoEstaBloqueado` cortan por
    `estado='cerrado'` **sin mirar el bloqueo**, así que reabrir NO habilita a nadie a
    corregir; y mientras tanto **el dato desaparece del documento** en 3 de los 4 casos
    (boleta = solo competencias bloqueadas · `getTransversalesAgregadas` exige cierre
    vigente · `ConductaModel::getParaPeriodo` devuelve `null` sin él). Quedaba una
    competencia invisible en la boleta que **nadie podía reparar sin reabrir**.
  - ⚠️ **La ASISTENCIA es la excepción MEDIDA, no supuesta:** `getDelBimestre` lee
    `inasistencias` sin mirar el cierre → ahí **no se pierde nada de la boleta**. Se
    bloquea igual (nadie podría registrar), pero **su mensaje no promete un daño que no
    ocurre**. Cada llamada pasa SU aviso; por eso el guard recibe el texto por parámetro.
  - **Punto único:** `BloqueoController::abortarSiPeriodoCerrado`. Los botones quedan
    **inertes con el motivo en el `title`** (no desaparecen: se ve POR QUÉ no se puede).
    Conducta tenía **DOS** botones "Reabrir" (`pendiente_tutor` y `cerrada`): los dos.
  - **Los botones de AVANCE se dejan intactos a propósito** (bloquear competencia /
    transversal / etapa 1 / etapa 2 / asistencia): no destruyen nada y son la vía para
    recomponer lo que haya quedado sin bloqueo por un desbloqueo anterior al fix.
  - **Contexto operativo que lo motivó:** el usuario necesita poder desbloquear una
    competencia si un docente se equivocó. Con B2 **activo** eso funciona, pero ⚠️
    `limite_notas` sigue **vencido** (04/08 23:59): hay que **ampliarlo** desde
    `/director/anios/1` o el docente no podrá editar aunque se desbloquee.
  - **La ventana barata para corregir es entre CERRAR y PUBLICAR:** ahí reabrir → corregir
    → re-cerrar todavía actualiza el snapshot **oficial** del mérito. Tras publicar, el
    candado 046 lo congela y la corrección va a `orden_merito_rectificado` (no oficial).
    Medido: B2 **no tiene ninguna fila** en `periodos_publicacion`.
- ✅ **DESBLOQUEO GRANULAR DE TRANSVERSALES EN EL PANEL DEL DIRECTOR — EN PRODUCCIÓN
  (deploy `cf8bdb2`, 06/08/2026). Sin migración.** Detalle:
  **`docs/modulos/admin.md` §"Transversales: los dos niveles"**.
  - **El hueco:** las transversales **no son filas del panel académico**
    (`getCompetenciasPorPeriodo` une por el área de la CARGA), así que para reabrir una
    TIC/GAMA mal aprobada había que **desbloquear una competencia ACADÉMICA** de la misma
    carga: la sacaba a ella de la boleta, liberaba **las dos** transversales de golpe y
    obligaba a re-aprobar todo. Y si la carga no tenía académicas bloqueadas —permitido:
    64 cargas de B2 bloquean transversales primero— **no había vía ninguna**.
  - ⚠️ **El botón que parecía servir, no servía:** "Desbloquear" del bloque de
    transversales solo llamaba a `anularCierreVigente` (el cierre del TUTOR), sin tocar
    los bloqueos por carga. **Renombrado a "Anular cierre"** y el texto del bloque ahora
    distingue explícitamente los dos niveles. Solo texto: la lógica no cambió.
  - **Granularidad por COMPETENCIA** (decisión del usuario): TIC y "Aprendizaje autónomo"
    se liberan por separado, desde un `<details>` colapsado por sección (carga · docente ·
    competencia · origen · nº de notas · acción). Sin JS.
  - **Guards:** el anclaje exige `a.tipo='transversal'` (un `bloqueo_id` académico aborta,
    probado), exige el **bimestre activo** con el mismo punto único del 06/08, y **anula
    el cierre del tutor** porque el agregado promedia solo lo bloqueado.
  - **Probado en transacción:** liberar TIC deja *Aprendizaje autónomo* bloqueada,
    conserva las **44 notas**, no toca las 2 académicas de la carga y anula el cierre;
    rollback limpio.
- ✅ **TRANSVERSALES: LAS 4 FASES EN PRODUCCIÓN (deploy `cf8bdb2`, 06/08/2026), con la
  migración 051 aplicada después.**
  Qué se construyó y con qué cifras:
  **`docs/modulos/transversales-visibilidad-tutor.md` §5** (manda esa sección).
  - **F3 — el tutor ya no espera a ciegas.** La tabla de promedios se pinta SIEMPRE, con
    badge `Provisional` y en solo lectura mientras falten cargas; debajo, el resumen de
    **qué cargas aprobaron sus transversales y qué docente las lleva** (deroga la regla de
    privacidad del 14/06/2026 — se expone área, docente y estado; **nunca** notas ajenas
    ni DNI). Solo se listan las cargas que APORTAN (`total_comp > 0`).
    🔴 **El guard de escritura está en el SERVIDOR** (`guardarConclusion`), que no
    comprobaba `$listo`: ocultar el textarea habría sido cosmético.
  - **F4 — el cierre transversal se desacopla de las académicas.** `estadoCargasSeccion`
    cuenta solo transversales (numerador y denominador se mueven juntos). Las académicas
    no participan del promedio que se congela, así que exigirlas hacía esperar por notas
    que no cambiaban el resultado. **Contrapartida aceptada:** cerrar antes alarga la
    ventana en la que un desbloqueo académico anula el cierre en cascada (B2 ya llevaba
    48 anulaciones sobre 71).
  - ⚠️ **Se revisaron los OTROS DOS consumidores de `estadoCargasSeccion`**
    (`BloqueoController` y la card del dashboard docente): no se rompen —preguntan lo
    mismo— pero **sus textos pasaban a mentir** y se ajustaron a «competencias
    transversales». Un «X de Y» a secas se leía como el total de la sección.
  - **Probado construyendo el estado provisional en transacción** (en local no existe:
    B2 está cerrado y todo bloqueado): liberar una carga deja `30/28`, el guard **rechaza
    el POST**, la tabla conserva sus 24 alumnos y el resumen dice `14 de 15`; rollback a
    `30/30`. Y `calificaciones.md` tenía una línea **falsa desde antes** —decía que
    `estadoCargasSeccion` contaba «solo competencias PROPIAS»— ya corregida.
  - ⚠️ **Dato que matiza F3 sin cambiar la decisión:** el promedio provisional **sí se
    mueve** (34 de 48 celdas con 12 de 15 cargas sin aprobar), pero **el literal no llegó
    a cambiar** mientras quedara alguna carga aportando, ni en primaria ni en secundaria.
    El riesgo es real en el promedio; en B2 no se materializó en el literal.
  - 🔴 **LA SECUENCIA CHOCA CON EL CIERRE DE B2:** `F1 a producción → aplicar la 051 →
    CERRAR B2`. Los fantasmas los crea el cierre forzado, así que **si B2 se cierra antes
    de que el fix esté en prod, nacen fantasmas nuevos**; y si la 051 se aplica con el
    código viejo arriba, el siguiente cierre los recrea.
  - ⚠️ **PUEDE QUE EN PROD NO HAYA NADA QUE BORRAR, y es válido.** Las cifras están
    medidas en **local**, donde B2 figura `cerrado`; en **prod B2 seguía ABIERTO**. Si
    nunca se cerró allí, los 130 no llegaron a nacer y el PASO 1 dará **0 filas**. Manda
    el PASO 1 en prod, no las cifras del doc. Lo que protege de verdad es F1.
  - **F1** (`AnioAcademicoModel::bloquearCompetenciasPendientes`, bloque 2): añade las dos
    exclusiones del formulario. **Prueba dura, no inspección:** vaciados los 820 bloqueos
    transversales de B2 en transacción y recreados con el SQL nuevo → **690 en vez de 820,
    exactamente 130 menos**, 0 en TOE y 0 en no-dueña; rollback limpio.
  - **La regla de "carga dueña" queda en CUATRO sitios** (decisión: cuarto sitio
    documentado, **no** helper compartido — no se toca el gate del tutor, que es delicado).
    Los cuatro llevan comentario cruzado que los nombra: formulario, `estadoCargasSeccion`,
    `cargaDuenaTransversales` y el cierre forzado.
  - **F2 = migración `051`** (datos, no esquema), **escrita y ensayada, NO aplicada en
    ningún entorno**. Aborta si aparece un solo `C_OLVIDO_REAL` o si hay notas/criterios
    colgando. Ensayo en local con `ROLLBACK` y verificación *dentro* de la transacción:
    borró **130**, dejó el aviso en **0/0**, con los **690** de docente y los **23** cierres
    vigentes intactos; B1 siguió en **774**.
  - **Verificación:** `database/verificaciones/verif_transversales_fantasma.php` (solo
    lectura, corre en prod). Su bloque de **equivalencia de universos** es el que impide
    que el defecto vuelva: hoy da **345 = 345**, antes del fix 410 contra 345. Mientras la
    051 no se aplique, el script **falla a propósito** en el bloque 1.
  - **Dos afirmaciones del plan corregidas al medirlas:** de las 774 forzadas de B1,
    **84 son este mismo defecto** (no todas son "modelo viejo"); y la transversal es la
    última en **13 de 23** secciones, no en las 23 — **F4 solo daría tiempo útil a 4**
    (47 h, 29 h, 11 h, 3 h).
  - Diagnóstico original del defecto (sigue vigente como contexto):
  - **El aviso de `/admin/control` en B2 es FALSO.** Dice que 130 competencias en 65
    cargas de **23 docentes** quedaron sin bloquear "porque el docente no las había
    bloqueado". Clasificadas las 65: **23 son cargas TOE** (el formulario NO adjunta
    transversales a la carga de tutoría, decisión del 07/07) y **42 son cargas no-dueñas
    de secciones unidocentes** (las TIC/GAMA se adjuntan una vez por área, en la dueña).
    **Olvidos reales: CERO.** 23+42 = 65 cargas × 2 = 130, cuadra exacto.
  - **Causa raíz:** `AnioAcademicoModel::bloquearCompetenciasPendientes` (bloque 2)
    recorre **TODAS las cargas activas** sin aplicar las dos exclusiones que sí aplica
    `CalificacionController:507-514`. **Misma regla, dos implementaciones divergentes.**
  - ⚠️ **Ya mordió antes y se parcheó del lado equivocado:** el comentario de
    `estadoCargasSeccion` documenta que las no-dueña inflaban el numerador (53/41) y
    habilitaban las conclusiones antes de tiempo. Se arregló **el conteo**, no el origen.
  - **Impacto acotado:** NO contamina el promedio agregado (una carga fantasma no tiene
    notas) ni infla ya el gate del tutor. El daño es de **confianza**: acusa a 23 docentes
    en un panel de dirección. Sospecha por verificar: podría explicar parte de las **48
    anulaciones sobre 71 cierres transversales** de B2, vía la cascada de desbloqueo.
  - **B1 no se toca** (774 forzadas allí son del modelo viejo, carga única del tutor).
  - ✅ **DECISIONES CERRADAS (06/08/2026, no re-preguntar):** **(1)** los 130 fantasmas de
    B2 **se borran** con una migración de DATOS **`051`** (la `049` sigue reservada al
    registro retroactivo) — el PASO 1 aborta si aparece un solo caso de "olvido real" o si
    alguna de esas cargas tiene notas transversales colgando, y **F1 va antes o en el
    mismo despliegue**, o el siguiente cierre los recrea. **(2)** El tutor **solo mira**
    hasta tener el promedio final: nada de conclusiones sobre un parcial, y el guard va
    **en servidor** (`guardarConclusion` hoy NO comprueba `$listo`, así que ocultar el
    textarea sería cosmético). **(3)** El resumen del tutor **sí muestra el nombre de la
    carga y del docente**: esto **DEROGA** la regla de `tutoria.php:55` ("no se expone el
    detalle por carga ni el nombre de otros docentes"), nacida el **14/06/2026** en
    `73838d1`. Al implementar hay que **reescribir ese comentario**, o el código afirmará
    lo contrario de lo que hace. La protección del DNI del mismo lote **no se toca**.
- **PROPUESTA "BLOQUEAR TRANSVERSALES ANTES QUE LAS ACADÉMICAS" — EVALUADA (06/08/2026):
  ya es posible y no destraba nada por sí sola.** `bloquear()` es por competencia y admite
  transversales, sin guard de orden: **64 cargas de B2 (16%) ya lo hacen**. Lo que frena
  al tutor es que el gate cuenta académicas + transversales y que `tutoria.php:98`
  **oculta la tabla de promedios entera** hasta que todo esté bloqueado. Y la ganancia
  sería nula: en las 23 secciones de B2 la última transversal llegó **40-144 h DESPUÉS**
  que la última académica. El acoplamiento sí es gratuito (`getPromediosSeccion` filtra
  `tipo='transversal'`), así que desacoplar el gate es correcto — pero el valor está en
  **mostrar promedios parciales**, no en el orden de bloqueo.
- 🔴 **`notFound()` NO EXISTÍA — BUG PREEXISTENTE CORREGIDO (07/08/2026, en `dev`).**
  Varios controladores llamaban `$this->notFound()` sin que estuviera definido en
  ninguna parte: `Router` y `RectificacionController` tenían el suyo, ambos **privados**
  y por tanto inalcanzables. Efecto real medido: en **local** reventaba con
  `Call to undefined method` y en **producción** el blindaje global lo capturaba como
  excepción y devolvía la página de error **genérica** — nunca un 404.
  - **No se notó durante meses** porque los únicos caminos que lo invocaban exigían un
    periodo inexistente. Los **gates D3 de `/consulta-notas`** fueron los primeros en
    dispararlo de verdad, y ahí saltó.
  - **Punto único:** `BaseController::notFound(): never` — `http_response_code(404)` +
    `require` de `shared/404.php` + `exit`. Se eliminó el privado de
    `RectificacionController` (obligatorio: un `private` en la hija choca con el
    `protected` de la base y da fatal error de compatibilidad de acceso).
  - ⚠️ **Corrige de paso un segundo defecto latente:** aquel usaba `$this->view('shared/404')`
    y esa vista es una **página HTML completa**, así que el layout la anidaba dentro de
    otra. Ahora es `require` directo, como el Router. Verificado: HTTP 404, **un solo
    `<!DOCTYPE>`**.
  - **Auditoría de alcance:** se revisaron los **34 controladores** buscando llamadas
    `$this->metodo()` inexistentes. **0 casos más.** Convención registrada en `CLAUDE.md`.
- ✅ **`/consulta-notas` CON TRANSVERSALES Y CONDUCTA — IMPLEMENTADO EN `dev`
  (07/08/2026), SIN DESPLEGAR. Sin migración, sin métodos de modelo nuevos.**
  Qué se construyó y con qué cifras:
  **`docs/modulos/consulta-notas-ampliada.md` §9** (manda esa sección).
  - **Las tres fases juntas:** crudo transversal dentro de cada carga, agregado
    transversal por sección y conducta por sección, las dos últimas con ruta propia de
    5 segmentos (registradas **antes** que la de 4).
  - 🔴 **CORRECCIÓN AL PLAN — en B1 el crudo por carga NO existe, y es correcto.** El plan
    pedía verificar «23 cargas en B1»; da **0**, porque allí regía el modelo viejo (carga
    única del tutor) y esas 23 cargas están hoy `inactiva`, fuera del alcance de
    `getCompetenciasPorPeriodo`. **El crudo por docente nace en B2**; para B1 el valor es
    el agregado.
  - 🔴 **El bloqueo NO es señal de contenido:** 820 bloqueos sobre 410 cargas por bimestre
    (cascada del cierre forzado). Sin el `EXISTS` de calificaciones se pintarían **410
    bloques vacíos en B1** y 65 en B2. El helper exige bloqueo **Y** notas.
  - **Gate D3 verificado:** B3 oculta las dos entradas (nada cerrado) y sus rutas
    responden 404 — ocultar el enlace no basta, la URL queda en marcadores.
  - **El roster se reusa de `ConductaModel::getEstudiantesParaTutor`** a propósito: es el
    canónico con las exclusiones de retorno, y duplicar ese filtro es como nacieron los
    bugs de asistencia del 04/08.
  - **Verificación:** `verif_consulta_notas_ampliada.php` contrasta contra **las fuentes
    de la boleta** (`getPromediosMatricula` y `getParaPeriodo`): **2086 celdas y 1048
    filas, 0 divergencias**, con B1 (legado) y B2 (modelo nuevo) en la misma corrida.
  - Plan original y decisiones D1-D3: **`docs/modulos/consulta-notas-ampliada.md`**.
  - ✅ **PROBADO EN NAVEGADOR POR EL USUARIO (07/08/2026): todas las pruebas pasaron.**
    Cubrió, en local y en prod: el aviso de incidencias de B2 en 0 (F1+051), el desplegable
    granular de TIC/GAMA con sus botones inertes en bimestre cerrado, la vista del tutor en
    estado *Provisional* con el resumen de cargas, la card del dashboard docente, las tres
    fases de `/consulta-notas` (incluida la comprobación de que **B1 no pinta bloques
    transversales crudos**) y los gates D3 devolviendo 404 en B3.
  - **Las dos ausencias son estructurales, no un olvido de la vista.** Las transversales
    no las puede alcanzar `getCompetenciasPorPeriodo`: une competencia↔carga por el área
    de la CARGA, y las transversales cuelgan de un área propia (`tipo='transversal'`,
    ids 9 y 21) — **el vínculo transversal↔carga no existe en el esquema**, se resuelve
    por nivel. La conducta no vive en `calificaciones` (4 tablas propias) y su ciclo es
    por SECCIÓN en dos etapas. Invisible hoy: **B2 tiene 17 078 notas transversales**.
  - **Decisiones cerradas (no re-preguntar):** D1 las **dos** caras de las transversales
    (crudo por carga + agregado por sección); D2 la conducta entra **dentro** de
    `/consulta-notas` en solo lectura, **sin** ampliar los roles de `/admin/conducta`
    (tiene escritura); D3 **solo lo oficial** (cierre vigente / las dos etapas).
  - **Cero métodos de modelo nuevos y sin migración** — verificado con sonda:
    `getResumenCompetencia` funciona igual sobre una competencia transversal, así que el
    crudo se pinta con el `_tabla.php` que ya existe.
  - 🔴 **Dos trampas medidas:** el **bloqueo NO es señal de contenido** en transversales
    (820 bloqueos / 410 cargas por bimestre, pero solo 23 cargas con notas en B1 → copiar
    el criterio actual pintaría 410 bloques vacíos); y **B1 y B2 no comparten modelo de
    conducta** (B1 legado: 528 literales y 0 respuestas). `getEstudiantesParaTutor` ya
    resuelve las dos, marcando `es_legado`.
  - **Cierra un hueco de roles real:** `director_general` y `director_ebr` no tienen hoy
    ninguna forma de ver conducta ni el agregado transversal.
- **NOTAS DE BIMESTRES CERRADOS PARA QUIEN LLEGÓ DESPUÉS — PLAN DE IMPLEMENTACIÓN LISTO,
  SIN IMPLEMENTAR (05/08/2026).** Plan completo con fases, archivos y SQL:
  **`docs/modulos/registro-retroactivo-notas.md`** (empezar por §6 **F0**).
  - **Lleva migración `049`** (tabla `calificaciones_retroactivas` + `DROP notas_externas`)
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
  - ✅ **F1 HECHA — la boleta ya NO imprime `0 faltas` de un bimestre no cursado
    (07/08/2026, en `dev`, SIN MIGRACIÓN y sin SASS).** `sin_registro` gana un tercer
    motivo: que **nadie haya registrado** la asistencia de ese alumno. Punto nuevo
    `AsistenciaModel::tieneRegistroUnion`, consumido por `BoletaModel::armar` en último
    lugar para que `||` corte en corto y no consulte cuando el umbral ya dijo que no.
    Detalle y cifras en `docs/modulos/boletas.md`.
    - **Universo medido: 18 pares** (matrícula, bimestre) sin fila en bimestres
      cerrados/activos; **la unión neutraliza 2** → **16 celdas** pasan de `0` a guion:
      los **6 que llegaron tarde** en B1 y **10 trasladados/retirados** en B2.
      **El Total anual no se mueve** (esas columnas aportaban 0).
    - ⚠️ **La UNIÓN era imprescindible, y por poco no se ve:** en el retorno #1 la fila de
      B1 vive en la oficial (190) y la de B2 en la operativa (692). Preguntando matrícula
      por matrícula, esa boleta habría salido en guion en **los dos** bimestres teniendo
      datos. Verificado en los dos sentidos.
    - ⚠️ **Sin fila ≠ sin incidencias**, y esto NO era deducible del código: `guardar()`
      es un upsert **AJAX fila por fila** y el cierre de sección **no exige completitud**
      ("sin fila = 0 incidencias", dice su comentario). Como de hecho el registro sí
      escribe fila por alumno aunque vaya en cero (**197** en B1, **173** en B2), esas
      conservan su `0`. **Hubo que medirlo, no deducirlo.**
    - **Contraste que prueba la precisión del cambio:** la 694 pasa a guion en B1 (no lo
      cursó) y **conserva su `0` en B2**, donde sí tiene fila registrada sin incidencias.
    - Verificación: `database/verificaciones/verif_asistencia_sin_registro.php` (solo
      lectura, corre en prod). Su **bloque 2** es el antirregresión.
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
- ✅ **EXONERAR A UN ALUMNO QUE YA TIENE NOTAS — EN PRODUCCIÓN (implementado el
  05/08/2026, desplegado ese mismo día en `c8681da`), SIN MIGRACIÓN.** Deroga el candado
  del 07/07, que dejaba sin salida el caso real
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
  - ✅ **RESUELTO el mismo día: el ORDEN DE MÉRITO excluye las áreas exoneradas**
    (decisión del usuario). `NOT EXISTS` sobre `exoneraciones` en las 2 queries que
    calculan promedio; cubre exoneración por área y por subárea. **Los snapshots guardados
    NO se tocan** (el de B1 sigue en 528 filas).
  - **CASO REAL YA REGISTRADO EN LOCAL POR EL USUARIO (05/08, 19:39):** NOLASCO ALVARADO,
    YURIANA (matrícula **530**, 5.º B primaria), exonerada de Ed. Religiosa con 3 notas
    ya puestas — 1 en B1 **cerrado** y 2 en B2. Verificado end-to-end: su boleta muestra
    **EXO en los 4 bimestres** y anual EXO, las 3 notas siguen vivas en la BD, y en el
    mérito su promedio de B2 baja de **13.38 a 13.21** sin que **cambie ni un puesto** en
    su grado (39 alumnos). Su puesto congelado de B1 (34, promedio 12.17) queda intacto.
- 🟡 **DESBLOQUEAR UNA ACADÉMICA YA NO ARRASTRA LAS TRANSVERSALES — EN `dev`,
  IMPLEMENTADO Y PROBADO, SIN DESPLEGAR (07/08/2026). Sin migración, sin SASS.**
  Decisión del usuario: prima la **granularidad** sobre el clic de menos.
  **El merge a `main` espera al cierre de B2** (decisión explícita: no mover el panel que
  se usa durante el cierre). Detalle en `docs/modulos/admin.md`.
  - `BloqueoController::desbloquear` pasa de 3 efectos a 2: se retira
    `liberarTransversalesDeCarga`; **se conserva** la anulación del cierre del tutor.
  - **Por qué se retira:** su motivo —que las transversales quedarían "inalcanzables"—
    murió el 06/08 con el desbloqueo granular. Mantenerlo obligaba al docente a re-aprobar
    TIC/GAMA que nadie tocó y **bajaba el gate del tutor**, que no podía re-cerrar hasta
    que el docente actuara. Medido en el contraste: el gate caía de **16/16 a 14/16**.
  - **Por qué se conserva la anulación del cierre:** el promedio transversal NO cambia
    (`getPromediosSeccion` solo lee bloqueos transversales), pero **la conclusión
    descriptiva del tutor puede dejar de ser precisa** si cambian las notas. Criterio
    pedagógico del usuario. Ahora es barato: con los bloqueos intactos el tutor re-cierra
    de inmediato.
  - `liberarTransversalesDeCarga` queda **DORMIDO** (0 llamadores), no borrado.
  - **Verificación:** `verif_desbloqueo_sin_cascada.php` (escribe, transacción + ROLLBACK,
    guard de prod). **7 bloques en verde**, incluido el contraste que reproduce la cascada
    vieja y comprueba que rompía el gate.
  - ✅ **Corregidos de paso dos comentarios FALSOS** en `CalificacionController`: decían que
    las transversales "se bloquean junto con la última competencia propia (Variante 1)",
    cuando el docblock de `bloquear()` dice que desde el II Bimestre **cada competencia se
    bloquea por separado** y ese empaquetado se retiró. Las otras 4 menciones a "Variante 1"
    son correctas —nombran el MODELO (las transversales viven en la carga de cada docente)—
    y no se tocaron.

  #### 📋 CHECKLIST DE PRUEBAS EN NAVEGADOR — PENDIENTE (guardada el 07/08/2026)

  > Escrita para ejecutarla en **el setup de casa**. Todo lo automatizable ya está en
  > verde; esto cubre lo único que los scripts no ven: el render y el flujo real.

  **PASO 0 — antes de nada, comprobar la frescura de la BD de esa máquina.**
  ⚠️ **La BD local de cada equipo es independiente.** La de la oficina se sincronizó con
  prod el 07/08 (trae la `050`). Si la de casa está atrasada, las cifras de abajo no
  cuadran y **no es un bug**. Marcadores:

  ```sql
  SELECT (SELECT COUNT(*) FROM calificaciones WHERE extraordinaria = 1)              AS m050_espera_275,
         (SELECT COUNT(*) FROM information_schema.tables
           WHERE table_schema = DATABASE() AND table_name LIKE '\_bkp%')             AS m048_espera_0,
         (SELECT COUNT(*) FROM bloqueos_competencia bc
            JOIN competencias c ON c.id = bc.competencia_id
            JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
           WHERE bc.periodo_id = 2 AND bc.origen = 'cierre')                         AS m051_espera_0;
  ```

  **PASO 1 — la batería automática (un comando, todo en verde en la oficina):**
  ```bash
  php database/verificaciones/verif_desbloqueo_sin_cascada.php   # 7 bloques, incluye el contraste
  php database/verificaciones/verif_transversales_fantasma.php   # 345 = 345, 690 intactos
  php database/verificaciones/verif_asistencia_sin_registro.php  # F1
  ```

  **BLOQUE 1 — el desbloqueo académico ya no arrastra transversales**
  1. `/director/bloqueos` con **B2** → desbloquear una competencia **académica** de una
     carga que tenga TIC/GAMA bloqueadas. El `confirm` debe avisar que las transversales
     NO se tocan.
  2. En la pestaña **Competencias transversales**, abrir el desplegable de esa sección:
     sus **TIC/GAMA siguen bloqueadas**.
  3. Entrar como el **tutor** de esa sección: la tabla de promedios debe seguir
     **habilitada** (no en badge *Provisional*) y debe poder **cerrar sin esperar al
     docente**. ← *es la mejora principal; antes quedaba bloqueado.*

  **BLOQUE 2 — el cierre del tutor sí se anuló**
  4. En el panel, esa sección debe aparecer **sin cierre vigente** (con el botón *Cerrar*
     disponible).

  **BLOQUE 3 — la granularidad sigue intacta**
  5. Liberar **una** transversal desde el desplegable: la otra queda bloqueada y las
     académicas de la carga no se tocan.

  **BLOQUE 4 — el docente**
  6. Como docente de esa carga: la competencia desbloqueada **editable**, sus TIC/GAMA en
     **solo lectura**.

  **BLOQUE 5 — deuda anterior, aprovechando el turno (P1 #7)**
  7. `/admin/boletas-publicas/{id}` → botón **📄 Borradores**: comprobar que el **ZIP
     descarga bien en el navegador**. Verificado en servidor (3 boletas → 3 marcas, 0 QR),
     **nunca en navegador**. Es lo único que quedaba del P1.

  ⚠️ **Nada de esto se despliega todavía**: el merge a `main` espera al cierre de B2.
- **PANEL DE TRANSVERSALES COMPLETO + PUNTO ÚNICO DE "CARGA DUEÑA" — DIFERIDO AL AÑO
  ACADÉMICO SIGUIENTE (decisión del usuario, 07/08/2026).** El gestor de bloqueos
  transversales solo muestra lo aprobado y bloqueado (`getBloqueosTransversalesPorPeriodo`
  arranca `FROM bloqueos_competencia`), y debería mostrar todo diferenciado por estado como
  el panel académico. **Análisis completo y medido en
  `docs/decisiones-diferidas.md`** — no re-derivarlo. En una línea: sería la **quinta copia**
  de la regla de carga dueña (la cuarta divergente creó los 130 fantasmas), **hoy no
  aportaría información** (en B2 las 690 filas del universo están todas en un mismo estado)
  y **en B1 mentiría** (sus 1052 notas viven en 23 cargas `inactiva` del modelo viejo,
  y el panel nuevo escondería los 130 fantasmas que B1 conserva). Toca
  `estadoCargasSeccion`, el gate del cierre del tutor. **Va junto con la F1 del plan de los
  4 registros**, que es un punto único sobre el mismo territorio.
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
  - ✅ **CERRADO — los backups YA NO EXISTEN en prod (migración 048, 06/08/2026).** La
    condición se verificó el 04/08 (conducta de B2 con 23 cierres, la sección 18 entre
    ellos) y el `DROP` se ejecutó el 06/08 tras un PASO 1 que devolvió `PUEDE_BORRARSE`
    con la identidad completa. **En LOCAL tampoco existen ya** (medido el 06/08). Detalle
    en la migración 048, arriba.
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
- ✅ **FORMATO OFICIAL EN TODAS LAS BOLETAS — EN PRODUCCIÓN (corregido el 04/08/2026,
  desplegado el 05/08 en `c8681da`).** La regla de formato del 09/07 (las 4 columnas de bimestre siempre) se había
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
- ✅ **ASISTENCIA EN LA VISTA PREVIA DE BOLETAS — EN PRODUCCIÓN (corregido el 04/08/2026,
  posterior al deploy `de449e2`; desplegado el 05/08 en `c8681da`).** En
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
- ✅ **ORDEN ALFABÉTICO: LA Ñ IBA ANTES QUE LA N — EN PRODUCCIÓN (corregido y desplegado
  el 04/08/2026, `de449e2`).** Detectado por el usuario en la grilla de 4° A primaria (ÑIQUEN PAJUELO
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
  - Fue en el **mismo deploy** que el roster de asistencia (decisión del usuario).
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
  - ✅ **RE-MEDICIÓN COMPLETA DEL 07/08/2026 (local ya sincronizada con prod, con la 050
    incluida) — LAS CUATRO CONDICIONES DURAS EN VERDE. B2 SIGUE SIN CERRARSE.**

    | Condición del runbook | Valor | Cómo se midió |
    |---|---|---|
    | Termómetro de bloqueos B2 | **0** | la consulta 1.1 no devuelve fila del periodo 2 |
    | Alerta de evaluación incompleta B2 | **0 estudiantes** | `ControlOperativoModel::alertasEvaluacionIncompleta(2)` |
    | Empates pendientes B2 | **0 grados** | `OrdenMeritoModel::gradosConEmpatesPendientes(2)` |
    | Conducta / asistencia (Fase 3.5) | **23/23 y 23/23**, 0 dobles | las 3 consultas dan 0 filas |

    - **`fuePublicado(2)` = `false`** → `registrarRanking` escribirá **OFICIAL**, no
      rectificado: el candado 046 no muerde y **B2 es reversible hasta que se publique**.
      `periodos_publicacion` solo tiene las 2 filas de B1 (22/07).
    - **B1 conserva sus 12 alumnos con blancos** — la `050` **no los movió**, tal como
      predecía el análisis previo (`alertasEvaluacionIncompleta` filtra
      `cr.extraordinario = 0`). Verificado, no supuesto.
    - **La lista 1.1-bis (61 pares bloqueados y vacíos) quedó REVISADA y CERRADA**: ninguna
      ÁREA está del todo vacía y la única competencia con la firma de Ética —`Escribe
      diversos tipos de textos en inglés`, primaria— **la declaró NO EVALUADA la docente**
      (confirmado por el usuario). Detalle y discriminador en el runbook, Fase 1.1-bis.
    - **Falta solo lo humano:** revisar en papel (Fase 5.5) y pulsar Cerrar.
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
    ya está en producción (04/08) → **si alguna vez se REABRE B1, no se podrá volver a cerrar**
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
- **05/08/2026 — DEPLOY EJECUTADO: `origin/main` pasó de `de449e2` a `c8681da`**
  (20:02). **41 commits**, 0 conflictos, árbol limpio. Verificado antes de mergear:
  sintaxis de los PHP del lote, scripts de verificación en verde y sin archivos sensibles
  en el diff. Entró a producción: la **boleta con todas las competencias del plan** y
  guion donde no hay dato (conducta incluida), la **Regla A del retorno de grado**
  (F1-F3), **Ética y Valores en el mérito de toda secundaria**, la **señal de borrador
  como punto único** + marca de agua en la digital, la **descarga de borradores en ZIP**,
  **exonerar a un alumno que ya tiene notas** con las áreas exoneradas fuera del mérito,
  el **formato oficial (4 columnas) en las 9 entradas** de boleta y la **asistencia con el
  mismo umbral que las notas**. La migración **047 se aplicó en prod ANTES del merge**
  (confirmada por el usuario).
  - ⚠️ **Este deploy NO fue fast-forward: se hizo con COMMIT DE MERGE** (`c8681da`, padres
    `de449e2` + `9eb13b9`). Consecuencia permanente: **`main` tiene un commit que `dev` no
    contiene**, así que las dos ramas ya no comparten una historia lineal aunque su ÁRBOL
    coincida. **No hay que "arreglarlo" trayendo `main` de vuelta a `dev`.**
- **06/08/2026 — foto verificada: `dev` = `origin/dev` = `95877bb`, `origin/main` =
  `c8681da`.** `dev` va **1 commit por delante** y ese commit es **solo SQL + docs** (la
  migración 048 y la actualización de estado): **no hay código pendiente de desplegar**.
  Árbol limpio.
  - 🐞 **Incidente del `git pull` (06/08):** se ejecutó `git pull origin main` estando en
    `dev`. Como `pull.rebase = false` (config global de Git for Windows), arrancó un merge
    de `main` dentro de `dev` que quedó **a medias** — `MERGE_HEAD` presente, índice sin
    conflictos y árbol idéntico a `dev`, porque `main` no aporta contenido. Se resolvió con
    `git merge --abort`, sin pérdida. **Estando en `dev`, `git pull` a secas**:
    `branch.dev.merge` ya apunta a `refs/heads/dev`.
- **06/08/2026 — DEPLOY EJECUTADO: `origin/main` pasó de `c8681da` a `83c87f5`** (merge
  commit, como el del 05/08). **19 commits**, árbol de `main` idéntico al de `dev`.
  - **Lo único que cambia de comportamiento en prod es el guard de las 4 reaperturas**
    del panel de bloqueos: de los 11 archivos del lote, solo **2 son código**
    (`BloqueoController.php` y `bloqueos/index.php`). El resto son las migraciones **048 y
    050 —ya aplicadas en prod, viajan como archivos inertes—** y documentación (los 3
    planes nuevos, el runbook y este ESTADO).
  - **Riesgo bajo por construcción:** el fix es **defensivo**, solo IMPIDE acciones que
    antes destruían datos en silencio; ninguna acción que ya funcionaba deja de hacerlo.
    Por eso se desplegó **antes** de cerrar B2 pese a tocar el panel que se usa durante el
    cierre.
  - **Verificado antes de mergear:** `main` local **sí** estaba al día con `origin/main`
    (la trampa recurrente no mordió esta vez), `php -l` en los 2 archivos, 0 archivos
    sensibles en el diff, **0 cambios en SASS/JS** (no hacía falta `gulp build` ni había
    riesgo de CSS desincronizado) y **ninguna migración pendiente de aplicar**.
  - **La migración `051`** (limpieza de los 130 bloqueos fantasma) sigue **planificada, no
    escrita**, y depende de que antes se implemente F1 del plan de transversales.
    *(Superado: se escribió, se aplicó en prod el 06/08 y en local el 07/08.)*
- **07/08/2026 — DEPLOY EJECUTADO: `origin/main` pasó de `31b136c` a `2242ec7`** (commit
  de merge, como los del 05 y 06/08). **5 commits**, autorizados por el usuario tras haber
  probado el lote en navegador ese mismo día.
  - **Qué entró:** `/consulta-notas` con transversales y conducta (las 3 fases) y el fix de
    **`notFound()`**, que hasta ahora **no existía**: varios controladores lo llamaban sin
    estar definido, así que en producción ningún 404 llegaba a mostrarse (caía en la página
    de error genérica). Más la limpieza de `CLAUDE.md` y la documentación del lote.
  - **Verificado ANTES de pushear:** `main` local estaba **82 commits detrás** de
    `origin/main` (la trampa recurrente, esta vez enorme) y se puso al día con un
    fast-forward limpio; el **árbol de `main` quedó idéntico al de `dev`** (`git diff main
    dev` vacío); `php -l` limpio en los 8 PHP del lote; **0 archivos sensibles** en el diff;
    **0 cambios en SASS/JS** → no hacía falta `gulp build`. **Sin migración pendiente.**
  - **Riesgo bajo por construcción:** de los 13 archivos, los únicos que cambian
    comportamiento fuera de `/consulta-notas` son `BaseController` (gana `notFound`) y
    `RectificacionController` (pierde el suyo, privado — era obligatorio: un `private` en la
    hija choca con el `protected` de la base y da fatal error de compatibilidad de acceso).
    **Nada toca el camino del cierre de bimestre.**
- **07/08/2026 — SEGUNDO DEPLOY DEL DÍA: `origin/main` pasó de `2242ec7` a `c8fa4fd`**
  (commit de merge). **6 commits**, 8 archivos, de los que solo **3 son código**:
  `AsistenciaModel`, `BoletaModel` y `boleta/digital.php`. Sin migración, sin SASS/JS.
  - **Qué entró:** **F1** (la asistencia de un bimestre sin registro sale en guion) y el
    **sello del director fuera de borrador y vista previa**, más la documentación del día
    (Hito A en el runbook, señal 1.1-bis por competencia, corrección de la causa de los
    fantasmas).
  - **Validado por el usuario en navegador ANTES del merge**, en 4 bloques: el caso 694
    (guion en B1 y `0` en B2), el control 556 sin cambios, el retorno #1 con sus dos
    bimestres con dato, y el pie de la boleta digital sin sello y sin descuadre.
  - **Batería previa, toda en verde:** `verif_asistencia_sin_registro` (nuevo),
    `verif_asistencia_boleta`, `verif_estructura_boleta`, `verif_plan_completo_boleta`
    (1965 filas de nota, 0 perdidas) y `verif_retorno_grado`. Árbol de `main` idéntico al
    de `dev`; `php -l` limpio; 0 archivos sensibles.
  - **`main` local SÍ estaba al día esta vez** (se había puesto al corriente en el primer
    deploy del día), así que la trampa recurrente no mordió.

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
