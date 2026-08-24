# Módulo: Usuarios de Dirección (supervisión en solo lectura)

> **Estado: EN `dev`, SIN DESPLEGAR ni PROBAR EN NAVEGADOR (24/08/2026).**
> Las 7 fases están implementadas y las 5 verificaciones automáticas en verde,
> pero **nadie ha abierto todavía una sola pantalla**. La migración **055** está
> aplicada en LOCAL y **pendiente en producción**.

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

### Criterios con descripción

El dato ya viajaba y ya se pintaba… **solo como `title=`**: invisible en celular
y al imprimir. Ahora se lista debajo de la grilla, y **solo para los criterios
que la tienen llena** — medido en producción el 24/08: **555 de 5 533, el 10 %**.

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
