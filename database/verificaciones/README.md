# Verificaciones — Rediseño del orden de mérito

Scripts CLI de verificación de las fases ya implementadas. Se corren a mano
(`php database/verificaciones/<archivo>.php`) contra la BD local. Asumen el
dataset de referencia (backup activo, mismo que producción): IDs de matrícula/
grado concretos del I Bimestre (541 retirado, 220/666 pendientes, 692/190 retorno).

- **`verif_fase_a_orden_merito.php`** — SOLO LECTURA. Comprueba el filtro por `tipo`:
  pendientes entran al ranking, `trasladado`/`retirado` quedan fuera, el retorno de
  grado sigue anclado (operativa compite, oficial excluida) y B1 sin empates nuevos.
  Lee el cálculo **EN VIVO** (`rankingGradoLive`, por reflexión), no el wrapper
  snapshot-aware: B1 congeló la regla ESPECIAL de la Fase C (roster sin filtro de
  tipo), y desde el snapshot un `trasladado` sí aparece.

- **`verif_fase_b_orden_merito.php`** — Comprueba la migración 046 y el candado de
  inmutabilidad (`registrarRanking`): publicado sin oficial → oficial; publicado con
  oficial → rectificado (oficial intacto). Escribe, pero **todo corre dentro de una
  TRANSACCIÓN con ROLLBACK** y su paso 4 verifica que los conteos volvieron al valor
  previo. NO se limpia con DELETE: su primera versión lo hacía y llegó a destruir el
  snapshot oficial de B1 en local (26/07/2026).

- **`verif_fase1_rediseno_merito.php`** — SOLO LECTURA. P1/P2 del rediseño 2: B1 lee
  del snapshot (528), el universo del cálculo en vivo son solo competencias
  bloqueadas, y el orden estable por `matricula_id` corre sin error.

- **`verif_fase5b_rediseno_merito.php`** — Transacción + ROLLBACK. Con el bimestre
  reabierto el ranking SIGUE saliendo del snapshot (candado 046), mientras que
  `gradosConEmpatesPendientes` sí mira el cálculo en vivo. Su **paso 0 es un control**:
  si el vivo y el snapshot coincidieran, los pasos siguientes no probarían nada.

Requisitos: haber aplicado la migración `046_orden_merito_inmutable.sql` en local y
tener B1 (periodo 1) con sus filas de `periodos_publicacion` (backfill de la 044).

**Antes de creer una prueba del mérito de B1, comprobar que el snapshot tiene sus 528
filas** (`SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = 1`). Si está
vacío, reponerlo con `database/reconstruir_snapshot_b1.php`.
