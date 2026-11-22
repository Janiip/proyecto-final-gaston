<?php
/**
 * vistas/panel_alumno.php
 * ------------------------------------------------------------
 * Inicio del alumno (mockup: panel alumno).
 * Muestra 3 tarjetas con números, sus últimos préstamos
 * y accesos rápidos.
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';
exigir_rol('alumno');

$pdo = conectar();
$id  = $_SESSION['usuario_id'];

/**
 * Cuenta los préstamos del alumno que cumplen una condición extra.
 * Todas las consultas son preparadas: el id va aparte en execute().
 */
function contar($pdo, $id, $condicion, $datos = [])
{
    $consulta = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE id_usuario = ? AND $condicion");
    $consulta->execute(array_merge([$id], $datos));
    return (int) $consulta->fetchColumn();
}

// Las 3 tarjetas de arriba
$activos     = contar($pdo, $id, "estado = 'retirado'");
$pendientes  = contar($pdo, $id, "estado = 'solicitado'");
// Próximas: retiradas que hay que devolver hoy, mañana o que ya se vencieron
$manana      = date('Y-m-d', strtotime('+1 day'));
$proximas    = contar($pdo, $id, "estado = 'retirado' AND fecha_devolucion_prevista <= ?", [$manana]);

// Sus últimos 5 préstamos (el JOIN trae el nombre de la herramienta)
$consulta = $pdo->prepare(
    "SELECT h.nombre AS herramienta, p.fecha_pedido, p.estado, p.fecha_devolucion_prevista
       FROM prestamos p
       JOIN herramientas h ON h.id = p.id_herramienta
      WHERE p.id_usuario = ?
      ORDER BY p.fecha_pedido DESC
      LIMIT 5"
);
$consulta->execute([$id]);
$ultimos = $consulta->fetchAll();

// Alerta de cierre (RF11): lo que este alumno tiene sin devolver (desde las 16:30)
$para_devolver = datos_alerta_cierre($pdo, $id);

$titulo = 'Inicio';
$pagina_activa = 'panel_alumno.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Panel alumno</p>
        <h1>¡Hola, <?= e($_SESSION['nombre']) ?>!</h1>
        <p>Este es el resumen de tu actividad.</p>
    </div>
    <a href="pedir_herramienta.php" class="boton boton-principal">+ Pedir herramienta</a>
</div>

<?php if ($para_devolver): ?>
    <div class="aviso-cierre">
        <h2>El pañol cierra a las <?= HORA_CIERRE ?>. Tenés que devolver:</h2>
        <ul>
            <?php foreach ($para_devolver as $item): ?>
                <li><?= e($item['herramienta']) ?> (<?= (int) $item['cantidad'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Tarjetas con números -->
<div class="metricas">
    <div class="tarjeta metrica">
        <div class="metrica-titulo">Préstamos activos</div>
        <span class="metrica-icono">&#9636;</span>
        <div class="metrica-numero"><?= $activos ?></div>
        <div class="metrica-detalle">Herramientas en tu poder</div>
    </div>

    <div class="tarjeta metrica">
        <div class="metrica-titulo">Solicitudes pendientes</div>
        <span class="metrica-icono">&#9719;</span>
        <div class="metrica-numero"><?= $pendientes ?></div>
        <div class="metrica-detalle">Esperando aprobación</div>
    </div>

    <!-- La tarjeta coral ("alerta") solo se pinta si hay alguna devolución próxima -->
    <div class="tarjeta metrica <?= $proximas > 0 ? 'alerta' : '' ?>">
        <div class="metrica-titulo">Devoluciones próximas</div>
        <span class="metrica-icono">&#8617;</span>
        <div class="metrica-numero"><?= $proximas ?></div>
        <div class="metrica-detalle">Para hoy o mañana</div>
    </div>
</div>

<div class="dos-columnas">
    <!-- Últimos préstamos -->
    <section class="tarjeta">
        <h2>Mis últimos préstamos</h2>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr><th>Herramienta</th><th>Fecha</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php if (!$ultimos): ?>
                        <tr><td colspan="3" class="tabla-vacia">Todavía no pediste ninguna herramienta.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ultimos as $p): ?>
                        <tr>
                            <td><?= e($p['herramienta']) ?></td>
                            <td><?= fecha_corta($p['fecha_pedido']) ?></td>
                            <td><?= badge_estado($p['estado'], $p['fecha_devolucion_prevista']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p><a href="mis_prestamos.php" class="boton boton-suave">Ver todos mis préstamos &rarr;</a></p>
    </section>

    <!-- Accesos rápidos -->
    <section class="tarjeta">
        <h2>Accesos rápidos</h2>
        <div class="acciones">
            <a href="pedir_herramienta.php" class="boton boton-suave">+ Nueva solicitud</a>
            <a href="mis_prestamos.php" class="boton boton-secundario">Consultar préstamos</a>
        </div>
        <div class="info" style="margin-top: 22px;">
            Recordá devolver las herramientas antes de las <?= HORA_CIERRE ?>, así otros
            compañeros también pueden usarlas.
        </div>
    </section>
</div>

<?php require __DIR__ . '/fin_pagina.php'; ?>
