# Notificaciones — bandeja interna

> **Estado: IMPLEMENTADO en `dev` el 10/09/2026. Migración `058` aplicada en LOCAL.**
> Pendiente de aplicar la `058` en producción y de merge a `main`.
> Módulos relacionados: `matriculas.md` (el evento que la estrena), `usuarios-direccion.md`.

## 1. Por qué existe

Nació de un caso concreto: **las notas del colegio de origen no salen en la boleta**
(regla del colegio, ver `matriculas.md`), así que un docente **no tendría forma de
enterarse de que existen**. Registrarlas sin avisar es registrarlas para nadie.

Ya que hacía falta el canal, se construyó completo: la misma bandeja transporta
**comunicados** que admin / Registro Académico redactan a los docentes.

## 2. Por qué NO se reusó `alertas`

`alertas` ya existía y estaba vacía, así que la tentación era obvia. No sirve:

| | `alertas` | `notificaciones` |
|---|---|---|
| Dirección | tutor → **padre** | sistema o RA → **personal del colegio** |
| Columnas | `tutor_id`, `matricula_id` | `usuario_id` (destinatario) |
| Quién le escribe | **nadie** (0 filas, ningún INSERT en el código) | el evento de notas de origen y los comunicados |
| Quién la lee | solo `/padre/alertas` | la bandeja y la campana |

Son otro emisor, otro receptor y otro esquema. **`alertas` se deja intacta** —la sigue
leyendo `PanelController::alertas`— y `verif_notas_origen.php` comprueba en cada pasada
que no se ha tocado.

## 3. Modelo de datos — migración `058`

`notificaciones`: `usuario_id` (destinatario), `tipo`, `origen`
(`sistema`|`comunicado`), `titulo`, `mensaje`, `enlace`, `emisor_id` (NULL si la generó
el sistema), `leida_en` (DATETIME NULL), `created_at`.
`KEY idx_bandeja (usuario_id, leida_en, created_at)`.

Dos decisiones que conviene no deshacer:

- **`tipo` es VARCHAR, no ENUM.** La lista cerrada vive en `NotificacionModel::TIPOS`,
  así que **añadir un tipo no exige migración**. Mismo patrón que los motivos de omisión.
- **`leida_en` es una fecha, no un booleano.** Guarda además *cuándo* se leyó: sale gratis
  y sirve para auditar.

## 4. Roles

Dos constantes en `NotificacionModel`, y no se listan códigos a mano en ningún otro sitio:

- **`ROLES_RECEPTORES`** = `docente`, `registro_academico`, `admin`.
  `padre` queda fuera: hay **0 usuarios con ese rol** y su superficie sigue oscura.
- **`ROLES_EMISORES`** = `admin`, `registro_academico`. **Deliberadamente NO incluye
  ninguno de `ROLES_DIRECCION`**: los tres directores son SOLO LECTURA (invariante de
  `CLAUDE.md`). Entran a VER su bandeja; el gate de escritura va **por método**, no en el
  constructor. Lo comprueba `verif_notas_origen.php` §7b.

## 5. La campana

El contador vive en `BaseController::view()`, junto a `auth_user` y los flashes, **porque
lo necesita el LAYOUT en todas las páginas** y este proyecto **no tiene middleware** (ver
Convenciones de `CLAUDE.md`: `AuthMiddleware` se eliminó y no se reintroduce).

⚠️ **El global es `null` para quien no recibe notificaciones, y un entero (0 incluido)
para quien sí.** El layout pinta la campana según ese `isset`, no según el número: con `0`
como valor por defecto no habría forma de distinguir «no tiene ninguna» de «este rol no
tiene bandeja». Es un `COUNT` servido por `idx_bandeja` y solo se ejecuta si hay sesión.

## 6. Superficies

| Ruta | Quién | Qué |
|---|---|---|
| `GET /notificaciones` | cualquier autenticado | su bandeja; no leídas primero |
| `POST /notificaciones/leer` | el dueño | marca UNA; responde JSON con el contador |
| `POST /notificaciones/leer-todas` | el dueño | marca todas |
| `GET|POST /notificaciones/comunicado` | `ROLES_EMISORES` | redactar y enviar |

**Aislamiento:** `marcarLeida` filtra por `usuario_id` además del `id`, así que **nadie
puede marcar la notificación de otro aunque adivine el id**. Verificado.

Destinatarios de un comunicado: **todo el rol docente**, o **los docentes con carga activa
en una sección**. El envío va en una transacción.

## 7. Punto de extensión

Para notificar desde otro módulo:
`NotificacionModel::crearParaDocentesDeSeccion($matriculaId, $tipo, $titulo, $mensaje, $enlace)`
— un aviso por docente, no uno por dato. Añade el `tipo` nuevo a `TIPOS` (no hace falta
migración) y llama desde el controlador, **fuera de la transacción del dato**: que falle un
aviso no puede tumbar un registro ya válido.

## 8. Verificación

`database/verificaciones/verif_notas_origen.php`, bloques 5, 6, 7 y 7b: una notificación
por docente y ni una de más, aislamiento entre bandejas, `alertas` intacta y el gate de
roles. Solo lectura salvo por una transacción que termina en rollback.
