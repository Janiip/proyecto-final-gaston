<?php
/**
 * login-acciones/guardar_solicitud.php
 * ------------------------------------------------------------
 * Recibe el formulario de vistas/pedir_herramienta.php y guarda
 * la solicitud como "solicitado".
 *
 * Reglas que controla:
 *   - Solo un alumno con sesión puede pedir.
 *   - Si tiene deuda (préstamo vencido), no puede pedir (RF14).
 *   - La herramienta existe, está activa y alcanza el stock.
 *   - La fecha de devolución no puede ser anterior a hoy.
 *   - Fuera de horario, la justificación es obligatoria (RF5).
 *
 * Importante: al PEDIR no se descuenta stock. Se descuenta recién
 * cuando el pañolero entrega la herramienta (aprobar_prestamo).
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';

exigir_rol('alumno');

/**
 * Vuelve al formulario con un mensaje de error.
 */
function volver_con_error($mensaje)
{
    $_SESSION['error_form'] = $mensaje;
    header('Location: ../vistas/pedir_herramienta.php');
    exit;
}

// ---------------------------------------------------------
// PASO 1: solo se aceptan formularios enviados por POST.
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/pedir_herramienta.php');
    exit;
}

$pdo        = conectar();
$id_usuario = $_SESSION['usuario_id'];

// ---------------------------------------------------------
// PASO 2: bloqueo por deuda. Va primero: si debe algo, no seguimos.
// ---------------------------------------------------------
if (alumno_tiene_deuda($pdo, $id_usuario)) {
    volver_con_error('Tenés una herramienta vencida. Devolvela para poder pedir otra.');
}

// ---------------------------------------------------------
// PASO 3: leemos y limpiamos los datos.
// (int) convierte a número entero: si mandan texto, queda 0.
// ---------------------------------------------------------
$id_herramienta = isset($_POST['id_herramienta']) ? (int) $_POST['id_herramienta'] : 0;
$cantidad       = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 0;
$fecha          = isset($_POST['fecha_devolucion_prevista']) ? trim($_POST['fecha_devolucion_prevista']) : '';
$justificacion  = isset($_POST['justificacion']) ? trim($_POST['justificacion']) : '';

// ---------------------------------------------------------
// PASO 4: validaciones de los datos.
// ---------------------------------------------------------
if ($id_herramienta <= 0) {
    volver_con_error('Elegí una herramienta.');
}

if ($cantidad < 1) {
    volver_con_error('La cantidad tiene que ser 1 o más.');
}

// ¿La fecha es real y tiene formato AAAA-MM-DD? (checkdate rechaza un 31 de febrero)
$partes = explode('-', $fecha);
if (count($partes) !== 3 || !checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])) {
    volver_con_error('La fecha de devolución no es válida.');
}

// Las fechas AAAA-MM-DD se pueden comparar como texto.
if ($fecha < hoy()) {
    volver_con_error('La fecha de devolución no puede ser anterior a hoy.');
}

// ---------------------------------------------------------
// PASO 5: horario. Fuera de horario, la justificación es obligatoria.
// Dentro del horario la ignoramos y guardamos NULL.
// ---------------------------------------------------------
if (fuera_de_horario()) {
    if (mb_strlen($justificacion, 'UTF-8') < 10) {
        volver_con_error('Estás pidiendo fuera de horario: explicá por qué y para qué la necesitás (al menos 10 letras).');
    }
} else {
    $justificacion = null;
}

// ---------------------------------------------------------
// PASO 6: la herramienta existe, está activa y alcanza el stock.
// (Es un control para avisar al alumno. El control definitivo
//  del stock se hace al entregar, en aprobar_prestamo.)
// ---------------------------------------------------------
$consulta = $pdo->prepare("SELECT nombre, cantidad_disponible FROM herramientas WHERE id = ? AND activa = 1");
$consulta->execute([$id_herramienta]);
$herramienta = $consulta->fetch();

if (!$herramienta) {
    volver_con_error('Esa herramienta no existe o fue dada de baja.');
}

if ($cantidad > (int) $herramienta['cantidad_disponible']) {
    volver_con_error('No hay suficientes unidades disponibles (quedan ' . (int) $herramienta['cantidad_disponible'] . ').');
}

// ---------------------------------------------------------
// PASO 7: guardamos la solicitud.
// La fecha y hora del pedido las pone PHP (hora de Argentina),
// no MySQL, para no depender del reloj del servidor de base de datos.
// ---------------------------------------------------------
$insertar = $pdo->prepare(
    "INSERT INTO prestamos
        (id_usuario, id_herramienta, cantidad, fecha_pedido, fecha_devolucion_prevista, estado, justificacion)
     VALUES (?, ?, ?, ?, ?, 'solicitado', ?)"
);
$insertar->execute([$id_usuario, $id_herramienta, $cantidad, date('Y-m-d H:i:s'), $fecha, $justificacion]);

$_SESSION['exito_form'] = 'Solicitud enviada: ' . $herramienta['nombre'] . '. Esperá a que el pañolero la apruebe.';
header('Location: ../vistas/pedir_herramienta.php');
exit;
