<?php
/**
 * reportes.php
 * ------------------------------------------------------------
 * Consultas del tablero de control (Menú B – reportes y estadísticas).
 * Todo sale de COUNT y GROUP BY sobre las tablas que ya existen:
 * no hace falta ninguna tabla nueva.
 *
 *   - contadores_tablero()    -> solicitudes, retiradas y vencidas
 *   - herramientas_mas_pedidas() -> las 5 más solicitadas (mes o año)
 *   - alumnos_con_deuda()     -> quién tiene algo vencido y hace cuánto
 */

require_once __DIR__ . '/reglas_prestamos.php';

/**
 * Los tres números de las tarjetas.
 * COUNT(*) cuenta filas; SUM(CASE ...) cuenta solo las que cumplen
 * cada condición, así resolvemos los tres en UNA sola consulta.
 */
function contadores_tablero($pdo, $ahora = null)
{
    $consulta = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN estado = 'solicitado' THEN 1 ELSE 0 END) AS solicitudes,
            SUM(CASE WHEN estado = 'retirado'   THEN 1 ELSE 0 END) AS retiradas,
            SUM(CASE WHEN estado = 'retirado' AND fecha_devolucion_prevista < ? THEN 1 ELSE 0 END) AS vencidas
         FROM prestamos"
    );
    // La fecha límite es la misma regla de vencimiento de todo el sistema.
    $consulta->execute([fecha_limite_vencimiento($ahora)]);
    $fila = $consulta->fetch();

    // (int) porque si no hay préstamos, SUM devuelve NULL.
    return [
        'solicitudes' => (int) $fila['solicitudes'],
        'retiradas'   => (int) $fila['retiradas'],
        'vencidas'    => (int) $fila['vencidas'],
    ];
}

/**
 * Desde qué fecha y hasta qué fecha (sin incluirla) mirar:
 *   'mes'  -> del 1 de este mes al 1 del mes que viene
 *   'anio' -> del 1 de enero al 1 de enero del año que viene
 */
function rango_periodo($periodo, $ahora = null)
{
    $ahora = momento($ahora);
    if ($periodo === 'anio') {
        return [date('Y', $ahora) . '-01-01', (date('Y', $ahora) + 1) . '-01-01'];
    }
    return [date('Y-m-01', $ahora), date('Y-m-01', strtotime('first day of next month', $ahora))];
}

/**
 * Las 5 herramientas más solicitadas en el período.
 * Cuenta todas las solicitudes (también las rechazadas): miden cuánto
 * se PIDE cada herramienta, que es lo que sirve para decidir qué comprar.
 *
 * @param string $periodo 'mes' o 'anio'
 * @return array [['nombre' => 'Taladro', 'veces' => 7], ...]
 */
function herramientas_mas_pedidas($pdo, $periodo = 'mes', $ahora = null)
{
    list($desde, $hasta) = rango_periodo($periodo, $ahora);

    $consulta = $pdo->prepare(
        "SELECT h.nombre, COUNT(*) AS veces
           FROM prestamos p
           JOIN herramientas h ON h.id = p.id_herramienta
          WHERE p.fecha_pedido >= ? AND p.fecha_pedido < ?
          GROUP BY h.id, h.nombre
          ORDER BY veces DESC, h.nombre
          LIMIT 5"
    );
    $consulta->execute([$desde, $hasta]);
    return $consulta->fetchAll();
}

/**
 * Alumnos con préstamos vencidos, con los días de atraso.
 * Los más atrasados primero.
 */
function alumnos_con_deuda($pdo, $ahora = null)
{
    $consulta = $pdo->prepare(
        "SELECT u.nombre, u.apellido, u.curso, h.nombre AS herramienta,
                p.cantidad, p.fecha_devolucion_prevista
           FROM prestamos p
           JOIN usuarios u     ON u.id = p.id_usuario
           JOIN herramientas h ON h.id = p.id_herramienta
          WHERE p.estado = 'retirado' AND p.fecha_devolucion_prevista < ?
          ORDER BY p.fecha_devolucion_prevista, u.apellido"
    );
    $consulta->execute([fecha_limite_vencimiento($ahora)]);
    $filas = $consulta->fetchAll();

    // Días de atraso = hoy - fecha de devolución (86400 segundos = 1 día).
    // Si da 0, venció hoy a las 17:30.
    foreach ($filas as $i => $fila) {
        $filas[$i]['dias_atraso'] = (int) round(
            (strtotime(hoy($ahora)) - strtotime($fila['fecha_devolucion_prevista'])) / 86400
        );
    }
    return $filas;
}
