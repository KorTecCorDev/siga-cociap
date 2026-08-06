# La boleta muestra TODAS las competencias del plan, con guion donde no hay dato

> **Estado: IMPLEMENTADO Y VERIFICADO EN LOCAL (05/08/2026), SIN DESPLEGAR.**
> Falta el checklist de impresión en navegador (§8.3), que es lo único que prueba
> el requisito de UNA hoja A4.
> **Manda la §10** (lo que se construyó y en qué se desvió); §1-§7 son el plan
> original, útil como registro de por qué se decidió cada cosa.
> Módulo relacionado: `docs/modulos/boletas.md`.

## 1. Qué se pide

La boleta oficial impresa debe mostrar **todas las competencias del plan de estudios del
alumno**, tengan o no calificación. Hoy solo salen las que tienen nota aprobada y
bloqueada. Donde no haya dato que mostrar (numeral, literal o promedio) va un **guion**,
igual que en la tabla de asistencia.

**Restricción dura: TODO debe seguir entrando en UNA sola hoja A4 vertical.**

## 2. Decisiones cerradas (no re-preguntar)

| # | Decisión |
|---|---|
| D1 | El universo es **el plan que la sección realmente dicta** (cargas activas), NO el catálogo completo del nivel. Lo que se elimina es el filtro por *notas*, no el filtro por *lo que se dicta*. |
| D2 | Un **área entera sin calificar SÍ aparece**, con todas sus competencias en guion. |
| D3 | Los **exonerados siguen mostrando `EXO`**, no guion. |
| D4 | En **secundaria, el área-curso *Educación Religiosa* NO se muestra**: sus notas salen por *Ética y Valores* y su competencia C57. Ya se cumple sin código (área 14 tiene **0 cargas en cualquier estado**). En **primaria SÍ se muestra** (área 5, 12 cargas activas). |
| D5 | El guion respeta el umbral de datos: un bimestre no publicado se ve igual que uno no calificado — mismo criterio que se acordó para asistencia. |

### Por qué se descartó el catálogo completo (medido)

| Fuente | Primaria | Secundaria | Máx. filas |
|---|---|---|---|
| **A · cargas activas de la sección** ← elegida | 27 | 27-29 | **29** |
| B · catálogo completo del nivel | 27 | 32 | 32 |

En 5.º, la opción B habría impreso *Arte y Cultura* y *EPT* con guiones (el grado **no
los lleva**: eso es «no corresponde», no «no calificado») y —lo decisivo— habría sacado
**Educación Religiosa vacía justo al lado de Ética y Valores con notas**: el mismo curso
dos veces.

## 3. Evidencia de que entra en una hoja

Medido sobre las 23 secciones con `armar(..., 'todos', true)`:

```
MAXIMO hoy = 29 filas  ·  MAXIMO con el plan completo = 29 filas
Peor incremento en una seccion: +5  (Primaria 2° A: 22 -> 27)
Secundaria: +0 a +2   ·   Primaria: +1 a +5
```

- **El máximo no sube.** Secundaria 1° B y 1° C ya imprimen 29 filas hoy en una hoja, así
  que 29 está probado en papel.
- Las filas nuevas son las **cortas**: guion, sin conclusión descriptiva (que es lo que
  realmente consume alto, 2 líneas).
- Efecto colateral bueno: hoy el nº de filas **varía entre alumnos de la misma sección**
  (22-25 en Primaria 2° B). Pasa a ser **fijo por sección**, como debe ser un formato oficial.

⚠️ Aun así, el alto NO está probado por cálculo: **hay que verificarlo imprimiendo**
(ver §8). El SASS documenta el reparto de ancho en `_boleta.scss:11-15`.

## 4. Cómo funciona hoy (punto de partida)

- `BoletaModel::armar()` → `buildAreasConBimestres($datosPorPeriodo, ...)`.
- Ese builder **construye las filas a partir de las notas**: recorre las filas crudas y
  hace `$areas[$nombreArea][$compId][...]`. Una competencia sin ninguna nota en el año
  **no existe** en el documento.
- Las filas crudas salen de `CalificacionModel::getBoletaAlumno()`, que hace
  `INNER JOIN bloqueos_competencia` (solo bloqueadas).
- **Transversales:** entran por un camino aparte,
  `CalificacionModel::getTransversalesAgregadas()`, que **devuelve `[]` si no hay cierre
  vigente del tutor** → hoy el bloque entero desaparece.
- En la vista, una celda sin dato se pinta **vacía**, no con guion
  (`resources/views/boleta/alumno.php:182,187,199`).

## 5. Fases de implementación

### F1 · Esqueleto de competencias del plan
Nuevo método (propuesta: `CalificacionModel::estructuraCompetenciasSeccion(int $matriculaId)`)
que devuelve las competencias del plan **con la misma forma que las filas de nota** pero
sin valores: `competencia_id`, `nombre_corto`, `nombre_completo`, `codigo_minedu`,
`area_id`, `area_nombre`, `nombre_boleta`, `alias_boleta`, `area_tipo`, `subarea_nombre`,
`es_unidocente`.

Fuente (recordar que **una carga apunta O a `area_id` O a `subarea_id`**):

```sql
FROM matriculas m
INNER JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id AND ca.estado = 'activa'
INNER JOIN competencias comp
        ON (ca.subarea_id IS NOT NULL AND comp.subarea_id = ca.subarea_id)
        OR (ca.subarea_id IS NULL AND ca.area_id IS NOT NULL AND comp.area_id = ca.area_id)
LEFT  JOIN subareas sa ON sa.id = comp.subarea_id
INNER JOIN areas a     ON a.id  = COALESCE(sa.area_id, comp.area_id)
```

> ⚠️ **NUNCA** `JOIN areas ON a.id = comp.area_id` a secas: las áreas `con_subareas`
> (Matemática, Comunicación, CyT, CCSS) enlazan por subárea y se descartarían **en
> silencio**. Es el error que ya produjo dos mediciones falsas el 05/08.

**Red de seguridad:** unir con las competencias que YA tienen nota, para no perder un
dato si una carga se desactiva con notas puestas. Verificado que hoy el plan activo cubre
el 100% de las notas **salvo las transversales**, que van por su propio camino.

### F2 · Sembrar el esqueleto en el builder
`buildAreasConBimestres` recibe el esqueleto y **crea todas las entradas antes** de
recorrer las notas; después las notas se superponen. Así toda competencia existe aunque
no tenga filas, y el orden de áreas/competencias deja de depender de qué se calificó.

### F3 · Transversales siempre presentes
El bloque de Competencias Transversales debe aparecer **siempre** (con guiones si el tutor
no cerró). Hoy `getTransversalesAgregadas()` devuelve `[]` sin cierre y el bloque
desaparece. Con F1/F2 el esqueleto ya las incluye; hay que comprobar que el camino
agregado **no las duplique** cuando sí hay cierre.

### F4 · Guion en la vista
`resources/views/boleta/alumno.php`: numeral, literal y **Logro Anual** pintan `—` cuando
no hay valor (líneas 182, 187, 199). Los exonerados conservan `EXO` (D3).

### F5 · SASS
Modificador para que el guion salga **apagado**, no como un dato. Precedente exacto en la
boleta digital: `.bd-asistencia__num--pendiente`. **Requiere `npx gulp build`** — y
recordar que `public/css/app.css` va versionado y minificado en una línea.

## 6. Alcance colateral que hay que decidir al empezar

**El builder es COMPARTIDO con la boleta digital.** `buildAreasConBimestres` la usan las
9 entradas de `armar()`. Cambiarlo afecta también a `/boleta/digital/{token}`.

- Opción 1 (recomendada): aplicar a ambas. Coherencia total del documento.
- Opción 2: gobernarlo con un flag como se hizo con `estructuraCompleta` (que gobierna
  **columnas**; esto gobierna **filas**).

## 7. Preguntas abiertas

1. **Retorno de grado — ¿qué plan se muestra?** El alumno en retorno tiene la boleta
   rotulada con la **oficial** (2.º B) pero cursa en la **operativa** (1.º B). ¿El
   esqueleto sale del plan de la sección oficial, de la operativa, o de la unión?
   Hoy no se plantea porque las filas salen de las notas. Afecta a 1 alumno
   (matrícula 190/692). Ver `docs/modulos/retorno-grado.md`.
2. **Columna de conclusión descriptiva:** ¿guion también ahí, o se deja en blanco? La
   petición literal dice guion «donde no existan registros de notas o promedios»; un guion
   en una columna ancha de texto se lee como ruido. **Propuesta: dejarla en blanco** y
   poner guion solo en numeral, literal y Logro Anual.

## 8. Verificación

1. Script nuevo en `database/verificaciones/` (solo lectura, corre en prod): para cada
   sección, comprobar que **todos sus alumnos tienen el mismo nº de filas** y que ese
   número coincide con el plan; y que **ninguna nota existente desapareció** respecto del
   comportamiento anterior (equivalencia, como en `verif_retorno_grado.php`).
2. `php -l` de lo tocado + `npx gulp build`.
3. 🔴 **Checklist de impresión en navegador** (lo único que prueba el requisito real):
   - Primaria 2.º A — el peor incremento (+5 filas).
   - Secundaria 1.º B o 1.º C — las que ya van al máximo (29 filas).
   - Secundaria 5.º — verificar que **no** aparece Educación Religiosa.
   - Un alumno **exonerado** — debe seguir viendo `EXO`.
   - Un alumno con conclusiones descriptivas largas en varios bimestres (caso de más alto).
   - En los cinco: **una sola página** en la vista previa de impresión.

## 9. Estado del repo al escribir este plan

`dev` = `origin/dev` = `ae783ec`, árbol limpio, 12 commits por delante de `origin/main`
(`de449e2`). Sin migraciones pendientes: la `047` ya está aplicada. Este cambio **no
lleva migración**.

---

## 10. LO QUE SE IMPLEMENTÓ (05/08/2026) — manda esta sección

### 10.1 Las tres exclusiones salen solas de las cargas (confirmado con datos)

El usuario reformuló el requisito así: la boleta muestra toda la currícula, **salvo**
Educación Religiosa en secundaria (no se genera su carga; en su lugar va Ética y
Valores, el área vinculada en el SIAGIE) y, **solo en 5.º**, Arte y Cultura y EPT.

Medido sobre el catálogo del nivel contra las cargas activas, las áreas SIN carga son
exactamente:

| Grado | Áreas del catálogo sin carga activa |
|---|---|
| Secundaria 1.º-4.º | Educación Religiosa · **Taller de Pre-Cálculo** |
| Secundaria 5.º | Educación Religiosa · Arte y Cultura · Educación para el Trabajo |
| Primaria (los 6 grados) | *ninguna* |

Es decir: **D1 (universo = cargas activas) produce el resultado pedido sin una sola
excepción escrita a mano**, y de regalo resuelve un cuarto caso que no se había
enunciado — el **Taller de Pre-Cálculo solo se dicta en 5.º**, así que no debe salir
en 1.º-4.º. Con el catálogo del nivel habrían hecho falta tres `if` por grado.

⚠️ **Contrapartida asumida:** si alguien olvida crear una carga, el área desaparece
del documento en silencio. Por eso el bloque 1 de la verificación imprime esta misma
tabla y falla si aparece un área inesperada.

### 10.2 Qué se construyó

| Fase | Dónde | Nota |
|---|---|---|
| F1 | `CalificacionModel::estructuraCompetenciasSeccion()` | Cargas activas → competencias, resolviendo el área con `COALESCE(ca.area_id, sa.area_id)` igual que `getBoletaAlumno`. **No filtra por `a.tipo`** (ver 10.3). Las transversales se añaden en una 2.ª consulta. |
| F1b | `CalificacionModel::boletaContexto()` | Clave nueva **`evaluacion`** (aditiva): la matrícula de la que sale el plan. En un retorno es la **operativa** — decisión del usuario, coherente con «se evalúa donde se cursa». |
| F2 | `BoletaModel::buildAreasConBimestres()` | Nuevo 5.º parámetro `$esqueleto`, sembrado ANTES de las notas. Se extrajeron `nombreAreaBoleta()` y `nuevaEntradaCompetencia()` como punto único, para que la fila de nota y la del esqueleto caigan en la misma clave. |
| F3 | (resuelto en F1) | Las transversales **no** entran por las cargas: su área tiene **0 cargas** en ambos niveles. El plan afirmaba en §5-F3 que el esqueleto ya las incluía y era falso; van en consulta aparte, por `a.tipo='transversal'` del nivel. No se duplican: `getTransversalesAgregadas` cae en la misma clave y se superpone. |
| F4 | `resources/views/boleta/alumno.php` | Guion `&ndash;` en numeral, literal, **conclusión descriptiva** y Logro Anual. |
| F5 | `resources/sass/pages/_boleta.scss` | `.sin-dato` (gris `#bbb`, itálica), mismo lenguaje que `.boleta-asistencia__num--pendiente`. |

**La red de seguridad de F1 no necesitó SQL:** el loop de notas del builder sigue
creando la entrada si no existe, así que una nota de una carga desactivada —o de la
otra matrícula en un retorno— nunca desaparece. Verificado sobre 1943 filas de nota.

### 10.3 Trampas encontradas al implementar (no repetir)

1. **Los exonerados perdían el `EXO`.** `ExoneracionModel::inyectarEnAreas()` solo
   escribía `literal => 'EXO'` **si la competencia aún no existía** en `$areas`. Con
   el esqueleto sembrado, TODA competencia existe siempre → todo exonerado caía en la
   rama `else`, que solo marcaba `es_exonerado`, y su boleta salía con guiones. Se
   añadió `tieneNotasReales()`: si la entrada está vacía se le escribe el EXO igual
   que en la rama nueva; si ya trae notas, no se toca (comportamiento previo).
   Es la regresión que vigila el bloque 4 de la verificación.
2. **No filtrar por `a.tipo`.** Ética y Valores vive en un área `tipo='tutoria'`
   (artefacto de implementación). Copiar el `NOT IN ('transversal','tutoria')` del
   orden de mérito la habría borrado de toda la secundaria.
3. **`Comp. Transv.` ≠ `transversal`.** Las dos vistas separaban el bloque
   transversal con `stripos($nombre, 'transversal')`, pero el área de **secundaria**
   se rotula `Comp. Transv.` (la de primaria, `Competencias Transversales`). Defecto
   **preexistente**: en secundaria el bloque nunca se movía al final ni recibía su
   estilo. Antes pasaba desapercibido porque sin cierre del tutor el bloque no
   aparecía; ahora aparece SIEMPRE, así que se corrigió a `'transv'` en
   `boleta/alumno.php` y `boleta/digital.php`. Ninguna otra área contiene esa cadena.
   Importa además por el orden: el área transversal de secundaria tiene `orden = 13`
   y Ética `orden = 90`, así que sin la corrección las transversales quedaban **antes**
   de Ética en vez de al final.
4. **Copia dormida divergente.** `App\Controllers\BoletaPublicaController` (consulta
   pública por código, rutas comentadas) tiene su PROPIO `buildAreasConBimestres`, que
   NO recibió el esqueleto. Se le dejó una advertencia en el docblock.

### 10.4 Preguntas de §7, ya respondidas

1. **Retorno de grado → plan de la sección OPERATIVA** (+ red de seguridad). Probado
   con el retorno #1 real (oficial 190 = 2.º B, operativa 692 = 1.º B): boleta rotulada
   2.º B, 27 competencias, **0 notas perdidas**. Hoy la decisión no cambia el resultado
   —1.º y 2.º de primaria dictan el mismo plan, 27 competencias ambos—, así que la regla
   queda fijada para cuando sí difieran.
2. **Conclusión descriptiva → GUION también ahí** (el usuario eligió la literalidad
   frente a la propuesta de dejarla en blanco).
3. **Alcance → las dos boletas**, impresa y digital (opción 1 de §6). La digital no
   necesitó cambios: ya pintaba `—` en chips y logro anual y omite el bloque de
   conclusiones cuando no hay ninguna (ahí no existe la «columna», así que el guion
   de la decisión 2 no aplica).

### 10.5 Verificación

`database/verificaciones/verif_plan_completo_boleta.php` (solo lectura, corre en prod).
5 bloques: exclusiones curriculares, uniformidad + exclusiones vistas desde el
documento, equivalencia (ninguna nota perdida), exonerados con EXO, transversales
presentes. **Resultado en local: OK**, con estas cifras por sección:

```
Primaria (12 secciones)      27 competencias · 9 areas
Secundaria 1.º-4.º (9 sec.)  29 competencias · 12 areas
Secundaria 5.º (2 secciones) 27 competencias · 11 areas
Equivalencia: 1943 filas de nota revisadas, 0 perdidas
```

Coincide con la medición de §3 (máximo 29) y confirma el efecto buscado: **el número de
filas ya no varía entre alumnos de la misma sección**.

🔴 **PENDIENTE: el checklist de impresión de §8.3.** Que quepa en una hoja A4 es lo
único que estas cifras no prueban.
