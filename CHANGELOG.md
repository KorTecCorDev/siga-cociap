# Registro de cambios — SIGA-COCIAP

Sistema Integrado de Gestión Académica del Colegio de Aplicación
«Víctor Valenzuela Guardia» — UNASAM, Huaraz, Áncash, Perú.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado es [SemVer](https://semver.org/lang/es/).

> **Dónde vive cada cosa.** Este archivo registra QUÉ cambió en cada versión.
> El estado vivo del proyecto —pendientes, migraciones aplicadas y planes con
> fecha— está en `docs/ESTADO.md`, y las reglas de negocio de cada módulo en
> `docs/modulos/`. No se duplican aquí.

---

## [1.0.0] — 2026-08-22

Primera versión estable. El corte no se puso donde no faltaba nada, sino donde
**el ciclo académico completo funcionó en producción con usuarios reales**: dos
bimestres cerrados y publicados, boletas entregadas a las familias y el orden de
mérito congelado bajo candado de inmutabilidad.

526 commits desde el 28/04/2026.

### Alcance de esta versión

**Académico**
- Currículo por niveles: áreas, subáreas y competencias, con áreas-curso y
  secciones unidocentes.
- Cargas académicas con detección de solapes, y reemplazo de docente con
  auditoría por snapshot.
- Calificaciones por criterios de evaluación libres y de igual peso, con
  promedio automático, conclusiones descriptivas y bloqueo por competencia.
- Competencias transversales registradas por cada docente y aprobadas por el
  tutor de sección.
- Conducta y asistencia por bimestre, con imprimible e historial.
- Escala 00-20 con punto único de verdad para los umbrales literales
  (AD 18-20 · A 14-17 · B 11-13 · C 00-10).

**Documentos**
- Boleta imprimible A4 apaisada y boleta digital mobile-first, ambas con la
  estructura anual completa de cuatro bimestres.
- Acceso público de las familias **siempre por token**, nunca por identificador
  enumerable; QR generado en local, sin servicios de terceros.
- Salida masiva en ZIP y boleta de archivo para estudiantes trasladados.
- Firma y sello del Director EBR, servidos desde fuera del repositorio.
- Exportación de notas a los formatos oficiales del SIAGIE.

**Gestión**
- Matrículas con apoderados, estados, alta provisional, traslados de entrada y
  salida, retiro reversible, exoneraciones y retorno de grado.
- Constancias de traslado con libro correlativo por año.
- Orden de mérito con cascada de desempate, resolución manual, snapshot por
  bimestre y versión rectificada.
- Centro de control operativo con alertas de evaluación incompleta.
- Ocho roles con control de acceso por controlador.

### Reglas de negocio que definen el sistema

- **Cerrar un bimestre no publica nada.** Publicar es un acto separado, por
  nivel y con fecha y hora propias; admite publicación inmediata o programada.
- **La boleta solo muestra competencias bloqueadas** por su docente.
- **El snapshot oficial del orden de mérito es inmutable** una vez que el
  periodo estuvo publicado; las correcciones posteriores van a una versión
  rectificada, visible solo para el staff.
- **Los estudiantes trasladados y retirados salen de los rosters de
  evaluación**, pero conservan su boleta de archivo.
- **Un retorno de grado se evalúa en la matrícula operativa y se documenta con
  la oficial**: los datos no se copian ni se mueven entre matrículas.

### Corregido en el cierre

- Los tres verificadores que daban falsa alarma. Dos replicaban la compuerta de
  publicación con media regla y fallaban desde que venció la primera publicación
  programada; el tercero confundía «no es el último publicado» con «sin
  publicar» y comparaba por subcadena. La batería quedó en **21/21**.
- Diez rutas registradas hacia tres controladores inexistentes, más la entrada
  muerta del rol `secretaria` en el destino de login.
- Dos umbrales de la escala hardcodeados en el panel de riesgo del Director,
  fuera del inventario de excepciones documentado.
- La constancia de traslado N° 052-2026 volvió a estar vigente tras una
  anulación que no correspondía (migración `054`).

### Estado verificado al cierre

| | |
|---|---|
| Archivos PHP sin error de sintaxis | 214 |
| Rutas registradas, todas resolubles | 195 |
| Rutas POST que validan CSRF | 83 / 83 |
| Controladores con control de acceso | 33 / 33 |
| Puntos de inyección SQL | 0 |
| Salidas sin escapar en vistas | 0 de 1 996 |
| Verificaciones del repositorio en verde | 21 / 21 |
| Migraciones aplicadas | 48 |

### Conocido y no incluido

- **No existe módulo de acceso para apoderados.** El rol está definido y sus
  pantallas construidas, pero no hay ningún usuario con ese rol en producción:
  las boletas llegan hoy por token y por impresión. Queda para la 1.1.
- La regla del periodo final, el punto único de «carga dueña», los cuatro
  registros del bimestre y el cambio de sección están planificados y
  documentados, sin implementar.
- Los roles Director General, Secretaría Académica y Secretaría Administrativa
  existen sin usuarios asignados.

[1.0.0]: https://github.com/KorTecCorDev/siga-cociap/releases/tag/v1.0.0
