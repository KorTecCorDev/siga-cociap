<?php

namespace App\Models;

/**
 * PublicacionBoletaModel
 *
 * COMPUERTA DE PUBLICACION DE BOLETAS (migracion 044).
 *
 * PUNTO UNICO DE VERDAD de la tabla `periodos_publicacion`: ningun otro
 * archivo la consulta directamente (mismo criterio que
 * boleta_estado_bimestre para la compuerta del Hito A).
 *
 * Cerrar un bimestre YA NO publica sus boletas: publicar es siempre un
 * acto separado, POR NIVEL y con fecha/hora, porque las boletas se
 * entregan en reuniones oficiales y primaria suele entregarse un dia
 * antes que secundaria.
 *
 * ZONA HORARIA — no usar NOW() de MySQL para LEER. El huso del servidor
 * de produccion (Hostinger) es desconocido y suele estar en UTC: una
 * publicacion programada para las 18:00 se disparia 5 horas antes. El
 * "ahora" lo calcula PHP con el timezone de la app (America/Lima, ya
 * aplicado en public/index.php) y viaja como parametro preparado.
 */
class PublicacionBoletaModel extends BaseModel
{
    protected string $table = 'periodos_publicacion';

    /**
     * "Ahora" segun el timezone de la APLICACION (no el del motor MySQL).
     * Todo criterio temporal de esta compuerta pasa por aqui.
     */
    public function ahora(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Ids de los periodos PUBLICADOS de un anio para un nivel, como set
     * [periodo_id => true] para chequeo O(1) desde el armado de la boleta.
     *
     * Publicado = tiene fila, su hora ya llego, no esta suspendido por
     * reapertura y no fue despublicado a mano.
     */
    public function periodosPublicados(int $anioId, int $nivelId, ?string $ahora = null): array
    {
        $filas = $this->query("
            SELECT pp.periodo_id
            FROM periodos_publicacion pp
            INNER JOIN periodos p ON p.id = pp.periodo_id
            WHERE p.anio_id            = ?
              AND pp.nivel_id          = ?
              AND pp.publica_en       <= ?
              AND pp.suspendida_en    IS NULL
              AND pp.despublicada_en  IS NULL
        ", [$anioId, $nivelId, $ahora ?? $this->ahora()]);

        $set = [];
        foreach ($filas as $f) {
            $set[(int) $f['periodo_id']] = true;
        }
        return $set;
    }

    /**
     * Estado de publicacion de UN periodo en todos los niveles, para la UI
     * del Centro de Control. Devuelve una fila por nivel (exista o no la
     * fila de publicacion) con un `estado` derivado ya calculado:
     *   'sin_publicar' | 'programado' | 'publicado' | 'suspendido' | 'despublicado'
     */
    public function estadoPorNivel(int $periodoId, ?string $ahora = null): array
    {
        $ahora = $ahora ?? $this->ahora();

        return $this->query("
            SELECT
                n.id                       AS nivel_id,
                n.nombre                   AS nivel_nombre,
                pp.publica_en,
                pp.suspendida_en,
                pp.despublicada_en,
                pp.motivo_despublicacion,
                CASE
                    WHEN pp.id             IS NULL     THEN 'sin_publicar'
                    WHEN pp.despublicada_en IS NOT NULL THEN 'despublicado'
                    WHEN pp.suspendida_en   IS NOT NULL THEN 'suspendido'
                    WHEN pp.publica_en      >  ?        THEN 'programado'
                    ELSE 'publicado'
                END                        AS estado
            FROM niveles n
            LEFT JOIN periodos_publicacion pp
                   ON pp.periodo_id = ?
                  AND pp.nivel_id   = n.id
            ORDER BY n.id
        ", [$ahora, $periodoId]);
    }

    /**
     * ¿Existe el nivel? Valida el nivel_id que llega por POST antes de escribir,
     * para responder con un mensaje claro en vez de reventar contra la FK.
     */
    public function nivelExiste(int $nivelId): bool
    {
        return $this->queryOne("SELECT id FROM niveles WHERE id = ? LIMIT 1", [$nivelId]) !== null;
    }

    /**
     * Publica (o reprograma) un periodo en un nivel. $publicaEn en el
     * futuro = publicacion PROGRAMADA; en el pasado o ahora = inmediata.
     *
     * Es un upsert: republicar limpia tanto la suspension por reapertura
     * como la despublicacion manual (volver a publicar a mano es la unica
     * via de revivir lo despublicado).
     *
     * OJO: quien llama debe haber verificado que el periodo esta CERRADO
     * (regla de negocio) y el rol del usuario. Este modelo no autoriza.
     */
    public function publicar(int $periodoId, int $nivelId, string $publicaEn, int $usuarioId): bool
    {
        return $this->execute("
            INSERT INTO periodos_publicacion
                (periodo_id, nivel_id, publica_en, publicado_por)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                publica_en            = VALUES(publica_en),
                publicado_por         = VALUES(publicado_por),
                suspendida_en         = NULL,
                despublicada_en       = NULL,
                despublicada_por      = NULL,
                motivo_despublicacion = NULL
        ", [$periodoId, $nivelId, $publicaEn, $usuarioId]);
    }

    /**
     * Despublicacion MANUAL: acto DEFINITIVO con motivo auditado. La fila
     * no se borra (se perderia la traza de quien y por que); se marca. A
     * diferencia de la suspension por reapertura, volver a cerrar el
     * bimestre NO la revive: solo publicar de nuevo a mano.
     */
    public function despublicar(int $periodoId, int $nivelId, string $motivo, int $usuarioId, ?string $ahora = null): bool
    {
        return $this->execute("
            UPDATE periodos_publicacion
            SET despublicada_en       = ?,
                despublicada_por      = ?,
                motivo_despublicacion = ?
            WHERE periodo_id      = ?
              AND nivel_id        = ?
              AND despublicada_en IS NULL
        ", [$ahora ?? $this->ahora(), $usuarioId, $motivo, $periodoId, $nivelId]);
    }

    /**
     * REABRIR un bimestre suspende su publicacion en TODOS los niveles.
     * Es REVERSIBLE a proposito: volver a cerrarlo restaura exactamente la
     * publicacion previa (ver restaurarPorCierre). No toca lo despublicado
     * a mano, que ya esta oculto por su propia marca.
     * Lo llama PeriodoController::reabrir dentro de su transaccion.
     */
    public function suspenderPorReapertura(int $periodoId, ?string $ahora = null): bool
    {
        return $this->execute("
            UPDATE periodos_publicacion
            SET suspendida_en = ?
            WHERE periodo_id    = ?
              AND suspendida_en IS NULL
        ", [$ahora ?? $this->ahora(), $periodoId]);
    }

    /**
     * Volver a CERRAR un bimestre restaura la publicacion que la reapertura
     * habia suspendido. NO publica nada nuevo (cerrar nunca publica): solo
     * limpia la suspension de las filas que ya existian. Lo despublicado a
     * mano sigue oculto (su marca es independiente).
     * Lo llama PeriodoController::cerrar dentro de su transaccion.
     */
    public function restaurarPorCierre(int $periodoId): bool
    {
        return $this->execute("
            UPDATE periodos_publicacion
            SET suspendida_en = NULL
            WHERE periodo_id = ?
        ", [$periodoId]);
    }
}
