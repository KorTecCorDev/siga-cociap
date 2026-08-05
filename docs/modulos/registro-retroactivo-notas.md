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

⚠️ **Hay que delimitar los tres mecanismos o se pisarán.** Al terminar este plan deben
quedar así:

| Mecanismo | Qué es | Dónde sale |
|---|---|---|
| Calificación **extraordinaria** (042) | nota **numérica** REAL de NUESTRO colegio, a alumno no evaluado | boleta + SIAGIE, no mérito |
| **Notas autorizadas SIAGIE** (040) | literal autorizado por dirección para un no evaluado | **solo** el acta SIAGIE |
| **Registro retroactivo** (este plan) | literal de OTRO colegio o de un bimestre no cursado | boleta (+ SIAGIE, ver §7) |

## 6. Fases propuestas

### F1 · Asistencia con guion (independiente, pequeña, sin migración)
`BoletaModel::armar` marca `sin_registro` también cuando **la matrícula no tiene ninguna
fila de asistencia en ese periodo**. Hoy solo mira el umbral del bimestre y el estado
`pendiente`, por eso imprime ceros. **Se puede hacer ya**, sin esperar al resto.
⚠️ Efecto colateral a validar: también afectaría a un alumno cuya sección nadie registró
—que hoy también imprime ceros falsos—, lo cual es deseable pero conviene medirlo antes.

### F2 · Modelo de datos (migración)
Tabla nueva (propuesta: `calificaciones_convalidadas`) anclada por **ids**, no por texto:
`matricula_id`, `periodo_id`, `competencia_id`, `nota_literal` ENUM(AD,A,B,C), `motivo`,
`colegio_origen`, auditoría (`registrado_por`, `registrado_en`), UNIQUE
(matricula, periodo, competencia).
Decidir en esta fase la suerte de `notas_externas` (§7.3).

### F3 · Captura en grilla (admin/RA)
Pantalla por alumno + bimestre cerrado: competencias del plan de su sección agrupadas por
área —reusando `estructuraCompetenciasSeccion`, que ya existe—, selector AD/A/B/C por
fila, campo de motivo y de colegio de origen. Guard: solo periodos **cerrados** y solo
competencias **sin nota** en `calificaciones`.

### F4 · Lectura en la boleta
`BoletaModel` une la fuente nueva a las notas reales, como ya hace con las fuentes del
retorno. En la celda: literal sí, **numeral en guion**. Nota al pie de D4.

### F5 · Orden de mérito y guards
Confirmar por escrito y con verificación que estas notas **no** entran al ranking (el
snapshot de un bimestre publicado es inmutable por el candado `046`) ni alteran
`alertasEvaluacionIncompleta`.

## 7. Preguntas abiertas

1. **¿Estas notas van al SIAGIE?** El alumno no estaba matriculado aquí en ese bimestre,
   así que el acta del colegio probablemente no debe llevarlas. Afecta a
   `SiagieExportModel` y se cruza con `notas_autorizadas_siagie`.
2. **¿Conviven la extraordinaria y el registro retroactivo, o se unifican?** Hoy la
   extraordinaria ya cubre "alumno sin nota en bimestre cerrado" con numeral. Si conviven,
   hay que explicar a RA cuándo usar cada una; si se unifican, la grilla tendría que
   admitir numeral y literal en la misma pantalla.
3. **¿Qué pasa con `notas_externas`?** Está vacía y no se lee en la boleta. O se rediseña
   como la tabla de F2 (aprovechando su UI), o se declara obsoleta y se documenta.
4. **¿Y la conducta y la asistencia del bimestre convalidado?** Hoy quedan en guion. ¿Se
   registran también o se dejan explícitamente fuera?
5. **¿Las transversales?** Quedan fuera del insertable actual porque se agregan desde el
   cierre del tutor. ¿Se convalidan o no?

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
