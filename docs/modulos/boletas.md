# Módulo: Boletas

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.
>
> **OJO — lectura cronológica:** las secciones antiguas (sesiones 2/3/6) describen
> rutas por id y por código que YA NO EXISTEN; la sección
> "Boleta: documento único por token (24/06/2026)" las supersede. Rutas vigentes:
> público por token (`/boleta/digital/{token}`, `/boleta/ver/{token}`), interno
> docente (`/docente/boleta/{id}[/imprimir]`) e interno gestión
> (`/matriculas/{id}/boleta[/imprimir]`). Ante duda, `routes/web.php` manda.

## Módulo de boleta imprimible (sesión 2)
- **Ruta:** `GET /boleta/{matricula_id}/{periodo_id}`
- **Roles con acceso:** admin, director_general, director_ebr, registro_academico, secretaria, padre
- **Restricción padre:** solo puede ver la boleta de su propio hijo (403 en otro caso)
- **Layout:** `resources/views/layouts/print.php` — sin navbar, sin flash, solo `app.css`
- **Vista:** `resources/views/boleta/alumno.php`
- **Estilos:** `resources/sass/pages/_boleta.scss`
- **Impresora objetivo:** RICOH MP4054 PCL6 — margen `@page: 0.5cm` por todos los lados
- **IMPORTANTE:** La boleta solo muestra competencias cuyo docente haya aprobado/bloqueado.
  `CalificacionModel::getBoletaAlumno()` hace INNER JOIN con `bloqueos_competencia`.

### Decisiones de diseño de la boleta imprimible
- **Conclusión descriptiva:** columna integrada de 60mm en la misma fila de la
  competencia (estilo SIAGIE). CSS `line-clamp: 3` con puntos suspensivos nativos.
  El texto completo se guarda en BD; el truncado es solo presentación CSS.
- **Subárea:** se antepone al nombre de la competencia para áreas `con_subareas`
  (ej: `Aritmética — C23. Resuelve problemas...`). Las áreas-curso no llevan prefijo.
- **Primaria:** muestra solo literal (AD/A/B/C); **Secundaria:** nota numérica + literal.
- **Pie de página:** DOS firmas — Tutor(a) de Aula y Director(a) E.B.R. (sesión 7).
  Se eliminó "Padre/Madre/Tutor(a)". Las líneas se alinean con `boleta-footer__espacio-firma`
  de 18mm fijo en ambos bloques (firma PNG anclada al fondo con `align-items: flex-end`).
- **buildBoletaData():** lógica de carga de datos extraída a método privado compartido
  entre `ver()` (imprimible) y `verDigital()` (digital). Incluye `directorEbr` con
  `firma_path` y `sello_path` del Director EBR vigente.

## Módulo de boleta digital (sesión 3)
- **Ruta:** `GET /boleta/digital/{matricula_id}/{periodo_id}`
- **Mismos roles y restricción de padre** que la boleta imprimible.
- **Layout:** `resources/views/layouts/digital.php` — sin navbar, carga `boleta-digital.js`
- **Vista:** `resources/views/boleta/digital.php`
- **Estilos:** `resources/sass/pages/_boleta-digital.scss`
- **JS:** `public/js/boleta-digital.js` — acordeones, QR (Google Charts API), toast PDF

### Características de la boleta digital
- **Mobile-first, responsive** — 3 breakpoints: < 640px, 640-959px, ≥ 960px
- **Conclusiones descriptivas completas** — sin `line-clamp`, texto íntegro visible
- **Cards expandibles** por área curricular — colapsadas por defecto en móvil
- **QR de verificación** — generado en cliente con `qrcode.min.js` (librería local, sin terceros)
- **Botón PDF** — abre diálogo de impresión del navegador con instrucción "Guardar como PDF"
- **Print A4 portrait** — `@media print` expande todos los acordeones automáticamente
- **BEM prefix `.bd-`** en todos los elementos del componente
- **Logros con color semántico:** AD=verde, A=azul, B=naranja, C=rojo con borde izquierdo
- **`beforeprint` event** expande acordeones al usar Ctrl+P nativo
- **Pie de página:** DOS firmas — Tutor(a) de Aula y Director(a) E.B.R. (sesión 7).
  Pantalla: sello PNG del director (`.bd-solo-pantalla`). Al imprimir: firma PNG + nombre
  (`.bd-solo-impresion`). Alineación con `bd-footer__img-area` de 44px/14mm fijo.
- **IMPORTANTE — orden de rutas:** la ruta literal `/boleta/digital/...` debe registrarse
  ANTES del patrón `/boleta/{matricula_id}/...` en `routes/web.php`, o el router la captura
  primero con parámetros incorrectos.

## Módulo de boletas públicas con código de acceso (sesión 6)

### Propósito
Durante el I Bimestre NO se dará acceso con login a los ~1000 padres.
En su lugar: el admin genera boletas por bimestre, cada una con un
**código de acceso único**, se imprimen con el código + QR y se entregan
físicamente. El padre consulta la **boleta digital pública** (sin login)
ingresando ese código o escaneando el QR.

### Compatibilidad con lo existente
Este módulo NO reemplaza nada. Reutiliza la infraestructura ya construida:
- **Reutiliza** `CalificacionModel::getBoletaAlumno()` (ya filtra por
  `bloqueos_competencia` — solo muestra competencias aprobadas).
- **Reutiliza** `buildBoletaData()` y la vista `resources/views/boleta/digital.php`
  ya existente de la sesión 3 — la boleta pública renderiza el mismo
  componente `.bd-` pero a través de una ruta sin autenticación.
- **No toca** las rutas `/boleta/{id}/{id}` ni `/boleta/digital/{id}/{id}`
  existentes (esas siguen siendo para usuarios autenticados / padres).
- El acceso público es una **capa nueva y paralela**, no una modificación.

### Base de datos — nueva tabla
`database/migrations/005_boletas_publicas.sql`
```sql
CREATE TABLE IF NOT EXISTS boletas_publicas (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id     INT UNSIGNED NOT NULL,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    codigo_acceso    VARCHAR(30) NOT NULL UNIQUE,
    veces_consultada INT UNSIGNED NOT NULL DEFAULT 0,
    ultima_consulta  DATETIME NULL,
    generada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generada_por     INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_matricula_periodo (matricula_id, periodo_id),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    FOREIGN KEY (generada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Formato del código: `COCIAP-2026-B1-XXXXXX` (XXXXXX = 6 alfanuméricos
mayúsculos aleatorios, sin caracteres ambiguos: sin O/0/I/1/L).
Insertar en orden de ejecución SQL después de `004_limpiar_datos_semilla.sql`.

### Modelo nuevo
`app/Models/BoletaPublicaModel.php` extiende `BaseModel`:
- `generarCodigo(int $anio, int $numBimestre): string` — código único verificado
- `generarMasivo(int $periodoId, int $usuarioId): int` — INSERT IGNORE para
  todas las matrículas `aprobada` con ≥1 calificación bloqueada en el periodo
- `getPorPeriodo(int $periodoId): array` — lista con estudiante, grado, sección
- `getPorCodigo(string $codigo): ?array` — busca por código; si existe
  incrementa `veces_consultada` y setea `ultima_consulta`; retorna
  `matricula_id` + `periodo_id` para reutilizar `getBoletaAlumno()`

### Controlador admin
`app/Controllers/Admin/BoletaPublicaController.php`
hereda `BaseController`, `requireRole(['admin','registro_academico'])`:
- `index()` — `GET /admin/boletas-publicas` — selector de periodos
- `porPeriodo($periodoId)` — `GET /admin/boletas-publicas/{periodo_id}`
- `generar($periodoId)` — `POST /admin/boletas-publicas/{periodo_id}/generar`
- `imprimir($periodoId)` — `GET /admin/boletas-publicas/{periodo_id}/imprimir`
  usa `layouts/print.php`, una boleta por página con código + QR visibles

### Controlador público (SIN login)
`app/Controllers/BoletaPublicaController.php` hereda `BaseController`
pero **NO** llama `requireAuth()` ni `requireRole()`:
- `formulario()` — `GET /boleta-publica` — campo para ingresar código
- `consultar()` — `POST /boleta-publica/consultar` — valida código,
  reutiliza `CalificacionModel::getBoletaAlumno()`, renderiza la boleta
  digital pública; código inválido → mensaje de error

### Rutas (routes/web.php)
```php
// Boletas públicas SIN login — registrar ANTES de las rutas /boleta/{id}
$router->get('/boleta-publica',           'BoletaPublicaController@formulario');
$router->post('/boleta-publica/consultar','BoletaPublicaController@consultar');
// Admin
$router->get('/admin/boletas-publicas',                       'Admin\BoletaPublicaController@index');
$router->get('/admin/boletas-publicas/{periodo_id}',          'Admin\BoletaPublicaController@porPeriodo');
$router->post('/admin/boletas-publicas/{periodo_id}/generar', 'Admin\BoletaPublicaController@generar');
$router->get('/admin/boletas-publicas/{periodo_id}/imprimir', 'Admin\BoletaPublicaController@imprimir');
```
**IMPORTANTE — orden de rutas:** igual que la lección de sesión 3 con
`/boleta/digital`, las rutas literales `/boleta-publica` deben ir ANTES
que cualquier patrón `/boleta/{matricula_id}/...` para que el router no
capture "publica" como parámetro.

### AuthMiddleware
Agregar `/boleta-publica` y `/boleta-publica/consultar` al array de rutas
públicas en `app/Middleware/AuthMiddleware.php` (junto a `/login`, etc.).

### Vistas
- `resources/views/admin/boletas-publicas/index.php` — selector periodos (layout app)
- `resources/views/admin/boletas-publicas/periodo.php` — tabla + botón generar (layout app)
- `resources/views/admin/boletas-publicas/imprimir.php` — `layouts/print.php`,
  una boleta por página, código + QR visibles, page-break-after
- `resources/views/boleta-publica/formulario.php` — `layouts/digital.php`,
  diseño institucional simple, logo COCIAP, campo código + botón
- `resources/views/boleta-publica/boleta.php` — `layouts/digital.php`,
  reutiliza el componente `.bd-` de `boleta/digital.php` (boleta completa)

### Estilos
Reutilizar `_boleta-digital.scss` (componente `.bd-`). Solo agregar lo mínimo
para el formulario de código en `_boleta-digital.scss` o un parcial nuevo
`resources/sass/pages/_boleta-publica.scss` importado en `app.scss`.
NUNCA CSS inline en PHP (convención del proyecto).

### Reglas de negocio
- Primaria: solo literal (AD/A/B/C). Secundaria: numeral + literal.
- Solo competencias con docente que aprobó/bloqueó (ya lo garantiza
  `getBoletaAlumno()` con su INNER JOIN a `bloqueos_competencia`).
- Código permanente (no se regenera). Toda la boleta visible (no resumen).
- QR generado con `qrcode.min.js` LOCAL (nunca servicios de terceros — la
  mención original a Google Charts quedó obsoleta). El QR apunta a
  `/boleta-publica` con el código.
- Vista pública sin sesión, sin navbar, sin datos de otros alumnos.
- CSRF con `$this->validateCsrf()` en `POST /boleta-publica/consultar`.


## Boleta: documento único por token (24/06/2026)

> Consolidación de la boleta como **documento oficial único** (digital + imprimible)
> servido SIEMPRE por token. Se retiraron las rutas anónimas por id (enumerables),
> se unificó el ensamblado en un solo modelo y el código tecleado quedó dormido.

### Builder único — `app/Models/BoletaModel.php`
- `armar(int $matriculaId, int $periodoId, bool $soloOficiales = false): ?array` —
  **fusiona las 3 copias** que vivían en `Boleta\BoletaController`,
  `BoletaPublicaController` (público) y `Admin\BoletaPublicaController`. Punto ÚNICO
  de verdad del documento (agregación, transversales, exoneraciones, conducta,
  asistencia, tutor, directorEbr). Es PURO: data in → array out; la autorización
  vive en los entry points.
- **`soloOficiales=true` → solo bimestres `cerrado`** (regla de familias: el BORRADOR
  de Hito A nunca se expone al público). Lo usan las rutas por token. `false` (docente,
  salida masiva admin) muestra todos los periodos.
- **El builder duplicado del controlador público (dormido) NO se tocó** (queda con su
  propia copia para resurrección; sus rutas están comentadas).

### Render y QR únicos — `Boleta\BoletaController::render()`
- Entry points DELGADOS: cada uno decide quién + qué periodo, y delega en `render()`.
- **El QR sale SIEMPRE de `urlBoletaToken()`** (token de la matrícula IDENTIDAD) — una
  sola fuente, fin de las 6 variantes que causaban el bug del QR. La boleta se ancla a
  la identidad (`$data['alumno']['matricula_id']`) para coincidir en retorno de grado.

### Direccionamiento (invariante de seguridad)
- **Cero rutas ANÓNIMAS por id.** Se **borraron** `GET /boleta/{id}/{periodo}` (`ver`) y
  `GET /boleta/digital/{id}/{periodo}` (`verDigital`) — eran enumerables.
- **Público (familias):** SIEMPRE por token, solo oficiales:
  `GET /boleta/digital/{token}` (`verDigitalToken`) y `GET /boleta/ver/{token}` (`verToken`).
- **Interno (docente/admin):** autenticado por id + alcance (`/docente/boleta/{id}[/imprimir]`),
  puede ver BORRADOR. Por estar tras login + 403 por alcance NO es enumerable → se queda por id.
- `padre/notas` enlaza por token (su controlador resuelve el token vía `getOCrearToken`).
- **(02/07/2026, commit `8dcff8a`)** `matriculas/show` YA NO enlaza por token: usa el
  flujo INTERNO (`GET /matriculas/{id}/boleta[/imprimir]` →
  `verDigitalMatricula`/`verImprimirMatricula`, roles admin/registro_academico/
  secretaría_academica/secretaria_administrativa) para que gestión vea el BORRADOR,
  igual que el docente. La pública por token sigue mostrando SOLO lo oficial.

### Tracking de visitas — `matriculas.token_consultas` (migración `028`)
- `028_boleta_token_tracking.sql`: `token_consultas INT` + `token_ultima_consulta DATETIME`,
  con **backfill** desde `boletas_publicas.veces_consultada` (preserva el histórico B1).
- `BoletaPublicaModel::registrarVisitaToken(int $matriculaId)` reescrito: `UPDATE matriculas`
  (ya NO toca `boletas_publicas`). **Cuenta TODO acceso por token** (escaneo de QR o portal
  del padre, digital o impreso). El token es por estudiante → conteo por identidad.
- `BoletaPublicaModel::getOCrearToken(int $matriculaId): string` — token hex-32 permanente,
  get-or-create idempotente.

### Salida masiva (sobrevive, re-apuntada a token)
- `Admin\BoletaPublicaController::{vistaPrevia,boletasAlumno,archivar}` iteran
  `getMatriculasAprobadasParaBoleta` (NO `getPorPeriodo`/código) y arman con
  `BoletaModel::armar` + `urlBoletaToken`. **Independientes del código.**
- Hub `porPeriodo` + vista `periodo.php`: ahora token-céntricos
  (`getEstudiantesParaPeriodo` con `token_consultas`); botones de impresión gated por
  `total_aprobables > 0`. `index.php` cuenta boletas oficiales (no códigos).

### Código tecleado — DORMIDO (conservado para reactivar)
- **Borradas** las rutas: público `/boleta-publica` + `/consultar`; admin `/{per}/generar`,
  `/{per}/actualizar`, `/{per}/imprimir` (hoja de códigos). **Quedan comentadas en
  `routes/web.php` con el snippet para reactivarlas.**
- **Conservado intacto** (sin ruta): `BoletaPublicaController` (público), métodos
  `generar`/`actualizar`/`imprimir` del admin, vistas `boleta-publica/*` e `imprimir.php`,
  tabla `boletas_publicas` y `BoletaPublicaModel::{generarMasivo,getPorCodigo,...}`.
- La generación de **token** (`generar-tokens`) sigue activa.

## Logro anual = nota del último bimestre, solo al cerrarlo (02/07/2026)

> **Bug corregido:** el chip "Anual" (logro final del año) de las boletas mostraba
> el **promedio de los bimestres cerrados** y aparecía apenas se cerraba CUALQUIER
> bimestre — p. ej. con B1/B2 cerrados y B3 activo, mostraba un "anual" que en
> realidad era el promedio de B1-B2. Debe ser la **nota del ÚLTIMO bimestre del año**
> (el 4.º) y aparecer **solo al cerrar ese último bimestre**.

### Causa raíz
- `BoletaModel::armar(soloOficiales=true)` (boleta por token/QR de familias) filtra
  los periodos a `estado='cerrado'` (`getPeriodosDelAnio`), así que `$periodos` NO
  eran los 4 del año sino "los cerrados hasta ahora".
- `buildAreasConBimestres` calculaba `literal_final` promediando sobre ESOS periodos
  (`array_column($periodos,'id')`). Con un solo bimestre cerrado, el chequeo "tiene
  nota en todos los periodos" pasaba trivialmente y el promedio de 1-2 bimestres se
  mostraba como logro anual. El comentario "solo cuando los 4 bimestres tienen nota"
  reflejaba la INTENCIÓN, no el código.

### Regla nueva (decisiones del usuario)
1. **Logro anual = literal de la nota del ÚLTIMO bimestre del año** (mayor `numero`),
   NO un promedio y NO el último bimestre CERRADO (modelo por competencias: el nivel
   alcanzado al final del año).
2. **Solo aparece al cerrar ese último bimestre**; mientras no, el chip "Anual" = `—`.
3. **"Último bimestre" es dinámico** por `MAX(numero)` (no hardcodea 4).
4. **Aplica a TODAS las boletas** (builder único): digital, imprimible, token e interna.

### Implementación (sin migración, sin cambio de vista)
- `BoletaModel::getUltimoBimestreDelAnio(int $anioId): ?array` — `ORDER BY numero
  DESC LIMIT 1` (id/numero/estado).
- `armar()` deriva `$ultimoBimestreId` + `$ultimoCerrado = estado==='cerrado'` y los
  pasa a `buildAreasConBimestres(..., $ultimoBimestreId, $ultimoCerrado)`.
- `buildAreasConBimestres`: `literal_final = $comp['bimestres'][$ultimoBimestreId]
  ['literal']` **solo si `$ultimoCerrado`** y hay nota; si no → `null`. Se eliminó el
  promedio y la variable `$periodIds`.
- **Duplicado dormido** `BoletaPublicaController::buildAreasConBimestres` (código
  tecleado dormido, sin rutas) parcheado con la MISMA regla para paridad futura.
- **EXO intacto:** `ExoneracionModel::inyectarEnAreas` setea su propio
  `literal_final='EXO'` DESPUÉS y respeta el ya calculado.
- **Retroactivo nulo:** al 02/07/2026 ningún año está completo (el IV bimestre nunca
  se cerró), así que no había logro anual correcto previo — el fix solo elimina el
  valor erróneo. La vista `boleta/{digital,alumno}.php` ya lee `literal_final` (sin
  cambios).

## Boleta — asistencia por cada bimestre cerrado + Total (02/07/2026)

> **Bug corregido:** la tabla de asistencia de la boleta mostraba solo el bimestre
> activo (el último cerrado) con datos. En la imprimible las demás columnas salían
> "—"; en la digital solo había `[{bim} | Acum. anual]`. Debía mostrar **una columna
> por cada bimestre CERRADO** (todos los registrados) **+ una columna Total** con las
> sumas.

### Causa
- `BoletaModel::armar()` entregaba `asistencia = { bimestre: getDelBimestreUnion(
  $periodoId), anual: getAcumuladoAnualUnion($periodoId) }` — SOLO el periodo activo
  + un acumulado, sin desglose por bimestre.
- `boleta/alumno.php` YA tenía el andamiaje de columnas por periodo, pero solo podía
  llenar la del activo (el resto "—") porque el builder no daba datos por periodo.

### Regla nueva (decisiones del usuario)
1. **Una columna por cada bimestre CERRADO** (todos los registrados) + **Total**.
2. **Solo cerrados** en TODAS las boletas (familias e interna del docente),
   independiente de `$soloOficiales`.
3. **Total = suma de los bimestres mostrados** (NO `getAcumuladoAnual` por `numero<=`,
   que podría incluir un bimestre no mostrado si uno intermedio se reabriera).

### Implementación (sin migración, sin cambio de esquema)
- `BoletaModel::armar()`: nueva estructura
  `asistencia = ['bimestres' => [ ['id','numero','datos'=>counters], … ], 'total' => sumas]`.
  Itera `getPeriodosDelAnio($anioId, true)` (cerrados, siempre) y acumula el total.
  Reemplaza las claves `bimestre`/`anual`.
- Vistas `boleta/digital.php` y `boleta/alumno.php`: la tabla itera
  `$asistencia['bimestres']` (una columna por bimestre, cada una con SU dato) + columna
  **Total** = `$asistencia['total']`. El guard pasa a `!empty($asistencia['bimestres'])`
  (sin cerrados → la tabla no se muestra). La imprimible dejó de pintar "—".
- SASS `_boleta-digital.scss`: `table-layout: auto`, `.bd-asistencia__scroll`
  (overflow-x en móvil, hasta 4 bim + Total) y `--total` con borde izquierdo.
- **Consumidores:** solo esas 2 vistas + el builder (verificado). El
  `BoletaPublicaController` dormido NO arma asistencia → no se toca. Los métodos
  `AsistenciaModel::getAcumuladoAnual*` quedan sin uso desde la boleta (se conservan).

## Boletas de matrículas desactivadas — vías internas (09/07/2026)

> Antes NINGUNA vía mostraba la boleta de una matrícula `desactivado` (traslado,
> deuda, etc.): los tres resolvers filtraban `estado <> 'desactivado'`. Los datos
> siempre persistieron (`armar()` es puro). Decisión cerrada con el usuario
> (08-09/07/2026); implementado el 09/07/2026.

### Regla
- **Gestión** (`/matriculas/{id}/boleta[/imprimir]`, roles actuales: admin, RA y
  ambas secretarías — los directores quedaron explícitamente FUERA): ve e imprime
  la boleta de CUALQUIER matrícula, incluidas desactivadas y trasladadas.
- **TRASLADADO consumado (`estado='desactivado' AND tipo='trasladado'`) — regla
  refinada 09/07/2026:** su boleta vía gestión es **exclusivamente OFICIAL** — el
  alumno ya tuvo su última boleta oficial y sus notas jamás cambiarán aquí. Se
  sirve con `armar(soloOficiales=true, estructuraCompleta=true)`: **estructura
  anual completa** (las 4 columnas de bimestres, regla de formato de abajo) con
  **DATOS solo de bimestres CERRADOS** (los no cerrados van como columnas
  vacías); **sin banner, CON firma del director y SIN QR** (opción `sinQr` de
  `render()`: el token está muerto y un QR impreso dirigiría a "no encontrado").
  La conducta también se filtra a bimestres cerrados. El periodo se ancla al
  último bimestre CERRADO con notas (`periodoPublicableConNotas(...,
  soloCerrados=true)`); **sin cerrados → 404** (nunca tuvo boleta oficial; su
  documento de salida es la constancia de traslado). Las notas parciales del
  bimestre en curso al momento del traslado quedan FUERA (nunca fueron oficiales).
- **REGLA DE FORMATO (09/07/2026): la boleta mantiene SIEMPRE la estructura
  anual completa** — todas las columnas de bimestres del año; los filtros
  (`soloOficiales`) aplican a los DATOS insertados, no a las columnas.
  `armar()` la implementa con el parámetro `estructuraCompleta`. **Deuda
  técnica consciente:** la boleta por token de familias sigue en el
  comportamiento histórico (columnas colapsadas a cerrados,
  `estructuraCompleta=false`) — el usuario decidió limitarlo al modo trasladado
  por ahora; al migrar el token a la regla, revisar que conducta/notas no
  oficiales no se filtren (el guard de datos ya existe en `armar`).
- **Docente** (`/docente/boleta/{id}[/imprimir]`): ve la boleta de todos los de su
  grilla — `aprobada`, `pendiente` y `desactivado`. Los `tipo='trasladado'` dan
  **403** (nuevo filtro explícito `m.tipo <> 'trasladado'` en `resolverBoletaDocente`;
  antes los cubría de facto el filtro de estado).
- **Invariante — jamás versión OFICIAL de un desactivado NO trasladado** (deuda,
  baja administrativa): `vistaPrevia = true` forzada en toda vía interna (banner
  BORRADOR, sin QR, sin firma en la imprimible), incluso con bimestre cerrado —
  sigue matriculado de facto y sus notas están en flujo. El trasladado es la
  EXCEPCIÓN deliberada (ver regla refinada arriba): registro histórico cerrado.
- **Token público y panel del padre: SIN CAMBIOS.** `resolveToken` conserva su
  `estado <> 'desactivado'` (el QR impreso de un trasladado da 404). El documento
  oficial de un trasladado sigue siendo la constancia de traslado.
- Las matrículas `pendiente` NO se fuerzan a borrador (siguen la regla del periodo,
  como siempre); su exclusión de documentos oficiales la garantizan la salida
  masiva y el orden de mérito (filtran `aprobada`), no la boleta interna.

### Implementación
- `Boleta\BoletaController`: `resolverBoletaDocente` y `resolverBoletaGestion` ya
  no excluyen `desactivado`; retornan `array{periodo_id, estado_matricula[, tipo]}`.
  `optsBoletaGestion()` decide el render de gestión: trasladado →
  `{soloOficiales, vistaPrevia=false, sinQr}`; otro desactivado → vistaPrevia
  forzada; resto → regla normal del periodo. `render()` acepta `sinQr` (vacía
  `url_boleta`; las vistas ya omiten el QR con url vacía, sin tocarlas).
- `matriculas/show.php`: la card "Boleta" dejó de estar gated por `$esActivo`
  (visible en cualquier estado); nota diferenciada: trasladado → "última boleta
  oficial"; otro desactivado → "se emite solo como borrador".
- **Nómina docente** (`Docente\PanelController` + `docente/nomina.php`):
  - `getMatriculados(..., bool $soloAprobadas = true)`: el buscador en vivo pasa
    `false` → incluye `pendiente` y `desactivado` (espejo de la grilla); la
    **nómina IMPRIMIBLE** (`nominaImprimir`) usa el default → **solo `aprobada`**
    (documento oficial SIAGIE, no relajar). El SELECT proyecta `m.estado`.
  - El selector de impresión (`$secciones`) solo cuenta filas `aprobada` (si no,
    inflaría los conteos del documento oficial).
  - Card del buscador: badge `.matricula-badge--pendiente/--desactivado` (reusa el
    global de `_matriculas.scss`) junto al nombre; para desactivados el panel de
    boleta se marca `--borrador` y su etiqueta dice siempre "Borrador · {bim}".
  - `getNominaResumen` (dashboard) y `nomina.js` sin cambios (`data-buscar` intacto).

### Nota conocida (preexistente, NO introducida por este cambio)
La boleta DIGITAL muestra el **sello** del director en pantalla sin gate de
`vistaPrevia` (`digital.php` footer) y sin regla `@media print` que lo oculte —
aplica a TODO borrador (también el del Hito A del docente), no solo a desactivados.
La IMPRIMIBLE (`alumno.php`) sí suprime la firma con `vistaPrevia`. Si se quiere
que el borrador digital tampoco muestre/imprima el sello, es un ajuste aparte.

## Compuerta del Hito A — la nota aparece solo tras la aprobación de RA (09/07/2026)

> **Bug corregido:** un docente bloqueaba su competencia del II Bimestre (aún en
> `registro`, sin Hito A) y esa nota YA aparecía en la boleta interna (docente y
> gestión) — sin banner BORRADOR, porque el ancla era el I Bimestre cerrado. La
> boleta INTERNA se armaba con `armar(soloOficiales=false)`, que insertaba datos de
> TODO periodo con competencia bloqueada, sin mirar el estado del bimestre. La
> compuerta del Hito A ahora rige en todos los procesos de boleta.

### Regla (punto único de verdad: `boleta_estado_bimestre`)
Un bimestre aporta NOTAS a la boleta según su estado de boleta (`helpers.php`):
- **`oficial`** (cerrado) → familias.
- **`borrador`** (activo + `boletas_aprobadas_en`, Hito A) → interno.
- **`registro`** (activo sin Hito A) → NO aporta notas (aunque el docente ya haya
  bloqueado su competencia).

`cerrado` implica que el Hito A quedó registrado: el cierre
(`PeriodoController::cerrar` → `AnioAcademicoModel::marcarBoletasAprobadas`) setea
`boletas_aprobadas_en` con `COALESCE(..., NOW())`. Por eso la compuerta de familias
sigue siendo "solo cerrado" (estrictamente posterior al Hito A) sin contradecir la
regla. (Anomalía histórica: B1 local quedó `cerrado` con `boletas_aprobadas_en=NULL`
por ser previo a la migración 025; `boleta_estado_bimestre` lo trata como `oficial`
por `estado='cerrado'`, así que muestra correctamente.)

### `armar()` — parámetro `$datos` (reemplaza el bool `$soloOficiales`)
`armar(int $matriculaId, int $periodoId, string $datos = 'oficial', bool $estructuraCompleta = false)`:
- **`'oficial'`** → solo bimestres `cerrado` (familias: token, salida masiva con QR).
- **`'borrador'`** → `cerrado` o `borrador` (interno: docente, gestión no trasladado);
  un bimestre en `registro` queda como **columna vacía** (estructura completa).
- **`'todos'`** → incluye `registro` (ÚNICA excepción: vista previa de RA).
- El guard por periodo vive en el helper privado `BoletaModel::periodoAportaNotas`;
  la conducta se filtra con el mismo conjunto de periodos que aportan.
- `getPeriodosDelAnio` ahora también trae `boletas_aprobadas_en` (lo necesita el guard).

### Mapa de umbrales por entry point
- **Familias (`'oficial'`):** token (`verToken`/`verDigitalToken`), salida masiva con
  QR (`Admin\BoletaPublicaController::boletasAlumno` y `archivar` — antes usaban el
  default y filtraban solo notas; ahora `'oficial'`, coherente con lo que resuelve el
  QR). El export SIAGIE ya exige `cerrado` (no usa `armar`).
- **Interno (`'borrador'`):** docente (`verDigital/ImprimirDocente`) y gestión no
  trasladado (`optsBoletaGestion`). El trasladado sigue `'oficial'`+`estructuraCompleta`.
- **Excepción (`'todos'`):** `Admin\BoletaPublicaController::vistaPrevia` — herramienta
  de RA para decidir el Hito A; staff, sin QR, marcada BORRADOR. Muestra el bimestre
  en `registro` a propósito.
- `Padre\PanelController::notas` (F4 último cerrado) no usa `armar`; ya solo oficial.

### Verificado end-to-end
Sembrando una competencia bloqueada + criterio confirmado en B2 (registro): docente y
gestión NO la ven; tras simular el Hito A (`boletas_aprobadas_en`) SÍ la ven con banner
BORRADOR; el token de familias nunca la ve; la vista previa de RA sí la muestra
(excepción); la salida masiva solo muestra lo cerrado con QR. El guard anti-fantasma de
`getBoletaAlumno` (migración 033) sigue exigiendo criterio vivo y confirmado.

## Compuerta de publicación de boletas (21/07/2026, migración 044)

> **Bug de negocio corregido:** poner un bimestre en `cerrado` publicaba sus boletas
> a las familias AL INSTANTE. Pero las boletas se entregan en **reuniones oficiales**
> y primaria se entrega, por lo general, **un día antes** que secundaria: el colegio
> necesita cerrar (congelar notas, generar el snapshot de mérito, exportar al SIAGIE)
> sin que las familias vean nada todavía.

### Regla
**Cerrar NUNCA publica.** Publicar es un acto separado de RA/admin, **por NIVEL** y
con **fecha/hora** (inmediata o programada). Alcance = las 3 superficies de familias:
boleta por token, boleta digital y `/padre/notas`.

### Modelo de datos — `periodos_publicacion`
`(periodo_id, nivel_id, publica_en, suspendida_en, despublicada_en, despublicada_por,
motivo_despublicacion, publicado_por, creado_en)`, UNIQUE `(periodo_id, nivel_id)`.

| Fila | Significado |
|---|---|
| sin fila | no publicado |
| `publica_en` futuro | **programado** (invisible hasta la hora exacta) |
| `publica_en` pasado | **publicado** |
| `suspendida_en` | suspendido por reapertura — **REVERSIBLE** |
| `despublicada_en` | retirado a mano — **DEFINITIVO** (solo republicar a mano lo revive) |

Un solo mecanismo cubre publicar y programar **sin cron**: la condición se evalúa al
leer. La fila **no se borra** al despublicar (se perdería el motivo y el autor): se
marca, igual que `anulado_en`/`motivo_anulacion` en `cierres_conducta`/`cierres_asistencia`.

**Backfill retroactivo obligatorio** en la migración: todo bimestre ya `cerrado` queda
publicado en todos los niveles, con `publica_en = COALESCE(boletas_aprobadas_en, NOW())`.
Sin esto el deploy oculta B1 a TODAS las familias — la regresión más grave posible.

### Punto único de verdad — `PublicacionBoletaModel`
Ningún otro archivo consulta la tabla (mismo criterio que `boleta_estado_bimestre`).
`periodosPublicados($anioId, $nivelId, $ahora)` devuelve el set `[periodo_id => true]`
de lo visible: `publica_en <= $ahora AND suspendida_en IS NULL AND despublicada_en IS NULL`.

**Zona horaria — riesgo resuelto por diseño:** `$ahora` lo calcula **PHP**
(`config('timezone') = 'America/Lima'`, aplicado en `public/index.php`) y viaja como
parámetro preparado. **`NOW()` de MySQL nunca interviene en la lectura**: el huso de
producción (Hostinger) es desconocido y suele ser UTC, así que una publicación
programada a las 18:00 se dispararía 5 horas antes.

### El corte `'oficial'` / `'archivo'` (decisión del usuario)
`armar()` suma un cuarto umbral. **Mismo corte de datos** (solo bimestres cerrados);
lo único que cambia es si se respeta la publicación:

| `$datos` | Quién | Compuerta |
|---|---|---|
| `'oficial'` | familias EN LÍNEA: `verToken`, `verDigitalToken`, `/padre/notas` | **respeta** |
| `'archivo'` | STAFF: salida masiva impresa/ZIP, boleta del trasladado | **ignora** |
| `'borrador'` | docente y gestión | n/a (no mira publicación) |
| `'todos'` | vista previa de RA | n/a |

**El porqué:** RA **imprime las boletas ANTES** de la reunión de entrega; con
`'oficial'` saldrían en blanco. La compuerta protege el **acceso en línea**, no la
impresión del colegio. El QR que va impreso sí respeta la compuerta: al escanearlo el
día de la entrega, la publicación ya está vigente.

### La compuerta oculta el bimestre COMPLETO
No solo las notas: cuando aplica, un bimestre no publicado tampoco aporta
**asistencia** ni **conducta**, y **no arma columna** (con columnas colapsadas). Si no,
la familia vería la asistencia de un bimestre cuyas notas siguen ocultas, delatando que
ya cerró. Hasta la 044 ambos conjuntos coincidían (cerrado == visible) y el filtro no
hacía falta.

### Puntos de lectura (4)
1. `BoletaModel::armar()` + `periodoAportaNotas()` — reciben el set publicado del nivel.
2. `BoletaModel::getAlumno()` — proyecta `n.id AS nivel_id` (la compuerta es por nivel).
3. `BoletaController::resolveToken()` — ancla al último bimestre **PUBLICADO**, no al
   último cerrado. Cerrar B2 no cambia lo que ve la familia: sigue viendo B1 hasta que
   RA publique. Sin ningún publicado con notas cae al primer periodo del año (boleta
   vacía, comportamiento histórico previo al primer cierre — no hay pantalla nueva).
4. `Padre\PanelController::getPeriodoVigentePadre($nivelId)` — se resuelve DESPUÉS de
   conocer al hijo, porque la publicación es por nivel; `getHijo` proyecta `n.id`.

### Matriz de reapertura
| Acción | Efecto |
|---|---|
| Cerrar | NO publica. Solo **restaura** una publicación que una reapertura había suspendido |
| Publicar / Programar | solo si el bimestre está `cerrado` |
| Reabrir | `suspendida_en = ahora` en todos los niveles — reversible |
| Volver a cerrar | `suspendida_en = NULL` → restaura la publicación previa |
| Despublicar a mano | `despublicada_en` + motivo → **no revive** al re-cerrar |

`Director\PeriodoController::cerrar()/reabrir()` entran en sus transacciones existentes
(mismo PDO singleton).

### UI y roles
Tercer paso en `/admin/control` (Centro de Control), después del Hito A y del cierre.
Estado por nivel + Publicar ahora / Programar / Retirar. Publican **`admin` y
`registro_academico`**; `director_general` y `director_ebr` ven el estado pero no
operan — **validado en el método** (`guardPublicacion`), no ocultando el botón.
Rutas POST con `validateCsrf()`: `/admin/control/{periodo_id}/{publicar,programar,despublicar}`.

### Procesos que NO cambian
SIAGIE, orden de mérito y su snapshot, rectificaciones, retorno de grado, boleta del
docente y de gestión: todos siguen mirando solo `cerrado`. Los trasladados **ignoran**
la compuerta (boleta archivada administrativa; el alumno ya no tiene vínculo).

### Verificado end-to-end (19 checks + 8 de render)
B1 sigue visible tras migrar · despublicar primaria no toca secundaria · `'archivo'`,
`'borrador'` y `'todos'` siguen trayendo las notas · republicar revive lo retirado ·
lo programado es invisible hasta la hora exacta · reabrir oculta y re-cerrar restaura ·
lo retirado a mano sigue oculto tras re-cerrar · asistencia/conducta/columna ocultas
con la compuerta y presentes en `'archivo'`. Superficie del token comprobada por HTTP
real con la compuerta encendida y apagada, por nivel.

### Pendiente relacionado (fuera de este trabajo)
**Logro anual** sigue usando "último bimestre cerrado" (`BoletaModel`); debe exigir
**año académico cerrado**. Decisión #9 del plan: queda para el final, el usuario
explicará antes la situación del cierre de fin de año.

## Fixes importantes aplicados (sesión 3)
- `CalificacionModel::getBoletaAlumno()` ahora hace INNER JOIN con
  `bloqueos_competencia` — la boleta solo muestra notas que el docente aprobó.
  Antes mostraba todas las notas guardadas aunque no estuvieran bloqueadas.
- Eliminado `003_bloqueos_competencia.sql.sql` (nombre con extensión duplicada).
- Orden de rutas en `routes/web.php`: `/boleta/digital/{id}/{id}` debe ir ANTES
  de `/boleta/{id}/{id}` para que el router no capture "digital" como parámetro.
