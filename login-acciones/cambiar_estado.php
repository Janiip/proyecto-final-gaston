<?php
/**
 * login-acciones/cambiar_estado.php
 * ------------------------------------------------------------
 * Recibe los botones de vistas/panel_panolero.php:
 *   accion = aprobar  -> entrega la herramienta (solicitado -> retirado)
 *   accion = rechazar -> rechaza la solicitud   (solicitado -> rechazado)
 *   accion = devolver -> registra la devolución (retirado   -> devuelto)
 *
 * Este archivo solo "recibe y deriva": las reglas están en
 * config/reglas_prestamos.php.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';

exigir_rol('panolero'); // solo el pañolero puede cambiar estados

// PASO 1: solo por POST (un link no puede aprobar nada por accidente).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/panel_panolero.php');
    exit;
}

// PASO 2: leemos qué préstamo y qué acción.
$id_prestamo = isset($_POST['id_prestamo']) ? (int) $_POST['id_prestamo'] : 0;
$accion      = isset($_POST['accion']) ? $_POST['accion'] : '';

// PASO 3: llamamos a la regla que corresponde.
$pdo = conectar();

if ($id_prestamo <= 0) {
    $resultado = ['ok' => false, 'mensaje' => 'Préstamo no válido.'];
} elseif ($accion === 'aprobar') {
    $resultado = aprobar_prestamo($pdo, $id_prestamo);
} elseif ($accion === 'rechazar') {
    $resultado = rechazar_prestamo($pdo, $id_prestamo);
} elseif ($accion === 'devolver') {
    $resultado = devolver_prestamo($pdo, $id_prestamo);
} else {
    $resultado = ['ok' => false, 'mensaje' => 'Acción no válida.'];
}

// PASO 4: guardamos el mensaje y volvemos al panel.
// El panel lo muestra en verde (éxito) o en rojo (error).
$_SESSION[$resultado['ok'] ? 'exito_form' : 'error_form'] = $resultado['mensaje'];
header('Location: ../vistas/panel_panolero.php');
exit;
