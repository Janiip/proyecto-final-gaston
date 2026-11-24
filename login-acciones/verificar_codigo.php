<?php
/**
 * login-acciones/verificar_codigo.php
 * ------------------------------------------------------------
 * Paso 1 del primer ingreso (RF2): verificar el correo.
 *
 *   - POST con "codigo"   -> comprueba el código de 6 números.
 *   - GET con ?reenviar=1 -> manda un código nuevo (Menú A).
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/avisos.php';
iniciar_sesion();

/**
 * Vuelve a la pantalla del primer ingreso con un mensaje.
 */
function volver($tipo, $mensaje)
{
    $_SESSION[$tipo === 'error' ? 'error_form' : 'exito_form'] = $mensaje;
    header('Location: ../vistas/primer_ingreso.php');
    exit;
}

// PASO 1: solo un alumno con sesión que todavía no verificó su correo.
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: ../vistas/login.php');
    exit;
}
if (!empty($_SESSION['correo_verificado'])) {
    header('Location: ../vistas/primer_ingreso.php'); // ya está en el paso 2
    exit;
}

$pdo = conectar();
$id  = $_SESSION['usuario_id'];

// PASO 2: ¿pidió un código nuevo?
if (isset($_GET['reenviar'])) {
    $envio = enviar_codigo_verificacion($pdo, $id);
    if ($envio['ok']) {
        volver('exito', 'Te mandamos un código nuevo. El anterior ya no sirve.');
    }
    volver('error', 'No pudimos mandar el código. Probá de nuevo en un rato o avisale al pañolero.');
}

// PASO 3: comprobamos el código que escribió.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/primer_ingreso.php');
    exit;
}

$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';

// Tienen que ser exactamente 6 números.
if (!preg_match('/^[0-9]{6}$/', $codigo)) {
    volver('error', 'El código tiene que tener 6 números.');
}

$consulta = $pdo->prepare("SELECT codigo_verificacion, codigo_vence FROM usuarios WHERE id = ?");
$consulta->execute([$id]);
$u = $consulta->fetch();

// ¿Hay un código pedido y todavía no venció?
if (!$u || empty($u['codigo_verificacion']) || $u['codigo_vence'] < date('Y-m-d H:i:s')) {
    volver('error', 'El código venció. Tocá "Reenviar código" para recibir uno nuevo.');
}

// El código está guardado encriptado: se compara con password_verify().
if (!password_verify($codigo, $u['codigo_verificacion'])) {
    volver('error', 'El código no es correcto. Revisá el mail y probá de nuevo.');
}

// PASO 4: correo verificado. Borramos el código para que no se pueda volver a usar.
$guardar = $pdo->prepare(
    "UPDATE usuarios
        SET correo_verificado = 1, codigo_verificacion = NULL, codigo_vence = NULL
      WHERE id = ?"
);
$guardar->execute([$id]);

$_SESSION['correo_verificado'] = true;

// Si ya había cambiado la contraseña antes (por ejemplo, el pañolero le
// cambió el correo), con verificar alcanza: entra directo a su panel.
$consulta = $pdo->prepare("SELECT password_cambiada FROM usuarios WHERE id = ?");
$consulta->execute([$id]);
if ((int) $consulta->fetchColumn() === 1) {
    $_SESSION['primer_ingreso_completo'] = true;
    $_SESSION['exito_form'] = '¡Correo verificado!';
    header('Location: ../vistas/panel_alumno.php');
    exit;
}

volver('exito', '¡Correo verificado! Ahora elegí tu contraseña nueva.');
