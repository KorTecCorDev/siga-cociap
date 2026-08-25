# Módulo: Usuarios de Dirección (supervisión en solo lectura)

> **Estado: EN `dev`, SIN DESPLEGAR ni PROBAR EN NAVEGADOR (24/08/2026).**
> Las 7 fases están implementadas y las 5 verificaciones automáticas en verde,
> pero **nadie ha abierto todavía una sola pantalla**. La migración **055** ya
> está aplicada en LOCAL (9 roles, `director_academico` = id 9) y sigue
> **pendiente en producción**.
> Después de las 7 fases se añadió el **explorador de criterios** (ver su sección):
> también sin probar en navegador.

## Qué es

Los **tres** cargos de dirección del colegio —Director General, Director EBR y
Director académico— tienen **exactamente las mismas atribuciones**: supervisión
en **SOLO LECTURA** sobre los dos niveles. No hay alcance por nivel y se decidió
que no lo habrá (no existe mapeo usuario→nivel en el sistema).

**Una sola diferencia, y es la razón por la que NO se unifican en un rol único:
solo el Director EBR puede ser asignado como FIRMANTE** de boletas, actas de
desempate y reporte de orden de mérito.

## Punto único: `ROLES_DIRECCION`

`app/Helpers/helpers.php`. Cualquier control de acceso que hable de «los
directores» se apoya en esta constante; **nunca se listan los códigos a mano**.

Nació porque estaban escritos a mano en **44 literales de 16 archivos**: sumar un
tercer director obligaba a tocarlos uno por uno, que es el patrón con el que ya
divergieron cuatro reglas en este repositorio.

### ⚠️ Las DOS excepciones deliberadas

Son el par que sostiene el invariante «solo el EBR firma». **No son un olvido y
no se deben “arreglar”**:

| Sitio | Función |
|---|---|
| `DirectorEbrModel::listarCandidatos()` (línea ~145) | **LISTA** los candidatos a firmante |
| `Admin\DirectorEbrController::asignar()` (línea ~81) | **REVALIDA** en servidor al asignar |

Si la constante se cuela en cualquiera de los dos, un Director General o
Académico podría quedar asignado como firmante de documentos oficiales.

### ⚠️ Trampa al buscar estos códigos

Buscar **siempre entre comillas**: la cadena `director_ebr` también es parte del
nombre de la tabla `director_ebr_historial` (11 apariciones en su modelo), y un
reemplazo masivo la corrompe en silencio.

### La copia que no puede leer la constante

`resources/sass/pages/_admin.scss` — el color del avatar. SASS no ve PHP. **Al
tocar `ROLES_DIRECCION`, revisar ahí también.** Los tres directores comparten un
único grupo ámbar.

## Cómo se retiró la escritura

El permiso vivía en el **constructor**. Como el director conserva la LECTURA, el
constructor sigue dejándolo entrar y **cada método de escritura lleva su propia
guarda** `requireRole(self::ROLES_ESCRIBEN)`. Mismo patrón que
`ControlOperativoController::ROLES_PUBLICAN`.

**30 métodos guardados** en 7 controladores (año y bimestres · cargas ·
reemplazo de docente · panel de bloqueos · desempates · hito A del cierre ·
retorno de grado).

Las vistas se pintan sin controles vía `$puedeEscribir`, pero **eso es UX, no
control de acceso**: la guarda real está en el método.

### Reasignaciones que trajo consigo

| Controlador | Antes | Ahora | Por qué |
|---|---|---|---|
| `Director\BloqueoController` | admin + 2 directores | **admin + registro_academico** + los 3 directores (lectura) | Sin RA, el panel con el que se opera cada cierre quedaba solo en `admin`. Además la card del dashboard ya se le ofrecía a RA y le devolvía **403** |
| `Director\ReemplazoDocenteController` | admin + director_general | **admin + registro_academico** + los 3 (lectura) | Mismo caso |
| `Admin\DirectorEbrController` | admin | **admin + registro_academico** | Registrar la firma del Director EBR no le corresponde al propio Director EBR |
| `Matricula\RetornoGradoController` | admin + RA + director_ebr | **admin + RA** | Era un permiso **huérfano**: para llegar al formulario había que pasar por `/matriculas/{id}`, que le daba 403 |

## 🔴 Matrículas: abrir el constructor NO alcanzaba

`MatriculaController` tenía **cuatro métodos de escritura sin guarda propia** que
confiaban solo en el constructor: `store` (crear matrícula), `storeApoderado`,
`storeDocumentos` y `storeNotasExternas`. Sumar los directores sin blindarlos les
habría dado **el alta de matrículas**.

Se guardaron con `ROLES_MATRICULAN`, que es **exactamente** la lista que tenía el
constructor antes: las secretarías conservan lo que ya hacían.

En la vista, `$puedeGestionar` (admin/RA) no servía para esos tres botones porque
las secretarías también los usan → nació `$puedeMatricular`.

## Navegación: el bucle que tenían

Hasta el 24/08/2026 los directores aterrizaban en `/director/anios`, que **no
enlaza a ningún otro módulo**, y cuyo botón «← Dashboard» los devolvía ahí mismo.
De los nueve módulos que tenían permitidos **alcanzaban uno**; el resto había que
escribirlo a mano en la barra de direcciones. (El único usuario director de
producción entró una vez, en mayo, y no volvió.)

Ahora aterrizan en `/dashboard` y ven **10 cards**. `/consulta-notas` no tenía
card ninguna —se llegaba solo desde bloqueos y rectificaciones—; ahora la tiene.

## Superficies nuevas

| Ruta | Qué |
|---|---|
| `/consulta-notas/{p}/seccion/{s}/asistencia` | 4.º registro del bimestre en lectura |
| `/consulta-notas/{p}/docentes` y `/docente/{id}` | **Eje por docente** (antes solo periodo→sección→carga) |
| `/director/cargas/seccion/{id}/horario` | **Grilla semanal por sección** (antes solo un string resumen) |
| `/admin/cuadros` | **Cuadros estadísticos** — los 5 registros en un tablero |
| `/consulta-notas/{p}/criterios` [+ `/imprimir`] | **Explorador de criterios** — árbol sección → carga → competencia → criterio |

### La regla del dato oficial

> El director ve el **AVANCE** en vivo, pero **todo DATO que se le muestre debe
> estar aprobado y bloqueado**.

Consecuencia importante: **no se derogó ningún invariante**. Calificaciones,
transversales (crudo y agregado), conducta y orden de mérito **mantienen
exactamente los gates que ya tenían**. El «avance en vivo» no se construyó: ya
existía en el tablero de `/director/bloqueos`, que el director conserva en
lectura, más los contadores de Cuadros estadísticos.

**⚠️ La ASISTENCIA es la única excepción, y es deliberada**: se muestra EN VIVO,
sin exigir cierre. Una inasistencia ya ocurrió — no es una calificación sujeta a
aprobación del docente.

### `HorarioModel` — punto único del horario

La consulta vivía en un método **privado** de `Docente\PanelController` y el
armado de la grilla (puntos de corte, `rowspan`, color por ángulo áureo, horas
académicas) estaba inline en `horarioImprimir()`: ~130 líneas inalcanzables desde
cualquier otra pantalla. Se extrajo **sin cambio funcional** (contraste contra
`git HEAD`: 35/35 docentes idénticos) y la grilla vive en el parcial
`resources/views/shared/_horario-grilla.php`, que ambos ejes consumen.

### Criterios con descripción — el intento descartado

La descripción del criterio existía **solo como `title=`** de la cabecera de
columna: invisible en celular y al imprimir. El primer intento la listó en un
cuadro **debajo de la grilla**, dentro de `/consulta-notas/{p}/carga/{id}`.

**Se retiró el 24/08/2026, el mismo día, por decisión del usuario**: no resolvía
lo que hacía falta —ver **todos** los criterios organizados— y dejaba el dato
repartido en dos sitios. En la cabecera sigue el `title=`; el lugar del dato es
el explorador de abajo. *(El cuadro vivía en `consulta-notas/_tabla.php` con la
clase `criterios-descripcion`: si aparece de nuevo, es una reintroducción.)*

### Explorador de criterios (24/08/2026)

`/consulta-notas/{periodo}/criterios` — **tercer eje** de la consulta, junto al de
sección y al de docente: un árbol **sección → carga (área + docente) → competencia
→ criterio**, con la descripción de cada uno. **Sustituye** al cuadro de arriba:
aquel resolvía «que la descripción se vea» dentro de una carga, y lo que hacía
falta era ver **todos** los criterios organizados.

- **Mismo universo que el resto de la pantalla**: solo competencias con bloqueo
  (`competenciasOficiales`). Consecuencia asumida: **en un bimestre en curso sale
  vacío** (B3 tenía 0 de 1 el 24/08), y el estado vacío lo explica en vez de
  parecer roto.
- 🔴 **Las TRANSVERSALES no salen por la vía normal y hay que anexarlas aparte**:
  `getCompetenciasPorPeriodo` une competencia↔carga por el **área de la carga**, y
  las transversales cuelgan de un área propia. Son **743 de los 2 731 criterios de
  B2, el 27 %** — omitirlas dejaba una pantalla que parecía completa y no lo estaba.
  Se anexan con `transversalesConContenido()`, que además exige **contenido real**:
  sin ese EXISTS aparecerían los bloques fantasma del cierre forzado.
- **Un solo método de modelo nuevo**: `CriterioModel::getCriteriosPorPeriodo()`,
  que **no filtra por bloqueo a propósito** — el gate vive en el controlador, punto
  único. Es una consulta para todo el bimestre: recorrer las ~400 cargas llamando a
  `getCriterios()` sería un N+1 de 400 consultas por pantalla.
- **Colapsable con `<details>` nativo y filtro por GET**: cero JavaScript nuevo. El
  selector de bimestre manda `?periodo_id=` y el controlador salta a la ruta que
  toca conservando la búsqueda.
- **El imprimible es una vista aparte a propósito**: un `<details>` cerrado **no
  imprime su contenido**, así que reusar la pantalla habría producido hojas vacías.
- La entrada sin periodo (`/consulta-notas/criterios`, la card del dashboard) reusa
  `ControlOperativoModel::getPeriodoPorDefecto()`, el mismo punto único que Cuadros.

#### Rediseño de lectura, el mismo día

La primera versión tenía **tres** niveles de acordeón y una lista vertical por
competencia. Al verla, el usuario la describió como *«muy cansada de leer»*, y las
cifras le daban la razón: **17 cargas y ~119 criterios por sección**, «nombres» de
criterio que son **frases de 70 caracteres** (hasta 100) y competencias de hasta
**185**. Qué cambió:

- **Dos niveles, no tres**: sección → carga, y dentro **una tabla**
  (competencia con `rowspan` | criterio | descripción). Se escanea por columnas
  en vez de leer frases apiladas.
- 🔴 **Fuera los «Sin descripción»**: eran **2 233 de 2 731 (82 %)** — la mitad del
  ruido de la pantalla. La celda queda vacía y cada carga lleva su contador
  «N con descripción».
- **Cabeceras fijas**: la de sección (`top: 0`) y la de carga (`top: 54px`) se
  quedan pegadas al recorrer los criterios. El `thead` **no** es sticky a
  propósito: una carga tiene ~7 filas y una tercera cabecera solo comería pantalla.
- 🔴 **NO hay buscador de texto, y es deliberado.** Lo hubo unas horas y se retiró
  el mismo día: *«el director no sabe cuáles son los criterios, así que no puede ser
  una modalidad para la búsqueda»*. Los criterios los redacta cada docente; buscarlos
  por su redacción exige conocerlos de antemano. **La pantalla se recorre solo por lo
  que el director SÍ conoce: nivel, grado, sección y docente.** Si alguien propone
  «añadir un buscador», esta es la razón por la que no está.
- **Cuatro filtros estructurales.** Los catálogos se calculan **antes** de filtrar
  —si no, el propio filtro vaciaría su selector y no habría cómo volver atrás— y
  salen de las mismas filas, sin consulta extra.

#### Cascada de los cuatro filtros (25/08/2026)

Los cuatro selectores eran **listas planas e independientes**: con Nivel =
Secundaria, Grado seguía ofreciendo 6.º, Sección las 23 del colegio y Docente
los 35. Al arreglarlo apareció un fallo mayor debajo.

- 🔴 **EL GRADO SE IDENTIFICA POR `grados.id`, NUNCA POR `grados.numero`.** El
  número **colisiona entre niveles**: 1.º de primaria y 1.º de secundaria son
  los dos `1`. El catálogo se indexaba por número, así que **los 11 grados
  reales se fundían en 6 opciones** y `?grado=1` devolvía a la vez 1.ºA/B de
  primaria y 1.ºA/B/C de secundaria — **5 secciones de dos niveles**. El
  imprimible remataba estampando «1.º» sin decir de cuál. Por eso la etiqueta
  del selector lleva el nivel («1.º Secundaria»): con Nivel en «Todos» el
  nombre por sí solo tampoco distingue. Obligó a añadir `g.id AS grado_id` a
  `CalificacionModel::getCompetenciasPorPeriodo()` (aditivo; sus otros dos
  consumidores leen por clave nombrada).
- ⚠️ **UN DOCENTE NO PERTENECE A UN NIVEL**: **2 de los 35** con carga activa
  dictan en primaria **y** en secundaria. Su selector se recorta por
  **pertenencia** —aparece si al menos una de sus secciones sobrevive a los
  demás filtros—, y por eso cada opción viaja con su **conjunto** de secciones
  (`data-secciones`). Con un atributo único («el nivel del docente») esos 2
  desaparecerían del selector en uno de los dos niveles y **sin ningún síntoma**.
- **La cascada es DESCENDENTE**: nivel → grado → sección → docente. Elegir un
  docente **no** recorta los de arriba (decisión explícita, no un olvido).
- **Se resuelve en el cliente** (`resources/js/criterios.js`), sin recarga y sin
  un segundo bloque de datos: el nivel y el grado de cada sección ya viajan en
  sus propias `<option>`, y el mapa se lee de ahí. Va **fuera** del guard de
  `#criterios-arbol` —el formulario existe también en la pantalla vacía, que es
  justo donde hace falta rectificar la combinación— y usa `option.hidden`, no
  `style.display`, que no es fiable para ocultar opciones.
- **Un filtro que queda inválido se limpia solo y en silencio** (vuelve a
  «Todos»), como ya hace `cargas.js` con las áreas. `recortar()` devuelve el
  valor **vigente** tras el posible reseteo: los eslabones siguientes tienen que
  encadenarse sobre ese valor, no sobre el que acaba de invalidarse.
- **Los catálogos siguen calculándose antes de filtrar**: la cascada solo decide
  qué se *ve*, nunca qué *existe*. Verificado en
  `database/verificaciones/verif_criterios_filtros_cascada.php`, que ejercita
  `arbolCriterios()` real por reflexión y contrasta contra SQL independiente
  (incluidas **las dos ramas** del grado ambiguo: 1.º primaria → 2 secciones,
  1.º secundaria → 3).

#### Cambiar de bimestre limpia la consulta (25/08/2026)

El selector de bimestre **auto-aplica** (`onchange="this.form.submit()"`, el
mismo patrón que las otras 9 vistas del repo) y **el salto limpia los cuatro
filtros**. Deroga la decisión del 24/08 de arrastrarlos.

- **Por qué se derogó**: los catálogos se recalculan por bimestre. B1 y B2 tienen
  catálogo idéntico (2 niveles · 11 grados · 23 secciones · 35 docentes), pero un
  bimestre **en curso** es mucho más pequeño —B3 tenía 1 de cada el 25/08—, así
  que arrastrar la consulta de B2 a B3 dejaba la pantalla vacía **con los
  selectores en «Todas»**: el `<option>` filtrado ya no se pintaba. Se cambia de
  bimestre para ver el bimestre, no para repetir la búsqueda.
- 🔴 **Se limpia SOLO cuando el bimestre CAMBIA** (`$destino !== $periodoId`). Si
  se limpiara en cada envío, el botón **Aplicar borraría los filtros que el
  usuario acaba de elegir** y la pantalla no volvería a filtrar nunca. Es la
  trampa de este cambio.
- **La regla vive en el SERVIDOR** (el redirect de `criterios()`); el `onchange`
  solo dispara el envío. Si la limpieza estuviera en el JS habría dos reglas para
  lo mismo. Sin JS, el botón Aplicar hace exactamente lo mismo.
- **Guarda de existencia en `arbolCriterios`**: además, cualquier filtro cuyo
  valor **no esté en el catálogo de ese periodo** se descarta, y el método
  devuelve los **filtros efectivos** (clave `filtros`) que la pantalla y el
  imprimible usan para marcar los selectores. Cierra el caso de la URL escrita a
  mano o el marcador guardado de otro bimestre. ⚠️ Valida **existencia, no
  coherencia**: que el grado pertenezca al nivel elegido lo resuelve la cascada
  del cliente, y duplicar esa regla aquí sería la enésima copia.
- **Caso residual conocido**: una URL a mano con dos valores que existen pero no
  casan (`?nivel=1&grado=7`, 1.º de *secundaria* bajo *primaria*) pasa la guarda
  de existencia; la cascada la limpia en el cliente al cargar, pero el servidor
  ya filtró con ella. Solo alcanzable escribiendo la URL.

#### Chip con el código de la competencia (25/08/2026)

La columna «Competencia» repite el **nombre completo** (hasta 185 caracteres) y
varias competencias del mismo área empiezan igual. Ahora cada una lleva delante
su **`codigo_minedu`** (C1…C57) como chip.

- **Va DELANTE del nombre, no detrás**: con nombres de 185 caracteres, un chip al
  final del párrafo no sirve de ancla. Delante se alinea entre filas y la columna
  se escanea de un vistazo. Misma decisión en pantalla y en el imprimible.
- **Sin casos borde**: las 59 competencias tienen código y los 59 son distintos
  (medido), así que el chip no puede salir vacío ni ambiguo. El `if` que lo
  envuelve es defensivo, porque la columna admite NULL.
- 🔴 **El chip es `.competencia-card__codigo`, el del PROYECTO — no uno propio.**
  Ese es el chip de código de competencia del sistema y ya se usa en otras 5
  vistas (`docente/calificaciones`, `docente/resumen-competencia`,
  `docente/historial-carga`, `padre/notas`, `consulta-notas/carga`). Su
  `margin-right` ya asume que va **delante** del nombre. El imprimible usa el
  mismo chip y solo le ajusta la métrica al A4 desde `.criterios-print`; colores
  y forma **no** se redefinen, para que un cambio del chip los alcance a todos.
  *(Nació un `criterios-chip--codigo` propio y se retiró el mismo día: se veía
  igual y habría divergido en el siguiente retoque.)*
- ⚠️ **Al buscar ese estilo, cuidado con dos trampas.** Está escrito como
  `&__codigo` anidado dentro de `.competencia-card`, así que **`grep
  "competencia-card__codigo"` sobre el SCSS no lo encuentra** — hay que buscar el
  bloque padre. Y existe una copia idéntica en `components/_dashboard.scss` que
  **no se importa desde ningún sitio**: editar esa no cambia nada en pantalla.
  La vigente es la de `pages/_dashboard.scss`.
- ⚠️ **La clave `codigo` se rellena en las DOS ramas del árbol**: las académicas
  desde `getCompetenciasPorPeriodo` (columna nueva `competencia_codigo`) y las
  transversales desde `transversalesConContenido`, que ya la traía. Se llaman
  igual para que la vista no tenga que saber de qué rama salió cada competencia.
  El verificador comprueba **cada rama por separado**: una de las dos podía
  quedarse sin código sin ningún síntoma visible.
- 🔴 **B1 NO sirve para probar la rama transversal**: allí las transversales
  cuelgan de 23 cargas `estado='inactiva'`, que el explorador excluye a
  propósito, y el árbol de ese periodo sale con **0** transversales. Por eso el
  verificador recorre **todos** los periodos (B2 aporta 690) y trata «la rama es
  observable» como una aserción más — si deja de haberlas, avisa en vez de pasar
  en verde sin haber probado nada.

- **Se despliega solo en cuanto acotas**: elegir una sección (~119 criterios) o un
  docente (~135) abre TODO para leerlo de corrido, porque el objetivo es *mostrar*,
  no esconder tras clics. El tope `CRITERIOS_ABRIR_TODO` (200) cubre esas dos
  unidades de trabajo y deja fuera el caso intermedio —un nivel entero son **1 325**
  criterios—, donde se abren las secciones y las cargas siguen plegadas.
- **JS mínimo y prescindible** (`resources/js/criterios.js`): solo los botones de
  expandir/contraer. El árbol es `<details>` nativo — sin el script la pantalla
  sigue siendo plenamente usable.
- El **imprimible hereda los filtros y los declara** en su cabecera: un listado
  parcial que no dice que lo es se lee como el listado completo.

### Cuadros estadísticos: compone, no calcula

🔴 `CuadrosEstadisticosController` **no tiene ni un `SELECT`**. Cada bloque llama
al método que ya existe (`MatriculaModel::getResumen`,
`AnioAcademicoModel::getResumenBimestre` / `getStatsCierre` / `getReaperturas`,
`ConductaModel::getResumenSeccionesPorPeriodo`,
`AsistenciaModel::getProgresoPorSeccion`, `OrdenMeritoModel::gradosConEmpatesPendientes`).
Si hace falta un indicador que no existe, **se añade al modelo que lo posee**.

El selector de bimestre sale de `ControlOperativoModel::getPeriodos()` /
`getPeriodoPorDefecto()`: escribirlo aquí habría sido la **quinta** copia de esa
consulta en el repositorio.

## Verificación

```bash
php database/verificaciones/verif_roles_direccion.php          # punto único + las 2 excepciones
php database/verificaciones/verif_horario_modelo.php           # contraste contra git HEAD
php database/verificaciones/verif_rol_director_academico.php   # migración 055 + color
php database/verificaciones/verif_direccion_solo_lectura.php   # las 30 guardas + integridad de vistas
php database/verificaciones/verif_direccion_superficies.php    # fases 4-7, con render real
```

Todas son de **solo lectura** y corren en producción.

> ⚠️ `verif_horario_modelo.php` reconstruye el algoritmo VIEJO desde `git HEAD`.
> Cuando estos cambios se mergeen a `main`, ese contraste dejará de tener sentido
> (HEAD ya traerá el código nuevo) — es un verificador **de la migración**, no
> permanente.
