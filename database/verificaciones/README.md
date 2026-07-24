# Verificaciones — Rediseño del orden de mérito

Scripts CLI de verificación de las fases ya implementadas. Se corren a mano
(`php database/verificaciones/<archivo>.php`) contra la BD local. Asumen el
dataset de referencia (backup activo, mismo que producción): IDs de matrícula/
grado concretos del I Bimestre (541 retirado, 220/666 pendientes, 692/190 retorno).

- **`verif_fase_a_orden_merito.php`** — SOLO LECTURA. Comprueba el filtro por `tipo`:
  pendientes entran al ranking, `trasladado`/`retirado` quedan fuera, el retorno de
  grado sigue anclado (operativa compite, oficial excluida) y B1 sin empates nuevos.

- **`verif_fase_b_orden_merito.php`** — Comprueba la migración 046 y el candado de
  inmutabilidad (`registrarRanking`): publicado sin oficial → oficial; publicado con
  oficial → rectificado (oficial intacto). **Se autolimpia** al final (borra el
  snapshot y el rectificado de B1 que crea para la prueba), así deja la BD como estaba.

Requisitos: haber aplicado la migración `046_orden_merito_inmutable.sql` en local y
tener B1 (periodo 1) con sus filas de `periodos_publicacion` (backfill de la 044).
