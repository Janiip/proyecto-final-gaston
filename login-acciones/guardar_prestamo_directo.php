<?php
/**
 * login-acciones/guardar_prestamo_directo.php
 * ------------------------------------------------------------
 * Recibe vistas/registrar_prestamo.php. El pañolero entrega una
 * herramienta sin solicitud previa:
 *   1. El alumno tiene que existir y estar activo.
 *   2. validar_pedido(): deuda, datos, fecha, horario y stock
 *      (las MISMAS reglas que cuando pide el alumno).
 *   3. registrar_prestamo_directo(): lo guarda como "retirado"
 *      y descuenta el stock en una transacción.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';

exigir_rol('panolero');

function volver_con_error($mensaje)
{
    $_SESSION['error_form']  = $mensaje;
    $_SESSION['form_previo'] = [];
    foreach (['id_usuario', 'id_herramienta', 'cantidad', 'fecha_devolucion_prevista', 'justificacion'] as $campo) {
        $_SESSION['form_previo'][$campo] = isset($_POST[$campo]) ? $_POST[$campo] : '';
    }
    header('Location: ../vistas/registrar_prestamo.php');
    exit;
}

// PASO 1: solo por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/registrar_prestamo.php');
    exit;
}

$pdo        = conectar();
$id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;

// PASO 2: el alumno existe y está activo.
$consulta = $pdo->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ? AND rol = 'alumno' AND activo = 1");
$consulta->execute([$id_usuario]);
$alumno = $consulta->fetch();

if (!$alumno) {
    volver_con_error('Elegí un alumno activo.');
}

// PASO 3: las reglas del pedido (las mismas que para el alumno).
$validacion = validar_pedido($pdo, $id_usuario, [
    'id_herramienta'            => isset($_POST['id_herramienta']) ? $_POST['id_herramienta'] : 0,
    'cantidad'                  => isset($_POST['cantidad']) ? $_POST['cantidad'] : 0,
    'fecha_devolucion_prevista' => isset($_POST['fecha_devolucion_prevista']) ? $_POST['fecha_devolucion_prevista'] : '',
    'justificacion'             => isset($_POST['justificacion']) ? $_POST['justificacion'] : '',
]);

if ($validacion['error'] !== null) {
    volver_con_error($validacion['error']);
}

// PASO 4: lo guardamos como "retirado" y descontamos el stock.
$resultado = registrar_prestamo_directo(
    $pdo,
    $id_usuario,
    (int) $validacion['herramienta']['id'],
    (int) $_POST['cantidad'],
    trim($_POST['fecha_devolucion_prevista']),
    $validacion['justificacion']
);

if (!$resultado['ok']) {
    volver_con_error($resultado['mensaje']);
}

$_SESSION['exito_form'] = $resultado['mensaje'] . ' (' . $validacion['herramienta']['nombre']
                        . ' para ' . $alumno['nombre'] . ' ' . $alumno['apellido'] . ')';
header('Location: ../vistas/panel_panolero.php');
exit;
