<?php
/**
 * vistas/mis_prestamos.php
 * ------------------------------------------------------------
 * Todos los préstamos del alumno (mockup: mis préstamos).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
exigir_rol('alumno');

$consulta = conectar()->prepare(
    "SELECT h.nombre AS herramienta, p.fecha_pedido, p.estado,
            p.fecha_devolucion_prevista, p.fecha_devolucion_real
       FROM prestamos p
       JOIN herramientas h ON h.id = p.id_herramienta
      WHERE p.id_usuario = ?
      ORDER BY p.fecha_pedido DESC"
);
$consulta->execute([$_SESSION['usuario_id']]);
$prestamos = $consulta->fetchAll();

$titulo = 'Mis préstamos';
$pagina_activa = 'mis_prestamos.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Mi actividad</p>
        <h1>Mis préstamos</h1>
        <p>Consultá el estado de tus solicitudes y herramientas.</p>
    </div>
    <a href="pedir_herramienta.php" class="boton boton-principal">+ Nueva solicitud</a>
</div>

<section class="tarjeta">
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr><th>Herramienta</th><th>Solicitud</th><th>Devolución</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php if (!$prestamos): ?>
                    <tr><td colspan="4" class="tabla-vacia">Todavía no tenés préstamos.</td></tr>
                <?php endif; ?>

                <?php foreach ($prestamos as $p): ?>
                    <?php
                    // Devolución: la real si ya devolvió; la prevista si la tiene en su poder;
                    // una raya si todavía no la retiró o se la rechazaron.
                    if ($p['estado'] === 'devuelto') {
                        $devolucion = fecha_corta($p['fecha_devolucion_real']);
                    } elseif ($p['estado'] === 'retirado') {
                        $devolucion = fecha_corta($p['fecha_devolucion_prevista']);
                    } else {
                        $devolucion = '—';
                    }
                    ?>
                    <tr>
                        <td><?= e($p['herramienta']) ?></td>
                        <td><?= fecha_corta($p['fecha_pedido']) ?></td>
                        <td><?= $devolucion ?></td>
                        <td><?= badge_estado($p['estado'], $p['fecha_devolucion_prevista']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
