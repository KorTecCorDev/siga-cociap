# Runbook — Cierre de un bimestre

> Procedimiento operativo para cerrar un bimestre en PRODUCCIÓN.
> Escrito el **29/07/2026** para el **cierre del II Bimestre (periodo_id = 2)**, pero
> redactado para reutilizarse en B3 y B4: donde dice `@periodo := 2`, cambiar el número.
>
> **REVISADO EL 06/08/2026** contra el código y los datos reales. Cambios: el deploy ya
> ocurrió, así que la **Fase 2 pasa a ser una comprobación, no un paso**; se añadió el
> **plazo de edición** (`limite_notas`), que es el bloqueador operativo que faltaba; la
> Fase 4 describe **las seis cosas** que hace `cerrar()`, no dos; hay un paso nuevo para
> **conducta y asistencia** (el cierre NO las valida) y otro para la **prueba de papel**
> de la boleta antes de publicar.
>
> **Ejecuta:** el usuario (admin / Dirección). Las consultas van en **phpMyAdmin de
> producción** y son **todas de solo lectura**. El cierre en sí se hace por la UI.
> Estado vivo y pendientes: `docs/ESTADO.md`. Reglas del módulo:
> `docs/modulos/orden-merito.md` y `docs/modulos/boletas.md`.

## Las tres reglas que no se negocian

1. **CERRAR NO PUBLICA.** Publicar las boletas y el orden de mérito a las familias es
   un acto separado, por NIVEL y con fecha/hora, desde `/admin/control` (compuerta 044).
   Cerrar oficializa; no muestra nada.
2. **El termómetro de bloqueos debe dar 0 ANTES de pulsar Cerrar.** Es la regla
   operativa que cubre el hueco del guard de empates (ver "Por qué el orden importa").
3. **No se mide antes de que los docentes terminen.** Cualquier cifra tomada antes de
   la fecha límite es un piso móvil y solo genera trabajo inútil.

## Por qué el orden importa

Los dos prerrequisitos del cierre no se comportan igual:

- La **alerta de evaluación incompleta es ESTABLE**: no mira `bloqueos_competencia`.
  Se puede medir y resolver antes o después del deploy.
- Los **empates NO son estables**: el rediseño 2 reduce el universo del cálculo en vivo
  a competencias **bloqueadas**, y cada resolución se ancla al conjunto EXACTO de
  matrículas (`grupo_clave`); si el grupo cambia, la resolución deja de cubrirlo y el
  empate reaparece. **Resolver empates va DESPUÉS del deploy y con todo bloqueado.**

Y el hueco que obliga a la regla 2: en `Director/PeriodoController::cerrar`, el guard de
empates corre en `:124`, pero `bloquearCompetenciasPendientes` está en `:155` y
`registrarRanking` en `:173`. Como el cálculo de empates y el del ranking hacen
`INNER JOIN bloqueos_competencia`, **el guard valida un universo más chico que el que se
congela**: cerrar con pares sin bloquear puede petrificar empates que nadie vio. Con el
termómetro en 0, ese universo coincide y el hueco desaparece.

> Es **reversible mientras el bimestre NO se publique**: sin publicación el candado 046
> no se activa, `registrarRanking` sigue escribiendo el oficial y basta reabrir →
> resolver → re-cerrar (costo: las boletas vuelven a BORRADOR). **La ventana
> irreversible se abre al PUBLICAR.**

---

## Fase 0 — Precondiciones

- [ ] Pasó la fecha límite de calificación de los docentes (**para B2: 31/07/2026**).
- [ ] Sabes en qué estado está el **plazo de edición** (`limite_notas`) del bimestre.
      No es un requisito para cerrar: es lo que decide **si puedes CORREGIR algo antes**.

### 0.1 El plazo de edición (`limite_notas`) — el bloqueador que no se ve

`CalificacionModel::periodoEstaBloqueado` y `AsistenciaModel::periodoEditable` exigen que
el bimestre esté `activo` **Y** dentro de `limite_notas`. **El mismo plazo corta notas,
conducta y asistencia a la vez.** `limite_notas = NULL` significa *sin límite*.

```sql
SELECT id, nombre_display, estado, limite_notas, NOW() AS ahora,
       (estado = 'activo'
        AND (limite_notas IS NULL OR NOW() <= limite_notas)) AS editable
FROM periodos WHERE id = 2;
```

- **`editable = 1`** → las Fases 1.3 y 3 se ejecutan tal cual.
- **`editable = 0`** → **nadie puede registrar nada**. Si las Fases 1 o 3 arrojan algo que
  resolver, primero hay que **ampliar `limite_notas`** desde `/director/anios/{anio_id}`,
  y eso abre un bucle que hay que cerrar antes de pulsar Cerrar:

  > ampliar el plazo → **se reabre la calificación docente** (cualquiera puede volver a
  > calificar) → **RE-MEDIR el termómetro (1.1)**, porque la regla 2 exige que dé 0 en el
  > instante del cierre, no la semana pasada.

⚠️ **Un plazo vencido congela también el termómetro**: si nadie puede calificar, el número
no puede moverse. Es una ayuda para razonar, **no una excusa para no repetir la medición**.

> **Estado de B2 al 06/08/2026:** `limite_notas = 2026-08-04 23:59`, **vencido**. Con la
> alerta y los empates en 0 no hace falta ampliarlo; si al re-medir apareciera algo, este
> es el primer paso y arrastra todo el bucle.

## Fase 1 — Medir (en prod, solo lectura)

### 1.1 Termómetro de bloqueos

Cuenta pares **carga + competencia** que tienen notas y **no** tienen fila en
`bloqueos_competencia`. Es el indicador de "los docentes terminaron".

```sql
SELECT cal.periodo_id, COUNT(*) AS pares_sin_bloquear
FROM (SELECT DISTINCT carga_id, competencia_id, periodo_id FROM calificaciones) cal
LEFT JOIN bloqueos_competencia bc
       ON bc.carga_id       = cal.carga_id
      AND bc.competencia_id = cal.competencia_id
      AND bc.periodo_id     = cal.periodo_id
WHERE bc.id IS NULL
GROUP BY cal.periodo_id
ORDER BY cal.periodo_id;
```

⚠️ **La ausencia de fila ES el cero.** El `GROUP BY` solo devuelve periodos con al menos
un par pendiente: si el bimestre que buscas no aparece en el resultado, su termómetro
está en 0. (Referencia: el 28/07/2026 en prod daba B1 = 0 —no aparecía— y B2 = 102.)

**Es un PISO:** no ve lo que aún no se ha calificado. Que dé 0 significa "nada de lo
calificado quedó sin bloquear", no "todo el mundo calificó".

🔴 **PUNTO CIEGO CONFIRMADO (hallazgo del 06/08/2026): una competencia BLOQUEADA Y VACÍA
es invisible para las dos herramientas de esta fase.** Pasó en B1 con **Ética y Valores**:
el 20/07 a la 01:44 se bloquearon las **11 secciones** de secundaria en 60 segundos **con
cero notas**, y horas después se cerró el bimestre.

- El **termómetro** cuenta pares *con notas y sin bloqueo* → un par **sin notas** nunca
  aparece. Por eso B1 daba 0 con Ética completamente sin evaluar.
- La **alerta de evaluación incompleta** solo aflora un criterio cuando algún compañero de
  sección ya tiene nota en él → si **nadie** la tiene, no hay con qué comparar.

### 1.1-bis Competencias bloqueadas y VACÍAS (lista de REVISIÓN, no de aborto)

```sql
SELECT n.nombre AS nivel, COALESCE(a.nombre, a2.nombre) AS area, bc.origen,
       COUNT(*) AS pares, COUNT(DISTINCT ca.seccion_id) AS secciones
FROM bloqueos_competencia bc
INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
INNER JOIN competencias c ON c.id = bc.competencia_id
INNER JOIN secciones s  ON s.id  = ca.seccion_id
INNER JOIN grados   gg  ON gg.id = s.grado_id
INNER JOIN niveles  n   ON n.id  = gg.nivel_id
LEFT  JOIN areas    a   ON a.id  = ca.area_id
LEFT  JOIN subareas sa  ON sa.id = ca.subarea_id
LEFT  JOIN areas    a2  ON a2.id = sa.area_id
WHERE bc.periodo_id = 2
  -- SOLO la competencia PROPIA del área de la carga. Sin este filtro entran las
  -- TRANSVERSALES que cada docente bloquea en su carga y que agrega el tutor:
  -- son ruido y multiplican el resultado por ~3 (medido: 191 → 61 en B2).
  AND (c.area_id = ca.area_id OR c.subarea_id = ca.subarea_id)
  AND NOT EXISTS (SELECT 1 FROM calificaciones cal
                   WHERE cal.carga_id       = bc.carga_id
                     AND cal.competencia_id = bc.competencia_id
                     AND cal.periodo_id     = bc.periodo_id)
GROUP BY n.nombre, COALESCE(a.nombre, a2.nombre), bc.origen
ORDER BY pares DESC;
```

⚠️ **Interpretación — esto NO es una lista de errores.** Desde el cambio del 05/08 una
competencia sin notas en un bimestre es **legítima**: la boleta la muestra con guion. Cada
fila puede ser:

- **normal** — esa competencia no se trabajó ese bimestre; o
- **un olvido como el de Ética** — el área entera sin evaluar y bloqueada igual.

La señal de alarma es **el área COMPLETA vacía en TODAS sus secciones**, que es la forma
que tuvo Ética (11 secciones, 11 bloqueos, 60 segundos, 0 notas).

**Referencia medida el 06/08/2026** (copia local de prod del 05/08 — repetir en prod):
**B2 = 61 pares** (Personal Social primaria 26 en 8 secciones · Inglés primaria 16 en 12 ·
Taller Raz. Mat. 9 en 9 · CyT primaria 5 · resto 5), todos `origen='docente'`.
**B1 = 116**, incluidos los 11 de Ética.

**Consecuencia de dejar una fila sin revisar:** todos los alumnos de esa sección salen con
**guion** en esa competencia en la boleta, y con la **celda en blanco** en el acta SIAGIE.
Revisarlo ANTES de cerrar: después, el arreglo cuesta una migración (ver la `050`).

### 1.2 A quién apurar (desglose del termómetro)

```sql
SELECT CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS docente,
       g.nombre_display AS grado, s.nombre AS sec,
       COALESCE(a.nombre, a2.nombre) AS area,
       COUNT(*) AS sin_bloquear
FROM (SELECT DISTINCT carga_id, competencia_id, periodo_id FROM calificaciones) cal
LEFT  JOIN bloqueos_competencia bc
       ON bc.carga_id       = cal.carga_id
      AND bc.competencia_id = cal.competencia_id
      AND bc.periodo_id     = cal.periodo_id
INNER JOIN cargas_academicas ca ON ca.id = cal.carga_id
INNER JOIN usuarios  u  ON u.id  = ca.docente_id
INNER JOIN personas  p  ON p.id  = u.persona_id
INNER JOIN secciones s  ON s.id  = ca.seccion_id
INNER JOIN grados    g  ON g.id  = s.grado_id
LEFT  JOIN areas     a  ON a.id  = ca.area_id
LEFT  JOIN subareas  sa ON sa.id = ca.subarea_id
LEFT  JOIN areas     a2 ON a2.id = sa.area_id
WHERE bc.id IS NULL
  AND cal.periodo_id = 2          -- ← el bimestre a cerrar
GROUP BY ca.docente_id, ca.seccion_id, COALESCE(a.nombre, a2.nombre)
ORDER BY sin_bloquear DESC, docente;
```

> El `COALESCE` + `LEFT JOIN subareas` es obligatorio: las cargas de un área
> `con_subareas` traen `area_id = NULL` y cuelgan de la subárea. Un join directo por
> `area_id` las pierde en silencio.

### 1.3 Alerta de evaluación incompleta

Herramienta ya versionada, **no requiere edición**:
`database/verificaciones/alerta_evaluacion_incompleta.sql` (viene con `@periodo := 2`).
Son **4 bloques autocontenidos** — en phpMyAdmin **ejecutar de a uno**:

| Bloque | Qué da |
|---|---|
| D | total (el número que aborta el cierre) |
| A | resumen por sección |
| B | por criterio, **con el docente responsable** |
| C | por alumno |

Resolver cada caso desde el módulo del docente: **registrar la nota** o **registrar la
omisión**. Esta alerta es estable: el trabajo vale igual antes o después del deploy.

### 1.4 Patrón a vigilar: evaluación registrada en la matrícula equivocada

✅ **Resuelto para B2** (verificado el 04/08/2026: la alerta de B2 da **0** y la matrícula
692 ya no aparece en el detalle). Se conserva porque **el patrón puede repetirse en B3/B4**
en cualquier retorno de grado.

**El caso:** BALTAZAR SHALOM CRISTEL — matrícula oficial **190** (Primaria 2° B, la del
SIAGIE) y operativa **692** (1° B, donde CURSA); retorno activo desde el 21/06/2026. La
evaluó la docente de 1° B, pero esa evaluación se registró en las cargas de **2° B**,
repitiendo la misma nota en cada criterio. La alerta marcaba en blanco los criterios de
1° B, y con el guard P4 eso **aborta el cierre**.

- Buscar el caso en el **bloque C** de la alerta (por matrícula).
- Registrar la nota o la omisión **en las cargas del grado donde CURSA** (el operativo).
- ⚠️ **NO es un duplicado de matrícula: no borrar la operativa.**
- Regla vigente desde el 05/08: **se evalúa en la operativa, se documenta con la oficial**
  (`docs/modulos/retorno-grado.md`). Un retorno registrado hoy ya no puede caer a mitad de
  bimestre evaluado: hay candado.
- ⚠️ **En B1 sigue abierto**: 12 alumnos con blancos sin motivo. Mientras B1 esté cerrado
  la alerta ahí solo informa, pero **si alguna vez se REABRE B1 no se podrá volver a
  cerrar** hasta resolverlos (guard P4, en producción desde el 04/08).

## Fase 2 — Deploy (COMPROBACIÓN, no un paso)

> **Para B2 esto ya está hecho** (deploys del 04 y 05/08/2026). Lo que queda sin desplegar
> son commits de **SQL y documentación**, que no cambian nada del cierre. La fase se
> conserva para B3/B4 y como comprobación previa.

**Primero, comprobar si de verdad hay algo que desplegar:**

```bash
git fetch origin
git diff --stat origin/main..origin/dev   # ¿toca app/, resources/, routes/, core/?
```

- Si solo salen `docs/` y `database/` → **no hay deploy que hacer**, sigue a la Fase 3.
- Si sale código → hay deploy, y solo se hace **con el termómetro en 0**.

- [ ] Autorización **explícita** del usuario para mergear `dev` → `main`.
- [ ] ¿Hay migración en el lote? Si la hay, **aplicarla a mano en phpMyAdmin ANTES del
      merge**, nunca después. Y darla por aplicada solo con la salida de su verificación
      **posterior** ejecutada en PROD: el veredicto previo no distingue local de prod.

```bash
git checkout main
git pull origin main   # ← IMPRESCINDIBLE: main local suele estar por detrás
git merge dev          # NO esperes fast-forward: desde el deploy del 05/08 main tiene
                       # un commit de merge que dev no contiene
git push               # Hostinger auto-despliega
git checkout dev
```

- [ ] Tras el push, abrir el sistema y confirmar que responde (el auto-deploy borra todo
      lo no versionado; los secretos viven en `~/siga_secrets/`, fuera del repo).
- [ ] Si algo falla: primer paso, `tail ~/siga_logs/siga.log`.
- ⚠️ **Nunca traigas `main` a `dev` para "sincronizar"**: no aporta contenido y deja un
      merge a medias. Estando en `dev`, `git pull` a secas.

## Fase 3 — Resolver empates

Con el deploy arriba y el termómetro en 0.

- [ ] `/director/orden-merito/{periodo}` → resolver los empates de cada grado.
- [ ] Volver a mirar: la lista debe quedar vacía.

Si aquí aparecen empates que no estaban antes del deploy, es lo esperado: el cálculo en
vivo ahora solo considera competencias bloqueadas.

## Fase 3.5 — Conducta y asistencia (el cierre NO las valida)

🔴 **El código no exige ninguno de los dos registros para cerrar** — eso es lo que quiere
cambiar la decisión D1 de `docs/modulos/cierre-cuatro-registros.md`, aún sin implementar.
Pero **la boleta SÍ los imprime** en cuanto el bimestre queda cerrado: una sección sin
conducta cerrada o sin asistencia bloqueada sale al papel con guiones y nadie avisa.
Mientras no exista el guard, **este paso es la única defensa**.

```sql
-- 3.5.a Secciones SIN conducta cerrada en sus DOS etapas → debe dar 0 filas
SELECT s.id, g.nombre_display AS grado, s.nombre AS seccion,
       cc.ra_bloqueado_en, cc.tutor_cerrado_en
FROM secciones s
JOIN grados g ON g.id = s.grado_id
LEFT JOIN cierres_conducta cc
       ON cc.seccion_id = s.id AND cc.periodo_id = 2 AND cc.anulado_en IS NULL
WHERE cc.id IS NULL OR cc.ra_bloqueado_en IS NULL OR cc.tutor_cerrado_en IS NULL;

-- 3.5.b Secciones SIN cierre de asistencia VIVO → debe dar 0 filas
SELECT s.id, g.nombre_display AS grado, s.nombre AS seccion
FROM secciones s
JOIN grados g ON g.id = s.grado_id
LEFT JOIN cierres_asistencia ca
       ON ca.seccion_id = s.id AND ca.periodo_id = 2 AND ca.anulado_en IS NULL
WHERE ca.id IS NULL;

-- 3.5.c Ninguna sección con DOS cierres vivos → debe dar 0 filas
SELECT seccion_id, COUNT(*) AS vivos FROM cierres_asistencia
WHERE periodo_id = 2 AND anulado_en IS NULL GROUP BY seccion_id HAVING COUNT(*) > 1;
```

⚠️ **Filtrar `anulado_en IS NULL` no es opcional.** Sin ese filtro, una sección cuyo único
cierre fue **anulado** parece cerrada. En B2 hay 7 anulaciones legítimas (el rehacer de las
secciones 1-6 tras el fix del roster del 04/08): 30 filas totales, **23 vivas**.

- **La conducta tiene DOS etapas** (`ra_bloqueado_en` del RA y `tutor_cerrado_en` del
  tutor). Una sola no basta: es la decisión D6.
- Si falta algo y el plazo está vencido → **volver a la Fase 0.1** (ampliar `limite_notas`
  arrastra el bucle completo).

> **Medido para B2 el 06/08/2026:** conducta **23/23 secciones con las dos etapas**, 0
> anuladas; asistencia **23 cierres vivos**, uno por sección, sin duplicados. Las tres
> consultas dan 0 filas.

## Fase 4 — Cerrar

- [ ] Termómetro = 0 (repetir 1.1: es la última comprobación antes de pulsar).
- [ ] Alerta de evaluación incompleta = 0.
- [ ] 0 empates pendientes.
- [ ] Conducta y asistencia completas (Fase 3.5).
- [ ] *(Opcional, barato)* Exportar `orden_merito_snapshot` del periodo desde phpMyAdmin.
- [ ] Cerrar el bimestre desde la UI de Dirección.

**Qué hace `cerrar()` — SEIS operaciones en una sola transacción** (`PeriodoController`):

| # | Operación | Efecto |
|---|---|---|
| 1 | `bloquearCompetenciasPendientes` | Fuerza los bloqueos que falten, con `origen='cierre'` — válvula de escape para el docente que nunca bloqueó (y son distinguibles después) |
| 2 | `crearCierresTransversalesPendientes` | **Cierra las transversales por sección**, respetando lo que el tutor ya hizo |
| 3 | `setEstadoPeriodo` | El periodo pasa a `cerrado` |
| 4 | `marcarBoletasAprobadas` | **Las boletas pasan a OFICIAL** (si luego se reabre, vuelven a BORRADOR) |
| 5 | `restaurarPorCierre` | Restaura una publicación que una reapertura hubiera suspendido. **Nunca crea publicaciones nuevas** |
| 6 | `registrarRanking` | Congela el orden de mérito |

Si algo falla, la transacción hace rollback completo y el error queda en `~/siga_logs/`.

⚠️ **Leer el mensaje de éxito.** Si dice que *"el orden de mérito oficial NO cambió
(bimestre ya publicado); se registró una versión rectificada"*, significa que
`registrarRanking` devolvió `'rectificado'`: el candado 046 se activó porque **ese periodo
ya estuvo publicado**. En un cierre normal de un bimestre nunca publicado **eso no debe
aparecer** — si aparece, para y revisa `periodos_publicacion` antes de seguir.

## Fase 5 — Verificar (prod, solo lectura)

```sql
-- 5.1 El periodo quedó cerrado
SELECT id, nombre_display, estado FROM periodos WHERE id = 2;

-- 5.2 Firma del snapshot recién generado
SELECT COUNT(*) AS filas, MIN(puesto_grado) AS mn, MAX(puesto_grado) AS mx,
       COUNT(DISTINCT grado_id) AS grados, COUNT(DISTINCT seccion_id) AS secciones
FROM orden_merito_snapshot WHERE periodo_id = 2;

-- 5.3 NINGÚN empate petrificado: debe devolver 0 filas
SELECT grado_id, puesto_grado, COUNT(*) AS repetido
FROM orden_merito_snapshot WHERE periodo_id = 2
GROUP BY grado_id, puesto_grado HAVING COUNT(*) > 1;

-- 5.4 Nadie sin puesto de sección: debe dar 0
SELECT COUNT(*) AS sin_puesto_seccion
FROM orden_merito_snapshot WHERE periodo_id = 2 AND puesto_seccion IS NULL;

-- 5.5 El termómetro ahora sí debe dar 0 para este periodo (el cierre forzó los bloqueos)
--     → repetir la consulta 1.1

-- 5.6 Cerrar NO publicó nada. Para un bimestre que NUNCA se publicó (el caso de B2)
--     la respuesta correcta es EXACTAMENTE 0 filas. Si el periodo ya tuvo una
--     publicación previa (reapertura), lo que debe cumplirse es que no haya filas
--     NUEVAS: compara `primera_publicacion_en` con la fecha de hoy.
SELECT * FROM periodos_publicacion WHERE periodo_id = 2;
```

**5.3 es la verificación clave**: si devuelve filas, se petrificaron empates (el hueco
del guard). Mientras el bimestre no esté publicado se corrige reabriendo → resolviendo →
re-cerrando.

## Fase 5.5 — Probar la boleta EN PAPEL (antes de publicar)

🔴 **Pendiente vivo al 06/08/2026: la boleta cambió el 05/08 y se desplegó SIN probarse en
papel.** Ahora lista **todas las competencias del plan** con guion donde no hay dato, y la
restricción dura es **UNA hoja A4 vertical**. El máximo de filas no sube (29 → 29) y el
peor incremento medido es +5, pero eso no está comprobado impreso.

Este es el momento natural: **emitir el documento oficial exige el bimestre CERRADO**, así
que la Fase 4 es la primera vez que se puede sacar el lote de verdad.

- [ ] **Primaria 2.º A** — el mayor incremento de filas.
- [ ] **Secundaria 1.º B o 1.º C** — las que llegan al máximo de 29.
- [ ] **Un 5.º** — confirmar que NO sale Ed. Religiosa (la evalúa Ética y Valores).
- [ ] **Un exonerado** — debe conservar `EXO` (fue una regresión, ya corregida).
- [ ] **Matrícula 556 (ROSALES STEPHANO), Secundaria 4.º A** — el peor caso medido: 6 filas
      con conclusión descriptiva, hasta 233 caracteres. ⚠️ **El alto ya no lo fijan las
      filas sino las conclusiones**, así que esta es la boleta que decide.
- [ ] Comprobar que el **ZIP de borradores** descarga bien en el navegador (verificado en
      servidor, no en navegador).

Detalle y cifras: `docs/modulos/boleta-competencias-completas.md` §8.3.

## Fase 6 — Publicar (acto SEPARADO)

Cuando Dirección lo decida, no como parte del cierre.

- [ ] `/admin/control` → publicar **por nivel**, con fecha/hora.
- [ ] Publicar libera **boletas Y orden de mérito** juntos, para ese nivel.
- [ ] A partir de la **primera** publicación, el snapshot oficial de ese periodo queda
      **INMUTABLE** (candado 046): cierres y rectificaciones posteriores van a
      `orden_merito_rectificado`, visible solo en `/admin/control`.

RA puede imprimir boletas antes de la reunión de entrega sin publicar: el umbral
`'archivo'` ignora la compuerta a propósito.

---

## Criterios de ABORTO

Parar y no cerrar si:

- El termómetro **no** da 0 (salvo decisión explícita de forzar los bloqueos, asumiendo
  el riesgo de petrificar empates no vistos).
- La alerta de evaluación incompleta devuelve casos sin resolver.
- Quedan empates pendientes en `/director/orden-merito/{periodo}`.
- **Alguna sección sin conducta cerrada en sus dos etapas o sin asistencia bloqueada**
  (Fase 3.5). El sistema te dejará cerrar igual: el criterio es humano.
- El deploy dejó el sistema con errores.

## Prohibiciones

- ❌ **No correr `database/backfill_orden_merito.php` en prod.** Usa la regla general
  (filtro por `tipo`) y sobrescribiría el snapshot de B1 (528 → 518). Desde el 26/07
  tiene guard propio, pero la advertencia vale para versiones desplegadas antes.
- ❌ **Ningún script de `database/` debe "limpiar" con DELETE lo que no creó.** Ya pasó:
  una verificación borró el snapshot oficial de B1 en local.
- ❌ **No mergear `dev` → `main` sin preguntar.**

## Después del cierre

- [x] ~~Borrar los backups de conducta de prod `_bkp_conducta_resp_541` y
      `_bkp_calif_conducta_541`, tras el cierre de conducta de la sección A.~~
      **HECHO el 06/08/2026** con la migración 048, antes de cerrar B2: la condición
      real era el cierre de conducta de esa sección (31/07), no el del bimestre.
- [ ] **Opción B del guard de empates** (corrección estructural): mover el guard a
      DESPUÉS del bloqueo forzado, dentro de la transacción y con rollback. Es la
      solución correcta al hueco; se decidió no estrenarla bajo la presión del cierre.
