<?php
/**
 * vistas/registrar_prestamo.php
 * ------------------------------------------------------------
 * El pañolero registra un préstamo directo: el alumno vino al pañol
 * sin haber hecho la solicitud. Se guarda directamente como "retirado".
 * Lo procesa login-acciones/guardar_prestamo_directo.php.
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';
exigir_rol('panolero');

$pdo = conectar();

// Alumnos activos, ordenados por apellido
$alumnos = $pdo->query(
    "SELECT id, nombre, apellido, curso FROM usuarios
      WHERE rol = 'alumno' AND activo = 1
      ORDER BY apellido, nombre"
)->fetchAll();

// Quiénes tienen deuda: los marcamos y no se pueden elegir (RF14)
$consulta = $pdo->prepare(
    "SELECT DISTINCT id_usuario FROM prestamos
      WHERE estado = 'retirado' AND fecha_devolucion_prevista < ?"
);
$consulta->execute([fecha_limite_vencimiento()]);
$con_deuda = $consulta->fetchAll(PDO::FETCH_COLUMN);

// Herramientas activas con unidades disponibles
$herramientas = $pdo->query(
    "SELECT id, nombre, cantidad_disponible FROM herramientas
      WHERE activa = 1 AND cantidad_disponible > 0
      ORDER BY nombre"
)->fetchAll();

$fuera_de_horario = fuera_de_horario();

// Lo que había escrito, si volvimos por un error
$previo = ['id_usuario' => 0, 'id_herramienta' => 0, 'cantidad' => 1,
           'fecha_devolucion_prevista' => hoy(), 'justificacion' => ''];
if (isset($_SESSION['form_previo'])) {
    $previo = array_merge($previo, $_SESSION['form_previo']);
    unset($_SESSION['form_previo']);
}
$error = isset($_SESSION['error_form']) ? $_SESSION['error_form'] : '';
unset($_SESSION['error_form']);

$titulo = 'Registrar préstamo';
$pagina_activa = 'panel_panolero.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Panel pañolero</p>
        <h1>Registrar préstamo</h1>
        <p>Entregá una herramienta a un alumno que no hizo la solicitud.</p>
    </div>
    <a href="panel_panolero.php" class="boton boton-secundario">&larr; Volver</a>
</div>

<section class="tarjeta formulario">

    <?php if ($error): ?><div class="mensaje mensaje-error"><?= e($error) ?></div><?php endif; ?>

    <div class="info">
        El préstamo se guarda como <b>retirado</b> y se descuenta el stock en el momento.
        Valen las mismas reglas que cuando pide el alumno.
    </div>

    <?php if ($fuera_de_horario): ?>
        <div class="mensaje mensaje-aviso">
            Estás fuera del horario del pañol (<?= HORA_APERTURA ?> a <?= HORA_CIERRE ?>, de lunes a viernes): la justificación es obligatoria.
        </div>
    <?php endif; ?>

    <form action="../login-acciones/guardar_prestamo_directo.php" method="POST">

        <div class="campo">
            <label for="alumno">Alumno</label>
            <select id="alumno" name="id_usuario" required>
                <option value="">Elegí un alumno</option>
                <?php foreach ($alumnos as $a): ?>
                    <?php $debe = in_array($a['id'], $con_deuda); ?>
                    <!-- Un alumno con deuda aparece, pero no se puede elegir -->
                    <option value="<?= (int) $a['id'] ?>" <?= $debe ? 'disabled' : '' ?>
                            <?= (int) $previo['id_usuario'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['apellido'] . ', ' . $a['nombre'] . ' — ' . $a['curso']) ?><?= $debe ? ' (tiene una herramienta vencida)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="herramienta">Herramienta</label>
            <select id="herramienta" name="id_herramienta" required>
                <option value="">Elegí una herramienta</option>
                <?php foreach ($herramientas as $h): ?>
                    <option value="<?= (int) $h['id'] ?>" <?= (int) $previo['id_herramienta'] === (int) $h['id'] ? 'selected' : '' ?>>
                        <?= e($h['nombre']) ?> (<?= (int) $h['cantidad_disponible'] ?> disponibles)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo campo-corto">
            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" min="1" required value="<?= (int) $previo['cantidad'] ?>">
        </div>

        <div class="campo campo-corto">
            <label for="fecha">Fecha de devolución</label>
            <input type="date" id="fecha" name="fecha_devolucion_prevista" min="<?= hoy() ?>" required
                   value="<?= e($previo['fecha_devolucion_prevista']) ?>">
        </div>

        <?php if ($fuera_de_horario): ?>
            <div class="campo">
                <label for="justificacion">Justificación: ¿por qué y para qué se entrega fuera de horario?</label>
                <textarea id="justificacion" name="justificacion" rows="3" required><?= e($previo['justificacion']) ?></textarea>
            </div>
        <?php endif; ?>

        <button type="submit" class="boton boton-principal boton-ancho">Registrar y entregar</button>
    </form>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
