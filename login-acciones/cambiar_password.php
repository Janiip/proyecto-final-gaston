<?php
/**
 * login-acciones/cambiar_password.php
 * ------------------------------------------------------------
 * Paso 2 del primer ingreso (RF2): cambiar la contraseña 1234
 * por una propia de 4 números. Solo se puede con el correo verificado.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
iniciar_sesion();

function volver_con_error($mensaje)
{
    $_SESSION['error_form'] = $mensaje;
    header('Location: ../vistas/primer_ingreso.php');
    exit;
}

// PASO 1: alumno con sesión, por POST.
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: ../vistas/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/primer_ingreso.php');
    exit;
}

$pdo = conectar();
$id  = $_SESSION['usuario_id'];

// PASO 2: el correo tiene que estar verificado. Lo miramos en la BASE,
// no solo en la sesión: es la regla "primero el correo, después la contraseña".
$consulta = $pdo->prepare("SELECT correo_verificado FROM usuarios WHERE id = ?");
$consulta->execute([$id]);
if ((int) $consulta->fetchColumn() !== 1) {
    volver_con_error('Primero tenés que verificar tu correo.');
}

// PASO 3: validamos la contraseña nueva.
$nueva   = isset($_POST['nueva']) ? $_POST['nueva'] : '';
$repetir = isset($_POST['repetir']) ? $_POST['repetir'] : '';

if (!preg_match('/^[0-9]{4}$/', $nueva)) {
    volver_con_error('La contraseña tiene que tener exactamente 4 números.');
}
if ($nueva === '1234') {
    volver_con_error('La contraseña no puede ser 1234. Elegí otra.');
}
if ($nueva !== $repetir) {
    volver_con_error('Las dos contraseñas no coinciden.');
}

// PASO 4: guardamos la contraseña encriptada y marcamos el primer ingreso como hecho.
$guardar = $pdo->prepare("UPDATE usuarios SET password = ?, password_cambiada = 1 WHERE id = ?");
$guardar->execute([password_hash($nueva, PASSWORD_DEFAULT), $id]);

// Nuevo número de sesión (por seguridad, porque cambió la contraseña).
session_regenerate_id(true);
$_SESSION['primer_ingreso_completo'] = true;
$_SESSION['exito_form'] = '¡Listo! Ya podés usar el pañol.';

header('Location: ../vistas/panel_alumno.php');
exit;
