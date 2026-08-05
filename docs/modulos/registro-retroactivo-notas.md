# PLAN — Registro retroactivo de calificaciones en bimestres cerrados

> **Estado: EN DISEÑO, SIN IMPLEMENTAR** (05/08/2026). Escrito para retomarse en frío.
> Decisiones cerradas en §4; lo que falta decidir, en §7.
> Módulos relacionados: `calificaciones.md`, `boletas.md`, `matriculas.md`,
> `orden-merito.md`, `export-siagie.md`.

## 1. Qué se pide

Un estudiante puede llegar al colegio con uno o más bimestres ya **cerrados**. Hoy su
boleta muestra esos bimestres vacíos y no hay forma de incorporar lo que trae del colegio
de origen. Se pide poder **registrar calificaciones literales por competencia en
bimestres cerrados**, con estas condiciones del usuario:

- **Literales** (AD/A/B/C), porque es lo que entrega el colegio de origen.
- **Libres por competencia**: el docente de origen pudo trabajar competencias distintas
  de las que trabajó el nuestro, así que se llenan solo las que correspondan.
- **Para cualquier estudiante sin calificaciones en un bimestre cerrado**, no solo para
  nuevos o trasladados.
- **Con motivo.**

## 2. Evidencia medida (05/08/2026, datos reales)

**El caso existe hoy: 6 estudiantes** con notas de B2 y ninguna de B1.

| Matrícula | Alumno | Grado | Registro | `tipo` |
|---|---|---|---|---|
| 690 | ÑIQUEN XOANA | 4.º A | 08/06 | nuevo |
| 691 | RAMIREZ ITZEL | 5.º B | 08/06 | **continuador** |
| 693 | RIMAC AZAHI | 5.º B | 30/06 | nuevo |
| 694 | SANTAMARIA JAKELINE | 3.º A | 02/07 | nuevo |
| 695 | GONZALEZ JEANFRANCO | 5.º B | 13/07 | **continuador** |
| 696 | MORALES YEREMI | 3.º A | 13/07 | **continuador** |

⚠️ **`matriculas.tipo` NO sirve para detectarlos**: la mitad figura como `continuador`.
El anclaje fiable es **la ausencia de notas en el periodo cerrado**, que además es
exactamente el criterio que pidió el usuario ("cualquier estudiante sin calificaciones").

⚠️ **No confundir con el lote `traslado_entrada` del 19/05**: ~180 matrículas llevan ese
`tipo_matricula` pero tienen B1 completo. Son alumnos de siempre con el flag mal puesto
(la migración `038` corrigió 6 de ellas). **No son casos de este plan.**

### Qué hace hoy el sistema con ellos (medido con los modelos reales)

| Aspecto | Comportamiento | ¿Correcto? |
|---|---|---|
| Notas de B1 en la boleta | columna completa con guiones | ✅ (desde el 05/08) |
| Orden de mérito B2 | compiten normal (puestos 46, 32, 40, 44, 22, 43) | ✅ es por bimestre |
| Snapshot oficial de B1 | los 6 fuera | ✅ |
| Guard del cierre (`alertasEvaluacionIncompleta` B2) | **0 alertas** | ✅ no bloquean |
| Conducta de B1 | guion | ✅ |
| **Asistencia de B1** | **imprime `0 faltas` como cifra** | 🔴 **dato FALSO** |
| `notas_externas` | tabla vacía, UI existente, **la boleta no la lee** | 🟠 mecanismo muerto |

## 3. Lo que YA está resuelto (y por qué no basta)

**La CALIFICACIÓN EXTRAORDINARIA (migración `042`, en producción desde el 16/07) cubre
más de lo que parecía.** Ver `calificaciones.md` §"Calificación extraordinaria". Permite a
admin/RA registrar nota en una competencia **de bimestre cerrado**, a un alumno **sin nota
previa**, con **motivo obligatorio**, y la nota va a boleta y SIAGIE pero **no** al orden
de mérito.

Probado contra los casos reales: `getCompetenciasInsertables` ofrece **26 de 27**
competencias (primaria 4.º A), **28 de 29** (secundaria 3.º A) y **26 de 27** (5.º B).
La diferencia son las **transversales**, excluidas a propósito (van por la agregación del
tutor).

O sea: **el punto 4 del pedido —"cualquier estudiante sin calificaciones", con motivo— ya
está implementado y desplegado.** Lo que falta es lo demás:

| Falta | Por qué |
|---|---|
| **Literal** | La extraordinaria pide un numeral 00-20. El colegio de origen entrega literales. |
| **Captura en lote** | El alta es de UNA competencia por vez (`?matricula=&carga=&competencia=&periodo=`). Un alumno que llega necesita **26-28 pasadas** por el formulario. |
| **Trazabilidad del origen** | La nota entra como propia; no queda registro de que se cursó en otra institución. |

## 4. Decisiones cerradas (no re-preguntar)

| # | Decisión |
|---|---|
| D1 | **Asistencia de un bimestre no cursado → GUION**, nunca `0`. Mismo criterio ya aceptado para los bimestres sin registrar. |
| D2 | **Literal puro; el numeral va en guion.** No se inventa un número: nadie certificó que una "A" de otro colegio sea 16. En primaria no cambia nada (solo muestra literal); en secundaria la columna N.Num. sale con guion. |
| D3 | **Captura en GRILLA por alumno y bimestre**: una pantalla con todas las competencias del plan de su sección, se llenan solo las que correspondan y el resto quedan vacías. Un motivo común para toda la carga. |
| D4 | **La boleta lo declara**: nota al pie del tipo "I Bimestre: calificaciones convalidadas del colegio de origen". Sin eso, el guion no distingue "no estaba" de "no le calificaron". |
| D5 | Se pide **motivo**, y el universo es **cualquier alumno sin calificaciones en el bimestre cerrado** (no solo nuevos/trasladados). |
| D6 | **La calificación EXTRAORDINARIA y el registro retroactivo SE UNIFICAN** en un solo mecanismo y un solo punto de entrada. No pueden convivir dos caminos para "alumno sin nota en bimestre cerrado". |
| D7 | **`notas_externas` desaparece**: su función la absorbe este proceso. Está vacía y su UI nunca llegó a la boleta, así que no hay datos que migrar. |
| D8 | **Las competencias TRANSVERSALES se registran OBLIGATORIAMENTE** en la grilla, igual que las demás. |
| D9 | **Conducta y asistencia del bimestre convalidado: OPCIONALES.** Se pueden registrar, pero no se exigen para dar por completa la carga. ⚠️ Confirmar al implementar esa fase si "opcional" significa *campo no obligatorio en la grilla* (lectura asumida) o *funcionalidad diferida*. |

### Estado de partida de la unificación (medido el 05/08/2026, LOCAL)

```
calificaciones extraordinarias ....... 0
criterios extraordinarios ............ 0
rectificaciones tipo extraordinaria .. 0
notas_externas ....................... 0
notas_autorizadas_siagie ............. 0
```

**Ningún mecanismo tiene datos**, así que la unificación no arrastra migración de
información. 🔴 **VERIFICAR ESTAS CINCO CIFRAS EN PRODUCCIÓN antes de tocar nada**: si en
prod hubiera extraordinarias registradas, habría que migrarlas al modelo nuevo en la misma
migración, no después.

## 5. Arquitectura: dónde viven estas notas

**Decisión de diseño propuesta: TABLA APARTE que la boleta une al leer. NO filas en
`calificaciones` con `nota_numerica NULL`.**

Medido: `nota_numerica` se usa en **45 lugares de 11 archivos**, y **26 de esos usos son
promedios, umbrales o desempates** (`AVG`, `>= NOTA_MIN_AD`, `BETWEEN`…). Un `NULL` ahí no
falla: **cambia el resultado en silencio** — altera denominadores de promedios, y las
comparaciones con NULL dan falso sin avisar. Contaminaría orden de mérito, promedios de
área, alertas del cierre y export.

**Precedente exacto del proyecto:** `notas_autorizadas_siagie` (migración `040`) ya es una
tabla paralela para notas que **no** deben tocar `calificaciones`. Y el **retorno de
grado** ya probó el patrón de "varias fuentes unidas al leer" (`boletaContexto`).

### El modelo unificado (D6)

Una sola tabla y un solo punto de entrada, con la nota **literal siempre** y el **numeral
opcional**. Así los dos casos caben sin `NULL` en `calificaciones`:

| Caso | `nota_literal` | `nota_numerica` | En la boleta |
|---|---|---|---|
| Viene de otro colegio (o bimestre no cursado) | AD/A/B/C | **NULL** | `— / A` (D2) |
| Evaluación real de NUESTRO colegio que no se registró (lo que hoy hace la extraordinaria) | derivado | 00-20 | `16 / A` |

El flujo actual de la extraordinaria (criterio `extraordinario` + fila en `calificaciones`)
**se retira como punto de entrada**: la grilla pasa a ser el único. Lo que hoy vive en
`CriterioModel::obtenerOCrearExtraordinario`, `RectificacionController@extraordinaria` y
sus guardas queda obsoleto — retirarlo o dejarlo dormido es parte de F2.

⚠️ **Queda un tercer mecanismo fuera de esta unificación, a propósito:**
`notas_autorizadas_siagie` (migración `040`), que existe **solo para el acta SIAGIE** y no
toca la boleta. Se decide junto con la pregunta del SIAGIE (§7.1, diferida por el
usuario). Si esa respuesta es "sí van al acta", los tres mecanismos deberían quedar en uno.

## 6. Fases propuestas

### F1 · Asistencia con guion (independiente, pequeña, sin migración)
`BoletaModel::armar` marca `sin_registro` también cuando **la matrícula no tiene ninguna
fila de asistencia en ese periodo**. Hoy solo mira el umbral del bimestre y el estado
`pendiente`, por eso imprime ceros. **Se puede hacer ya**, sin esperar al resto.
⚠️ Efecto colateral a validar: también afectaría a un alumno cuya sección nadie registró
—que hoy también imprime ceros falsos—, lo cual es deseable pero conviene medirlo antes.

### F2 · Modelo de datos unificado (migración)
Tabla nueva (propuesta: `calificaciones_retroactivas`) anclada por **ids**, no por texto:
`matricula_id`, `periodo_id`, `competencia_id`, `nota_literal` ENUM(AD,A,B,C) NOT NULL,
`nota_numerica` TINYINT **NULL**, `motivo`, `colegio_origen` NULL, auditoría
(`registrado_por`, `registrado_en`), UNIQUE (matricula, periodo, competencia).
En la misma migración: **`DROP TABLE notas_externas`** (D7, está vacía) y decidir el
retiro del flujo de la extraordinaria (D6).

### F3 · Captura en grilla (admin/RA)
Pantalla por alumno + bimestre cerrado: competencias del plan de su sección agrupadas por
área —reusando `estructuraCompetenciasSeccion`, que ya existe y **sí incluye las
transversales** (D8)—, selector AD/A/B/C por fila, campo de motivo y de colegio de origen.
Guard: solo periodos **cerrados** y solo competencias **sin nota** en `calificaciones`.

⚠️ **Las transversales exigen camino propio.** Hoy `getCompetenciasInsertables` las
excluye a propósito, porque una fila cruda en `calificaciones` no llega a la boleta: las
transversales se muestran **agregadas** desde el cierre del tutor
(`getTransversalesAgregadas`, que promedia las cargas bloqueadas y exige
`cierres_transversales` vigente). Un alumno convalidado no tiene nada que promediar, así
que por esa vía nunca aparecería. **El modelo de F2 lo resuelve solo**: al vivir en tabla
aparte y unirse al leer, la nota transversal convalidada entra directa a la boleta sin
pasar por la agregación. Es la razón por la que D8 es viable — pero hay que asegurar que
las dos fuentes **no se dupliquen** cuando el alumno sí tiene agregación.

### F4 · Lectura en la boleta
`BoletaModel` une la fuente nueva a las notas reales, como ya hace con las fuentes del
retorno. En la celda: literal sí, **numeral en guion**. Nota al pie de D4.

### F5 · Orden de mérito y guards
Confirmar por escrito y con verificación que estas notas **no** entran al ranking (el
snapshot de un bimestre publicado es inmutable por el candado `046`) ni alteran
`alertasEvaluacionIncompleta`.

## 7. Preguntas abiertas

**Cerradas el 05/08/2026** (ver D6-D9): unificar extraordinaria + retroactivo; eliminar
`notas_externas`; transversales obligatorias; conducta y asistencia opcionales.

1. 🔶 **¿Estas notas van al SIAGIE? — DIFERIDA por el usuario ("lo analizamos después").**
   El alumno no estaba matriculado aquí en ese bimestre, así que el acta probablemente no
   debe llevarlas. Afecta a `SiagieExportModel` y decide de paso si
   `notas_autorizadas_siagie` se absorbe en la unificación o sobrevive aparte.
   **No bloquea F1 ni F2**: la tabla nace sin exportarse y añadir el export después es
   aditivo. Sí conviene resolverla **antes de F4**, para no rehacer la lectura.
2. **¿"Opcional" en conducta y asistencia (D9) es campo no obligatorio o fase diferida?**
   Se asume lo primero; confirmar al llegar a esa fase.

## 8. Riesgos e invariantes en juego

- **`nota_numerica` NULL en `calificaciones`: prohibido** (26 usos silenciosamente
  afectados). Es la razón de la tabla aparte.
- **Snapshot de mérito inmutable** (candado `046`): registrar notas de B1 **no** puede
  regenerar el ranking de B1, que ya está publicado.
- **Fila en `calificaciones` ⟺ nota viva** (invariante de CLAUDE.md): la tabla nueva no
  debe romperlo escribiendo ahí.
- **Boleta = solo competencias bloqueadas**: la fuente nueva entra por un camino distinto
  y hay que asegurar que no se cuela en el conteo de aprobables ni en el lote de boletas.
- El **guion de D2** en el numeral convive con el guion de "sin dato" introducido el
  05/08: en secundaria una fila convalidada se verá `— / A`, que es justo lo que se busca.

## 9. Verificación prevista

Script en `database/verificaciones/` (solo lectura, corre en prod) que compruebe, sobre
los 6 casos reales: que la boleta muestra los literales convalidados sin numeral, que la
asistencia del bimestre no cursado sale en guion, que el ranking del bimestre cerrado
**no cambia** ni una posición, y que `alertasEvaluacionIncompleta` sigue en 0.
