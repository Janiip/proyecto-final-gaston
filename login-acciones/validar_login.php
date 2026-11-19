<?php
/**
 * controladores/validar_login.php
 * ------------------------------------------------------------
 * Recibe el formulario del login y decide:
 *   - si los datos están mal  -> vuelve al login con un error
 *   - si están bien           -> guarda la sesión y redirige según el rol
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/validaciones.php';
iniciar_sesion();

/**
 * Vuelve al login mostrando un mensaje de error.
 */
function volver_con_error($mensaje, $identificador = '')
{
    $_SESSION['error_login'] = $mensaje;
    $_SESSION['identificador_previo'] = $identificador;
    header('Location: ../vistas/login.php');
    exit;
}

// ---------------------------------------------------------
// PASO 1: este archivo solo acepta formularios enviados por POST.
// Si alguien lo abre escribiendo la dirección, lo mandamos al login.
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/login.php');
    exit;
}

// ---------------------------------------------------------
// PASO 2: leemos los datos del formulario.
// trim() saca los espacios que se escapan al principio o al final.
// ---------------------------------------------------------
$identificador = isset($_POST['identificador']) ? trim($_POST['identificador']) : '';
$password      = isset($_POST['password']) ? $_POST['password'] : '';

// ---------------------------------------------------------
// PASO 3: validamos antes de tocar la base de datos.
// ---------------------------------------------------------
if ($identificador === '' || $password === '') {
    volver_con_error('Completá el DNI o correo y la contraseña.', $identificador);
}

// ¿Escribió un correo o un DNI? (la función está en config/validaciones.php)
// Devuelve 'email', 'dni' o null. Ese valor es justo el nombre de la columna.
$columna = tipo_identificador($identificador);

if ($columna === null) {
    volver_con_error('Escribí tu DNI (solo números) o un correo válido.', $identificador);
}

// ---------------------------------------------------------
// PASO 4: buscamos al usuario con una consulta PREPARADA.
// El "?" es un lugar vacío: el dato se manda aparte en execute(),
// así lo que escriba el usuario nunca se mezcla con el SQL.
// ($columna no viene del usuario: la elegimos nosotras arriba,
//  solo puede ser 'email' o 'dni'.)
// ---------------------------------------------------------
$pdo = conectar();

$sql = "SELECT id, nombre, apellido, curso, password, rol,
               correo_verificado, password_cambiada
          FROM usuarios
         WHERE $columna = ? AND activo = 1
         LIMIT 1";

$consulta = $pdo->prepare($sql);
$consulta->execute([$identificador]);
$usuario = $consulta->fetch(); // un array con los datos, o false si no existe

// ---------------------------------------------------------
// PASO 5: comprobamos la contraseña.
// password_verify() compara lo que escribió con el hash guardado
// (en la base nunca está la contraseña real, solo el hash).
// Damos el MISMO mensaje si no existe el usuario o si la contraseña
// está mal, para no darle pistas a alguien que esté probando datos.
// ---------------------------------------------------------
if (!$usuario || !password_verify($password, $usuario['password'])) {
    volver_con_error('El usuario o la contraseña son incorrectos.', $identificador);
}

// ---------------------------------------------------------
// PASO 6: login correcto. Guardamos los datos en la sesión.
// session_regenerate_id(true) cambia el número de sesión:
// así nadie puede "reusar" una sesión vieja (robo de sesión).
// Nunca guardamos la contraseña en la sesión.
// ---------------------------------------------------------
session_regenerate_id(true);

$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nombre']     = $usuario['nombre'];
$_SESSION['apellido']   = $usuario['apellido'];
$_SESSION['curso']      = $usuario['curso'];
$_SESSION['rol']        = $usuario['rol'];
// El primer ingreso está completo cuando verificó el correo Y cambió la contraseña.
$_SESSION['primer_ingreso_completo'] =
    ((int) $usuario['correo_verificado'] === 1 && (int) $usuario['password_cambiada'] === 1);

// ---------------------------------------------------------
// PASO 7: redirigimos según el rol.
// ---------------------------------------------------------
if ($usuario['rol'] === 'panolero') {
    header('Location: ../vistas/panel_panolero.php');
} elseif (!$_SESSION['primer_ingreso_completo']) {
    // Alumno que todavía no verificó el correo o no cambió la 1234.
    header('Location: ../vistas/primer_ingreso.php');
} else {
    header('Location: ../vistas/panel_alumno.php');
}
exit;
