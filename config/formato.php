<?php
/**
 * formato.php
 * ------------------------------------------------------------
 * Funciones chiquitas para mostrar datos lindos en pantalla:
 * fechas, iniciales y las "píldoras" (badges) de estado.
 */

require_once __DIR__ . '/sesion.php';           // para usar e()
require_once __DIR__ . '/reglas_prestamos.php'; // para saber si está vencido

/**
 * Pasa una fecha de la base (2026-11-17) al formato argentino (17/11/2026).
 * Si no hay fecha, devuelve una raya.
 */
function fecha_corta($fecha)
{
    if (empty($fecha)) {
        return '—';
    }
    return date('d/m/Y', strtotime($fecha));
}

/**
 * Devuelve las iniciales para el círculo del avatar: "Alex López" -> "AL".
 * mb_substr y mb_strtoupper respetan las tildes y la ñ.
 */
function iniciales($nombre, $apellido)
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1), 'UTF-8');
}

/**
 * Arma la píldora de color según el estado del préstamo.
 * Un préstamo "retirado" que pasó el cierre de su día de devolución
 * se muestra como "Vencido" (la regla está en reglas_prestamos.php).
 *
 * @param string $estado         solicitado | retirado | devuelto | rechazado
 * @param string $fecha_prevista fecha_devolucion_prevista (Y-m-d)
 */
function badge_estado($estado, $fecha_prevista = null)
{
    if (esta_vencido($estado, $fecha_prevista)) {
        return '<span class="badge badge-vencido">Vencido</span>';
    }

    // estado de la base => [clase CSS, texto que ve el usuario]
    $estados = [
        'solicitado' => ['badge-pendiente', 'Pendiente'],
        'retirado'   => ['badge-en-curso', 'En curso'],
        'devuelto'   => ['badge-devuelto', 'Devuelto'],
        'rechazado'  => ['badge-rechazado', 'Rechazado'],
    ];

    if (!isset($estados[$estado])) {
        return e($estado);
    }

    list($clase, $texto) = $estados[$estado];
    return '<span class="badge ' . $clase . '">' . $texto . '</span>';
}
