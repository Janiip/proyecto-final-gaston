<?php
/**
 * vistas/panel_panolero.php
 * ------------------------------------------------------------
 * Gestión de préstamos (mockup: panel pañolero).
 * Tarjetas con números, alerta de cierre y solicitudes recientes.
 * Los botones Aprobar / Rechazar / Registrar devolución mandan
 * el pedido a controladores/cambiar_estado.php (parte de Jana).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/horario.php';
exigir_rol('panolero');

$pdo = conectar();

// Tarjetas: acá no hay datos del usuario, así que alcanza con query()
$pendientes = (int) $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'solicitado'")->fetchColumn();
$activos    = (int) $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'retirado'")->fetchColumn();
$hoy        = (int) $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'retirado' AND fecha_devolucion_prevista = CURDATE()")->fetchColumn();

// Solicitudes y préstamos que todavía necesitan algo del pañolero
$solicitudes = $pdo->query(
    "SELECT p.id, u.nombre, u.apellido, h.nombre AS herramienta, p.cantidad,
            p.fecha_pedido, p.estado, p.fecha_devolucion_prevista, p.justificacion
       FROM prestamos p
       JOIN usuarios u     ON u.id = p.id_usuario
       JOIN herramientas h ON h.id = p.id_herramienta
      WHERE p.estado IN ('solicitado', 'retirado')
      ORDER BY p.fecha_pedido DESC"
)->fetchAll();

// Alerta de cierre (RF11): todo lo retirado, junto
$para_devolver = [];
if (alerta_de_cierre_activa()) {
    foreach ($solicitudes as $s) {
        if ($s['estado'] === 'retirado') {
            $para_devolver[] = $s;
        }
    }
}

$titulo = 'Gestión de préstamos';
$pagina_activa = 'panel_panolero.php';
require __DIR__ . '/partes/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Panel pañolero</p>
        <h1>Gestión de préstamos</h1>
        <p>Administrá solicitudes, retiros y devoluciones.</p>
    </div>
    <a href="registrar_prestamo.php" class="boton boton-principal">+ Registrar préstamo</a>
</div>

<?php if ($para_devolver): ?>
    <div class="aviso-cierre">
        <h2>Faltan devolver antes del cierre (<?= HORA_CIERRE ?>)</h2>
        <ul>
            <?php foreach ($para_devolver as $s): ?>
                <li><?= e($s['herramienta']) ?> (<?= (int) $s['cantidad'] ?>) — <?= e($s['nombre'] . ' ' . $s['apellido']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="metricas">
    <div class="tarjeta metrica">
        <div class="metrica-titulo">Solicitudes pendientes</div>
        <span class="metrica-icono">&#9719;</span>
        <div class="metrica-numero"><?= $pendientes ?></div>
        <div class="metrica-detalle">Requieren revisión</div>
    </div>
    <div class="tarjeta metrica">
        <div class="metrica-titulo">Préstamos activos</div>
        <span class="metrica-icono">&#9636;</span>
        <div class="metrica-numero"><?= $activos ?></div>
        <div class="metrica-detalle">En circulación</div>
    </div>
    <div class="tarjeta metrica">
        <div class="metrica-titulo">Devoluciones hoy</div>
        <span class="metrica-icono">&#8617;</span>
        <div class="metrica-numero"><?= $hoy ?></div>
        <div class="metrica-detalle">Para controlar</div>
    </div>
</div>

<section class="tarjeta">
    <h2>Solicitudes recientes</h2>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr><th>Alumno</th><th>Herramienta</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php if (!$solicitudes): ?>
                    <tr><td colspan="5" class="tabla-vacia">No hay solicitudes ni préstamos activos.</td></tr>
                <?php endif; ?>

                <?php foreach ($solicitudes as $s): ?>
                    <?php $vencido = $s['estado'] === 'retirado' && $s['fecha_devolucion_prevista'] < date('Y-m-d'); ?>
                    <tr class="<?= $vencido ? 'fila-vencida' : '' ?>">
                        <td><?= e($s['nombre'] . ' ' . $s['apellido']) ?></td>
                        <td><?= e($s['herramienta']) ?></td>
                        <td><?= fecha_corta($s['fecha_pedido']) ?></td>
                        <td>
                            <?= badge_estado($s['estado'], $s['fecha_devolucion_prevista']) ?>
                            <?php if ($s['justificacion'] !== null): ?>
                                <!-- title: al pasar el mouse se lee la justificación -->
                                <span class="badge badge-fuera-horario" title="<?= e($s['justificacion']) ?>">Fuera de horario</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Cada botón es un formulario chiquito que manda el id y la acción -->
                            <form action="../controladores/cambiar_estado.php" method="POST" class="acciones">
                                <input type="hidden" name="id_prestamo" value="<?= (int) $s['id'] ?>">
                                <?php if ($s['estado'] === 'solicitado'): ?>
                                    <button name="accion" value="aprobar" class="boton boton-principal boton-chico">Aprobar</button>
                                    <button name="accion" value="rechazar" class="boton boton-secundario boton-chico">Rechazar</button>
                                <?php else: ?>
                                    <button name="accion" value="devolver" class="boton boton-suave boton-chico">Registrar devolución</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/partes/fin_pagina.php'; ?>
