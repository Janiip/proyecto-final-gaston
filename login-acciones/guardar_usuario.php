<?php
/**
 * login-acciones/guardar_usuario.php
 * ------------------------------------------------------------
 * Recibe vistas/usuario_formulario.php (RF13). Según "accion":
 *   guardar     -> da de alta un alumno (id = 0) o edita un usuario (id > 0)
 *   restablecer -> contraseña de nuevo en 1234 y primer ingreso pendiente
 *
 * Los usuarios nunca se borran: se desactivan (activo = 0),
 * así sus préstamos quedan en el historial.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/validaciones.php';

exigir_rol('panolero');

// Contraseña con la que arranca todo alumno nuevo (y la de "restablecer").
define('PASSWORD_INICIAL', '1234');

function volver_al_formulario($id, $mensaje)
{
    $_SESSION['error_form']  = $mensaje;
    $_SESSION['form_previo'] = [];
    foreach (['nombre', 'apellido', 'dni', 'email', 'curso'] as $campo) {
        $_SESSION['form_previo'][$campo] = isset($_POST[$campo]) ? $_POST[$campo] : '';
    }
    header('Location: ../vistas/usuario_formulario.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}

/**
 * Vuelve a la lista con un mensaje: verde si salió bien, rojo si es un error.
 */
function volver_a_la_lista($mensaje, $tipo = 'exito')
{
    $_SESSION[$tipo === 'error' ? 'error_form' : 'exito_form'] = $mensaje;
    header('Location: ../vistas/usuarios.php');
    exit;
}

/**
 * ¿Ya hay OTRO usuario con ese dato? (para DNI y correo, que son únicos)
 * "id <> ?" deja afuera al usuario que estamos editando.
 * $columna la elegimos nosotras ('dni' o 'email'), nunca el usuario.
 */
function dato_repetido($pdo, $columna, $valor, $id)
{
    $consulta = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE $columna = ? AND id <> ?");
    $consulta->execute([$valor, $id]);
    return (int) $consulta->fetchColumn() > 0;
}

// PASO 1: solo por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/usuarios.php');
    exit;
}

$pdo    = conectar();
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// ---------------------------------------------------------
// RESTABLECER CONTRASEÑA (solo alumnos)
// ---------------------------------------------------------
if ($accion === 'restablecer') {
    $cambio = $pdo->prepare(
        "UPDATE usuarios
            SET password = ?, correo_verificado = 0, password_cambiada = 0,
                codigo_verificacion = NULL, codigo_vence = NULL
          WHERE id = ? AND rol = 'alumno'"
    );
    $cambio->execute([password_hash(PASSWORD_INICIAL, PASSWORD_DEFAULT), $id]);

    if ($cambio->rowCount() === 0) {
        volver_a_la_lista('Solo se puede restablecer la contraseña de un alumno.', 'error');
    }
    volver_a_la_lista('Contraseña restablecida a ' . PASSWORD_INICIAL . '. El alumno tiene que repetir el primer ingreso.');
}

if ($accion !== 'guardar') {
    volver_a_la_lista('Acción no válida.', 'error');
}

// ---------------------------------------------------------
// GUARDAR: leemos y validamos los datos.
// ---------------------------------------------------------
$nombre   = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$dni      = isset($_POST['dni']) ? trim($_POST['dni']) : '';
$email    = isset($_POST['email']) ? mb_strtolower(trim($_POST['email']), 'UTF-8') : '';
$curso    = isset($_POST['curso']) ? trim($_POST['curso']) : '';

if ($nombre === '' || $apellido === '' || mb_strlen($nombre, 'UTF-8') > 60 || mb_strlen($apellido, 'UTF-8') > 60) {
    volver_al_formulario($id, 'Completá el nombre y el apellido (hasta 60 letras cada uno).');
}
if (!es_dni_valido($dni)) {
    volver_al_formulario($id, 'El DNI tiene que tener entre 7 y 10 números, sin puntos.');
}
if (!es_email_valido($email) || mb_strlen($email, 'UTF-8') > 100) {
    volver_al_formulario($id, 'El correo no es válido.');
}
if (dato_repetido($pdo, 'dni', $dni, $id)) {
    volver_al_formulario($id, 'Ya hay otro usuario con ese DNI.');
}
if (dato_repetido($pdo, 'email', $email, $id)) {
    volver_al_formulario($id, 'Ya hay otro usuario con ese correo.');
}

if ($id === 0) {
    // ---------- ALTA DE ALUMNO ----------
    if ($curso === '' || mb_strlen($curso, 'UTF-8') > 10) {
        volver_al_formulario($id, 'Completá el curso (hasta 10 caracteres, por ejemplo 6° 1°).');
    }

    // Arranca con 1234 encriptada y el primer ingreso pendiente.
    $insertar = $pdo->prepare(
        "INSERT INTO usuarios
            (nombre, apellido, dni, email, curso, password, rol, activo, correo_verificado, password_cambiada)
         VALUES (?, ?, ?, ?, ?, ?, 'alumno', 1, 0, 0)"
    );
    $insertar->execute([$nombre, $apellido, $dni, $email, $curso,
                        password_hash(PASSWORD_INICIAL, PASSWORD_DEFAULT)]);

    volver_a_la_lista('Alumno agregado: ' . $nombre . ' ' . $apellido . '. Entra con su DNI y la contraseña ' . PASSWORD_INICIAL . '.');
}

// ---------- EDITAR ----------
$consulta = $pdo->prepare("SELECT rol, email FROM usuarios WHERE id = ?");
$consulta->execute([$id]);
$actual = $consulta->fetch();

if (!$actual) {
    volver_a_la_lista('Ese usuario no existe.', 'error');
}

if ($actual['rol'] === 'alumno' && ($curso === '' || mb_strlen($curso, 'UTF-8') > 10)) {
    volver_al_formulario($id, 'Completá el curso (hasta 10 caracteres).');
}

// El pañolero no puede desactivar su propia cuenta (se quedaría afuera).
$activo = ($id === (int) $_SESSION['usuario_id']) ? 1 : (isset($_POST['activo']) ? 1 : 0);

// Si a un ALUMNO le cambió el correo, el nuevo todavía no está verificado
// (el primer ingreso es solo para alumnos).
$correo_cambio = $email !== $actual['email'] && $actual['rol'] === 'alumno';

$actualizar = $pdo->prepare(
    "UPDATE usuarios
        SET nombre = ?, apellido = ?, dni = ?, email = ?, curso = ?, activo = ?
      WHERE id = ?"
);
$actualizar->execute([$nombre, $apellido, $dni, $email,
                      $actual['rol'] === 'alumno' ? $curso : null,
                      $activo, $id]);

// Correo nuevo = sin verificar. Al entrar, el alumno va a tener que
// verificarlo con un código (no tiene que volver a cambiar la contraseña).
if ($correo_cambio) {
    $pdo->prepare("UPDATE usuarios SET correo_verificado = 0 WHERE id = ?")->execute([$id]);
}

$mensaje = 'Cambios guardados: ' . $nombre . ' ' . $apellido . '.';
if ($correo_cambio) {
    $mensaje .= ' Como cambió el correo, la próxima vez que entre va a tener que verificarlo.';
}
volver_a_la_lista($mensaje);
