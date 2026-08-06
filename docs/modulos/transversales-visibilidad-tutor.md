# Transversales: bloqueos fantasma del cierre + visibilidad del tutor (PLAN)

> **Estado: PLAN COMPLETO Y APROBADO, SIN IMPLEMENTAR (06/08/2026).** Nace de una
> observación del usuario sobre `/admin/control` en el II Bimestre y de evaluar la
> propuesta de "bloquear las transversales antes que las académicas".
> **Sin migración de esquema; sí una migración de DATOS (`051`) en F2.**
> Contexto del módulo: `docs/modulos/calificaciones.md` §"Transversales por docente".
>
> **Decisiones cerradas del usuario (06/08/2026 — no re-preguntar):**
> **(1)** los 130 bloqueos fantasma de B2 **se borran**;
> **(2)** el tutor **solo mira** hasta tener el promedio final — nada de escribir
> conclusiones sobre un parcial, con guard en SERVIDOR;
> **(3)** el resumen **sí muestra el nombre de la carga y del docente**, derogando la
> regla de privacidad del 14/06/2026 (`73838d1`).

---

## 1. El hallazgo: `/admin/control` acusa a 23 docentes de algo que no hicieron

El panel muestra en B2:

> *"Competencias que el cierre bloqueó automáticamente porque el docente no las había
> bloqueado al aprobar el bimestre. 130 competencia(s) en 65 carga(s) de 23 docente(s);
> 130 sin ningún criterio registrado."*

**El mensaje es falso.** Las 130 se explican al 100% sin ningún olvido:

| Causa | Cargas | Docentes | Por qué el docente NO pudo bloquearlas |
|---|---|---|---|
| **A)** Carga TOE / área `tipo='tutoria'` | 23 | 23 | El formulario **no adjunta** transversales a la carga de tutoría — decisión del 07/07/2026, `CalificacionController:507` |
| **B)** Carga **no dueña** en sección unidocente | 42 | 6 | Las TIC/GAMA se adjuntan **una sola vez por área**, en la carga dueña — `CalificacionController:508-514` |
| **C)** Olvido real | **0** | **0** | — |

23 + 42 = 65 cargas × 2 competencias = **130**. Cuadra exacto.

### Causa raíz

`AnioAcademicoModel::bloquearCompetenciasPendientes` (bloque 2, línea 233) inserta las
transversales así:

```sql
FROM cargas_academicas ca
INNER JOIN secciones s ON s.id = ca.seccion_id
INNER JOIN grados    g ON g.id = s.grado_id
INNER JOIN areas     a ON a.tipo = 'transversal' AND a.nivel_id = g.nivel_id
INNER JOIN competencias comp ON comp.area_id = a.id
WHERE ca.estado = 'activa' AND ca.anio_id = (...)
```

**TODAS las cargas activas**, sin excepción. El formulario del docente aplica dos
exclusiones; el cierre forzado, ninguna. Los dos lados de la misma regla divergieron.

### Este defecto YA mordió una vez, y se parcheó del lado equivocado

El comentario de `TransversalModel::estadoCargasSeccion:322-332` documenta que tras un
cierre "las no-dueña sumaban transversales que el total (dueña) no cuenta, inflando el
numerador por encima del total (ej. **53/41**) y habilitando las conclusiones antes de
tiempo". Se arregló **el conteo**, no **el origen**: las filas fantasma se siguen creando.

### Impacto real (medido, para dimensionar sin alarmismo)

- ✅ **NO contamina el promedio agregado.** `getPromediosSeccion` une bloqueos con
  `calificaciones`; una carga fantasma no tiene notas transversales, así que no aporta
  filas. El dato de la boleta es correcto.
- ✅ **NO infla el gate del tutor**: `estadoCargasSeccion` ya usa la lógica de dueña.
- 🔴 **SÍ acusa injustamente a 23 docentes** en un panel que ve la dirección. Es el daño
  principal y es de confianza, no de datos.
- ⚠️ **Sospecha no medida:** al desbloquear una académica de una carga fantasma,
  `liberarTransversalesDeCarga` borra esos bloqueos y `BloqueoController::desbloquear`
  **anula el cierre transversal de la sección**. Podría explicar parte de las **48
  anulaciones sobre 71 cierres** que tiene B2. Verificar al implementar F1.
- **B1 es otra historia:** allí hubo **774** transversales forzadas porque regía el modelo
  viejo (carga única del tutor). No se toca.

---

## 2. Lo que la evaluación de la propuesta dejó establecido

La propuesta original era *"que el docente bloquee las transversales antes que las
académicas para que el tutor no espere"*. Medido:

- **Ya es posible hoy**: `bloquear($cargaId, $competenciaId)` es por competencia y admite
  "propia o transversal", sin guard de orden. **64 cargas de B2 (16%) ya lo hacen.**
- **No destraba nada**, porque el gate del tutor cuenta académicas + transversales, y la
  vista `docente/tutoria.php:98` **oculta la tabla de promedios entera** si no está listo.
- **El acoplamiento es gratuito**: `getPromediosSeccion` filtra `a.tipo='transversal'`, o
  sea que las académicas **no participan del dato que el cierre congela**.
- **Pero la ganancia por sí sola sería nula**: en las 23 secciones de B2 la última
  transversal llegó entre **40 y 144 horas DESPUÉS** que la última académica.

Conclusión: lo que hay que arreglar es **la ceguera del tutor** (F2/F3) y **el ruido del
cierre forzado** (F1), no el orden de bloqueo, que ya es libre.

---

## 3. Fases

Orden: **F1 → F2 → F3 → F4**. Ninguna lleva migración de esquema. F1 es un bugfix con
efecto inmediato en la confianza del panel; F2 y F3 son el pedido del usuario; F4 es la
corrección conceptual del gate.

### F1 — El cierre forzado deja de inventar bloqueos transversales

**Archivo:** `app/Models/AnioAcademicoModel.php` (`bloquearCompetenciasPendientes`,
bloque 2).

Añadir al `WHERE` las **dos** exclusiones que el formulario ya aplica:

1. `AND (a2.tipo IS NULL OR a2.tipo <> 'tutoria')` sobre el área resuelta de la carga
   (`COALESCE(ca.area_id, sa.area_id)`).
2. En secciones `es_unidocente = 1`, solo la **carga dueña** del área: la subconsulta
   `ORDER BY COALESCE(sad.orden,0), cad.id LIMIT 1` que ya usan `estadoCargasSeccion` y
   `CalificacionModel::cargaDuenaTransversales`.

⚠️ **Esa regla de "dueña" quedaría escrita en un CUARTO sitio** (formulario, gate del
tutor, `cargaDuenaTransversales` y ahora el cierre). Al implementar, evaluar si se
unifica en `cargaDuenaTransversales` y el SQL la consume — o dejar constancia explícita
del cuarteto en los cuatro comentarios. **No dejar el cuarto sitio mudo.**

**Verificación:** re-simular el cierre de B2 en transacción con ROLLBACK y comprobar que
inserta **130 filas menos** y que el aviso de `/admin/control` baja a **0 competencias en
0 cargas**.

### F2 — Limpieza de los 130 fantasmas ya creados en B2

✅ **DECISIÓN CERRADA (06/08/2026): SE BORRAN.** F1 evita los futuros; esta fase borra los
existentes para que el panel deje de acusar a 23 docentes por B2.

**Migración de datos `051`** (no de esquema), con la estructura de la 047/050:

- **PASO 1 (solo lectura, de ABORTO):** la huella del servidor (`DATABASE()`, `USER()`,
  `@@version_compile_os`) + el recuento clasificado A/B/C. Debe dar **130 filas en A+B y
  CERO en C**: si aparece una sola en C, hay un olvido real de un docente y **borrarla
  sería destruir su bloqueo legítimo** → abortar.
- 🔴 **Guard indispensable:** ninguna de esas 65 cargas puede tener **calificaciones
  transversales** colgando. Si las tuviera, borrar el bloqueo dejaría notas sin bloqueo
  —el "estado fantasma" (bloqueo + notas + 0 criterios) que el proyecto ya persiguió— y
  además cambiaría el promedio agregado. Hoy las 130 están *sin ningún criterio
  registrado*, así que se espera 0; **se comprueba en el PASO 1, no se asume**.
- **PASO 2:** `DELETE` acotado por `periodo_id = 2` **AND** `origen = 'cierre'` **AND**
  área transversal **AND** (carga TOE **OR** no-dueña de unidocente). Envuelto en
  `START TRANSACTION … COMMIT`, ensayado antes con `ROLLBACK` **en la propia producción**
  (el procedimiento que funcionó con la 050).
- **PASO 3:** el aviso de `/admin/control` para B2 debe quedar en **0 competencias / 0
  cargas / 0 docentes**, y el cierre transversal de las 23 secciones **intacto**.
- ⚠️ **Numeración:** la `049` sigue **reservada** al registro retroactivo de notas, así
  que esta es la **`051`**. Al aplicarlas manda la dependencia, no el número (ya pasó con
  la 050 antes que la 049).
- ⚠️ **Orden respecto a F1:** si se borran los fantasmas **antes** de arreglar el forzado,
  el siguiente cierre los vuelve a crear. **F1 va primero, o al menos en el mismo
  despliegue.**

### F3 — El tutor ve los promedios parciales y un "Ver resumen"

**Archivos:** `Docente\TutoriaController::index` · `resources/views/docente/tutoria.php`
· `pages/_dashboard.scss` (o el parcial donde vivan las clases de tutoría).

1. **Dejar de ocultar la tabla.** Hoy `tutoria.php:98` la pinta solo con
   `$listo || $cerrado`. Pasa a pintarse **siempre**, con dos estados visuales:
   - **provisional** — badge "Parcial: N de M cargas aprobadas", fila por alumno con los
     promedios de lo que ya está bloqueado y **guion** donde aún no hay aporte;
   - **definitivo** — el de hoy, cuando `$listo`.
2. **"Ver resumen" de lo ya aprobado**: bloque que lista **qué cargas ya bloquearon sus
   transversales** y cuáles faltan, con el promedio que aporta cada una.
   ✅ **DECISIÓN CERRADA (06/08/2026): SE MUESTRAN EL NOMBRE DE LA CARGA Y EL DEL
   DOCENTE.** Esto **DEROGA** la regla escrita en `tutoria.php:55` —"NO se expone el
   detalle por carga ni el nombre de otros docentes (información sensible)"—, que nació el
   **14/06/2026** en el commit `73838d1`, dentro de un lote de protección de datos.
   - **Al implementar, borrar ese comentario y sustituirlo por la regla nueva con su
     fecha**, o el código quedará afirmando lo contrario de lo que hace.
   - `estadoCargasSeccion` **ya devuelve** `nombre_display` y `docente_nombre` por carga:
     no hace falta tocar el modelo, solo dejar de ocultarlos en la vista.
   - Alcance de lo que se expone: **área/carga, docente y si sus transversales están
     aprobadas**. Nada de notas de otras áreas ni datos personales (el mismo lote del
     14/06 protegía el DNI: **eso no se toca**).
3. **Conclusiones con promedio parcial:** ✅ **DECISIÓN CERRADA (06/08/2026): el tutor
   SOLO MIRA hasta tener el promedio final.** Con promedio parcial la tabla es de lectura;
   escribir conclusiones se habilita cuando todas las cargas aportaron. Razón: una
   conclusión escrita sobre un parcial puede quedar describiendo un promedio que después
   cambia (de B a A), y ese error lo paga la familia en la boleta.
   - 🔴 **El guard va EN SERVIDOR, no solo en la vista.** Hoy `guardarConclusion` solo
     exige que no haya cierre vigente (`TutoriaController:139`); **no** comprueba
     `$listo`. Sin ese guard, ocultar el textarea es cosmético: el endpoint sigue
     aceptando el POST.
   - El mensaje de estado debe decir **por qué** está en solo lectura ("faltan N cargas
     por aprobar sus transversales"), no solo que no se puede.

### F4 — El cierre transversal deja de depender de las académicas

**Archivo:** `TransversalModel::estadoCargasSeccion`.

Que `total_comp` y `comp_bloqueadas` cuenten **solo las competencias transversales** de
cada carga (manteniendo la lógica de dueña y las exclusiones actuales del `WHERE`). El
tutor podrá cerrar en cuanto todas las cargas hayan aprobado sus TIC/GAMA, sin esperar
las académicas — que no participan del dato.

**Consecuencias que hay que aceptar conscientemente:**

- El % de la barra "Avance de aprobación de la sección" cambia de significado. Hay que
  reetiquetarlo ("Cargas con transversales aprobadas"), o mentirá.
- **Cerrar antes alarga la ventana de la cascada:** desbloquear cualquier académica
  libera las transversales de esa carga y anula el cierre. B2 ya lleva **48 anulaciones
  sobre 71 cierres**; esto podría subir. Es reversible y con traza, pero es trabajo del
  tutor.
- Con F1 aplicado, el universo del gate se reduce a las cargas que realmente registran
  transversales, así que F4 y F1 se refuerzan.

---

## 4. Verificación

Script de solo lectura en `database/verificaciones/`:

1. **F1** — para B2, clasificar los bloqueos transversales `origen='cierre'` en A/B/C
   (TOE, no-dueña, resto). Tras el fix, una re-simulación debe dar **0 en A y B**.
2. **F1** — que el conjunto de cargas a las que el cierre adjuntaría transversales sea
   **idéntico** al conjunto al que el formulario se las adjunta. Es la equivalencia que
   hoy no se cumple: **misma regla, dos implementaciones**.
3. **F3** — el promedio parcial que ve el tutor coincide con
   `getPromediosSeccion` restringido a las cargas ya bloqueadas, y el definitivo (con
   todo bloqueado) coincide **alumno a alumno** con `getPromediosMatricula`, que es la
   fuente de la boleta.
4. **F4** — para las 23 secciones de B2, comparar el gate viejo y el nuevo: cuántas
   habrían podido cerrar antes y **cuánto antes**. Si la respuesta es "ninguna", F4 es
   correcta pero inútil, y conviene saberlo antes de invertir.

---

## 5. Fuera de alcance

- **B1 no se toca**: sus 774 transversales forzadas son del modelo viejo (carga única del
  tutor), no un defecto de esta regla.
- **No se añade ningún guard de orden de bloqueo**: el orden ya es libre y los datos
  muestran que 64 cargas lo aprovechan.
- **No se toca `/admin/control`** más allá de que su aviso deje de dispararse: el texto
  es correcto para el caso que sí describe (un docente que de verdad no bloqueó).
