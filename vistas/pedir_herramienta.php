<?php
/**
 * vistas/pedir_herramienta.php
 * ------------------------------------------------------------
 * Formulario para pedir una herramienta (mockup: pedir herramienta).
 * El formulario lo procesa login-acciones/guardar_solicitud.php.
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';
exigir_rol('alumno');

$pdo = conectar();

// Solo mostramos herramientas activas que tengan unidades disponibles
$herramientas = $pdo->query(
    "SELECT id, nombre, cantidad_disponible
       FROM herramientas
      WHERE activa = 1 AND cantidad_disponible > 0
      ORDER BY nombre"
)->fetchAll();

// Bloqueo por deuda (RF14): ¿tiene algún préstamo vencido?
$tiene_deuda = alumno_tiene_deuda($pdo, $_SESSION['usuario_id']);

$fuera_de_horario = fuera_de_horario();

// Mensajes que deja el controlador al volver (éxito o error)
$error = isset($_SESSION['error_form']) ? $_SESSION['error_form'] : '';
$exito = isset($_SESSION['exito_form']) ? $_SESSION['exito_form'] : '';
unset($_SESSION['error_form'], $_SESSION['exito_form']);

$titulo = 'Pedir herramienta';
$pagina_activa = 'pedir_herramienta.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Solicitudes</p>
        <h1>Pedir herramienta</h1>
        <p>Completá los datos para enviar tu solicitud al pañolero.</p>
    </div>
</div>

<section class="tarjeta formulario">

    <?php if ($error): ?><div class="mensaje mensaje-error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($exito): ?><div class="mensaje mensaje-exito"><?= e($exito) ?></div><?php endif; ?>

    <?php if ($tiene_deuda): ?>
        <!-- Bloqueado: no mostramos el formulario -->
        <div class="mensaje mensaje-error">
            Tenés una herramienta vencida. Devolvela para poder pedir otra.
        </div>
    <?php else: ?>

        <div class="info">
            La solicitud quedará pendiente hasta que el pañolero confirme la disponibilidad.
        </div>

        <?php if ($fuera_de_horario): ?>
            <div class="mensaje mensaje-aviso">
                Estás pidiendo fuera del horario del pañol (<?= HORA_APERTURA ?> a <?= HORA_CIERRE ?>, de lunes a viernes).
            </div>
        <?php endif; ?>

        <form action="../login-acciones/guardar_solicitud.php" method="POST">

            <div class="campo">
                <label for="herramienta">Herramienta</label>
                <select id="herramienta" name="id_herramienta" required>
                    <option value="">Seleccioná una herramienta</option>
                    <?php foreach ($herramientas as $h): ?>
                        <option value="<?= (int) $h['id'] ?>">
                            <?= e($h['nombre']) ?> (<?= (int) $h['cantidad_disponible'] ?> disponibles)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo campo-corto">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
            </div>

            <div class="campo campo-corto">
                <label for="fecha">Fecha de devolución</label>
                <!-- min = hoy: el navegador no deja elegir un día que ya pasó -->
                <input type="date" id="fecha" name="fecha_devolucion_prevista"
                       min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
            </div>

            <?php if ($fuera_de_horario): ?>
                <!-- Solo aparece fuera de horario y es obligatorio (RF5) -->
                <div class="campo">
                    <label for="justificacion">Justificación: ¿por qué y para qué la necesitás?</label>
                    <textarea id="justificacion" name="justificacion" rows="3" required
                              placeholder="Contanos brevemente para qué la necesitás"></textarea>
                </div>
            <?php endif; ?>

            <button type="submit" class="boton boton-principal boton-ancho">Enviar solicitud</button>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
