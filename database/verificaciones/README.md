# Verificaciones — Rediseño del orden de mérito

Scripts CLI de verificación de las fases ya implementadas. Se corren a mano
(`php database/verificaciones/<archivo>.php`) contra la BD local. Asumen el
dataset de referencia (backup activo, mismo que producción): IDs de matrícula/
grado concretos del I Bimestre (541 retirado, 220/666 pendientes, 692/190 retorno).

- **`verif_fase_a_orden_merito.php`** — SOLO LECTURA. Comprueba el filtro por `tipo`:
  pendientes entran al ranking, `trasladado`/`retirado` quedan fuera, el retorno de
  grado sigue anclado (operativa compite, oficial excluida) y B1 sin empates nuevos.
  Lee el cálculo **EN VIVO** (`rankingGradoLive`, por reflexión), no el wrapper
  snapshot-aware: B1 congeló la regla ESPECIAL de la Fase C (roster sin filtro de
  tipo), y desde el snapshot un `trasladado` sí aparece.

- **`verif_fase_b_orden_merito.php`** — Comprueba la migración 046 y el candado de
  inmutabilidad (`registrarRanking`): publicado sin oficial → oficial; publicado con
  oficial → rectificado (oficial intacto). Escribe, pero **todo corre dentro de una
  TRANSACCIÓN con ROLLBACK** y su paso 4 verifica que los conteos volvieron al valor
  previo. NO se limpia con DELETE: su primera versión lo hacía y llegó a destruir el
  snapshot oficial de B1 en local (26/07/2026).

- **`verif_fase1_rediseno_merito.php`** — SOLO LECTURA. P1/P2 del rediseño 2: B1 lee
  del snapshot (528), el universo del cálculo en vivo son solo competencias
  bloqueadas, y el orden estable por `matricula_id` corre sin error.

- **`verif_fase5b_rediseno_merito.php`** — Transacción + ROLLBACK. Con el bimestre
  reabierto el ranking SIGUE saliendo del snapshot (candado 046), mientras que
  `gradosConEmpatesPendientes` sí mira el cálculo en vivo. Su **paso 0 es un control**:
  si el vivo y el snapshot coincidieran, los pasos siguientes no probarían nada.

- **`verif_empates_card_control.php`** — Transacción + ROLLBACK. La card de empates de
  `/admin/control` y la pantalla donde se resuelven (+ el guard del cierre) deben
  reportar **los mismos grados y los mismos conteos**. Su **paso 2 retira temporalmente
  las resoluciones humanas** para tener un escenario con empates de verdad: con todo
  resuelto ambos dan 0 y la comparación no probaría nada. El paso 3 verifica que el
  ROLLBACK las devolvió.
  - Existe porque el Centro de Control tuvo su **propia copia de la cascada** desde el
    08/06/2026, congelada en la tupla de 3 conteos (`num_c|num_b|num_ad`) sin los
    criterios de regularidad alta (`num_alto`, `num_16`) que el motor real incorporó
    ese mismo día. Inventaba empates que el motor deshace solo y que la pantalla de
    resolución nunca ofrecía → **fantasmas irresolubles**. Corregido el 04/08/2026
    delegando en `OrdenMeritoModel::gradosConEmpatesPendientesDetalle`.

- **`verif_roster_asistencia.php`** — **SOLO LECTURA** (no escribe, no abre
  transacciones): es el único que **se puede correr en PRODUCCIÓN**, y por eso NO lleva
  el guard de secretos de los demás. Comprueba que la grilla de
  `/admin/asistencia/{id}?periodo={pid}` lista **el mismo roster que la grilla de notas**
  (`getAlumnosSeccion`: filtro por `tipo` + las dos exclusiones de retorno de grado, sin
  filtrar por estado) y que los `esperados` de `getProgresoPorSeccion` cuadran con ella.
  - Su **bloque 3 mide el impacto del despliegue** frente a la regla vieja
    (`m.estado='aprobada'`, sin tipo ni retornos): quién entra, quién sale y cuántas
    filas de `inasistencias` tiene cada uno. **Correrlo en prod ANTES del deploy** dice
    exactamente qué imprimibles ya firmados cambian. En local: 6 entran (todos
    `pendiente`, dos con datos de B1 que la grilla no mostraba) y 1 sale (la matrícula
    oficial de un retorno activo).
  - El bloque 4 cuenta las filas de matrículas fuera del roster: **dato histórico, no se
    borra** — siguen sumando en la boleta, que va por matrícula y no por roster.

- **`verif_asistencia_boleta.php`** — Transacción + ROLLBACK. Comprueba que el bloque de
  ASISTENCIA de la boleta usa el mismo umbral que las notas (`periodoAportaNotas`) **sin
  alterar lo que ven las familias**: `'oficial'` sigue siendo cerrados+publicados y
  `'archivo'` cerrados, mientras `'borrador'` y `'todos'` suman el bimestre en curso.
  - Su **paso 4 SIMULA el Hito A** del bimestre activo: sin él, `'borrador'` daría lo
    mismo que `'archivo'` y la aserción no probaría nada. El mismo paso verifica que
    `'oficial'` y `'archivo'` **no** se contagian del bimestre en curso, y el cierre
    comprueba que el ROLLBACK devolvió `boletas_aprobadas_en` a su valor original.
  - Existe porque la asistencia era el único de los tres bloques por periodo (notas,
    conducta, asistencia) que no honraba la excepción de la vista previa de RA.

- **`verif_estructura_boleta.php`** — **SOLO LECTURA**, apto para producción. Comprueba que
  las boletas se arman con la **estructura anual completa** (las 4 columnas de bimestre) en
  los cuatro umbrales, y —lo importante— que abrir esas columnas **NO relaja el guard de
  datos**: con `'oficial'` se ven 4 columnas pero solo aportan notas los bimestres cerrados
  Y publicados, aunque el bimestre en curso ya tenga notas en la BD.
  - Su **paso 3 es el control**: compara los bimestres con datos **con y sin**
    `estructuraCompleta` y exige que sean los mismos. Si difirieran, el flag estaría
    filtrando datos y no solo formato.
  - Existe porque la regla de formato del 09/07/2026 se había aplicado solo al token y al
    trasladado: la impresión masiva y el ZIP de archivo colapsaban columnas, y el papel que
    se firma salía con otro formato que el que la familia abre por QR.

## Consultas operativas (phpMyAdmin)

- **`alerta_evaluacion_incompleta.sql`** — SOLO LECTURA. Replica
  `ControlOperativoModel::alertasEvaluacionIncompleta()` en SQL puro, para medir contra
  **producción** desde phpMyAdmin sin depender de que el Centro de control esté
  desplegado. Cuatro bloques autocontenidos (resumen por sección · detalle por criterio
  **con el docente responsable** · detalle por alumno · total). Validada contra el método
  PHP en los dos periodos con datos (B2: 19/19 · B1: 10/671).
  - Es uno de los **dos** prerrequisitos del cierre. El otro —empates del orden de
    mérito— **no es replicable en SQL**: su cascada (grupos por promedio, N desigual,
    tupla de 5 conteos, resolución manual por `grupo_clave`) vive en PHP. Se consulta en
    `/director/orden-merito/{periodo}`, que ya lista los bimestres `activo`.
  - **Ojo con la secuencia:** la alerta es estable (no mira `bloqueos_competencia`), así
    que medirla y resolverla vale antes o después del deploy. Los **empates NO**: P2 del
    rediseño 2 reduce el universo del cálculo en vivo a competencias BLOQUEADAS, así que
    los empates cambian con el deploy, y una resolución se ancla al conjunto exacto de
    matrículas (`grupo_clave`) — si el grupo cambia, deja de cubrirlo. Resolver empates
    va DESPUÉS del deploy y con todo bloqueado.

- **`transversales_pendientes.sql`** — SOLO LECTURA. Lista a los **docentes que aún no
  aprobaron+bloquearon las transversales (TIC/GAMA)** de sus cargas: el bloqueador
  típico del cierre del tutor. Cuatro bloques autocontenidos (periodos y secciones de
  referencia · resumen por docente · detalle por carga+competencia · resumen por sección
  con el estado del cierre transversal), con `@periodo`, `@seccion_id` y `@seccion_txt`
  para acotar. Replica el universo de cargas de `CalificacionController::formulario()`
  —incluida la regla de carga **dueña** en secciones unidocentes— verificado 1 a 1
  contra `cargaDuenaTransversales()` (345 = 345 cargas, 0 diferencias) y contrastado en
  los dos periodos con datos (B1 cerrado: 0 pendientes en las 23 secciones; B2: las
  lista). Excluye por diseño la carga de Tutoría/Ética y la carga transversal del modelo
  viejo (migración 019), que no llevan TIC/GAMA.

Requisitos: haber aplicado la migración `046_orden_merito_inmutable.sql` en local y
tener B1 (periodo 1) con sus filas de `periodos_publicacion` (backfill de la 044).

**Antes de creer una prueba del mérito de B1, comprobar que el snapshot tiene sus 528
filas** (`SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = 1`). Si está
vacío, reponerlo con `database/reconstruir_snapshot_b1.php`.
