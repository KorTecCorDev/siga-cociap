# Runbook — Cierre de un bimestre

> Procedimiento operativo para cerrar un bimestre en PRODUCCIÓN.
> Escrito el **29/07/2026** para el **cierre del II Bimestre (periodo_id = 2)**, pero
> redactado para reutilizarse en B3 y B4: donde dice `@periodo := 2`, cambiar el número.
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

## Fase 0 — Precondición

- [ ] Pasó la fecha límite de calificación de los docentes (**para B2: 31/07/2026**).

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

### 1.4 Caso nominal a vigilar (B2)

**BALTAZAR SHALOM CRISTEL** — matrícula oficial **190** (Primaria 2° B, la del SIAGIE) y
operativa **692** (1° B, donde CURSA); retorno activo desde el 21/06/2026. La evaluó la
docente de 1° B, pero esa evaluación se registró en las cargas de **2° B**, repitiendo la
misma nota en cada criterio. Resultado: la alerta le marca en blanco los criterios de
1° B y **aborta el cierre**.

- Buscarla en el **bloque C** de la alerta (matrícula 692).
- Registrarle la nota o la omisión en las cargas de 1° B antes de cerrar.
- ⚠️ **NO es un duplicado de matrícula: no borrar la 692.**

## Fase 2 — Deploy

Solo cuando el termómetro dé **0**.

- [ ] Autorización explícita del usuario para mergear `dev` → `main`.
- [ ] **No hay migración pendiente** en este lote → el deploy es solo código.
      (Si en el futuro la hubiera: **aplicarla a mano en phpMyAdmin ANTES del merge**,
      nunca después.)

```bash
git checkout main
git merge dev          # debería ser fast-forward
git push               # Hostinger auto-despliega
git checkout dev
```

- [ ] Tras el push, abrir el sistema y confirmar que responde (el auto-deploy borra todo
      lo no versionado; los secretos viven en `~/siga_secrets/`, fuera del repo).
- [ ] Si algo falla: primer paso, `tail ~/siga_logs/siga.log`.

## Fase 3 — Resolver empates

Con el deploy arriba y el termómetro en 0.

- [ ] `/director/orden-merito/{periodo}` → resolver los empates de cada grado.
- [ ] Volver a mirar: la lista debe quedar vacía.

Si aquí aparecen empates que no estaban antes del deploy, es lo esperado: el cálculo en
vivo ahora solo considera competencias bloqueadas.

## Fase 4 — Cerrar

- [ ] Termómetro = 0 (repetir 1.1: es la última comprobación antes de pulsar).
- [ ] Alerta de evaluación incompleta = 0.
- [ ] 0 empates pendientes.
- [ ] Cerrar el bimestre desde la UI de Dirección.

El cierre, en una transacción: fuerza los bloqueos pendientes
(`bloquearCompetenciasPendientes`, origen `'cierre'` — es la válvula de escape para el
docente que nunca bloqueó) y registra el ranking (`registrarRanking`).

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

-- 5.6 Cerrar NO publicó nada: debe devolver 0 filas (o solo publicaciones previas)
SELECT * FROM periodos_publicacion WHERE periodo_id = 2;
```

**5.3 es la verificación clave**: si devuelve filas, se petrificaron empates (el hueco
del guard). Mientras el bimestre no esté publicado se corrige reabriendo → resolviendo →
re-cerrando.

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
- El deploy dejó el sistema con errores.

## Prohibiciones

- ❌ **No correr `database/backfill_orden_merito.php` en prod.** Usa la regla general
  (filtro por `tipo`) y sobrescribiría el snapshot de B1 (528 → 518). Desde el 26/07
  tiene guard propio, pero la advertencia vale para versiones desplegadas antes.
- ❌ **Ningún script de `database/` debe "limpiar" con DELETE lo que no creó.** Ya pasó:
  una verificación borró el snapshot oficial de B1 en local.
- ❌ **No mergear `dev` → `main` sin preguntar.**

## Después del cierre

- [ ] Borrar los backups de conducta de prod `_bkp_conducta_resp_541` y
      `_bkp_calif_conducta_541`, tras el cierre de conducta de la sección A.
- [ ] **Opción B del guard de empates** (corrección estructural): mover el guard a
      DESPUÉS del bloqueo forzado, dentro de la transacción y con rollback. Es la
      solución correcta al hueco; se decidió no estrenarla bajo la presión del cierre.
