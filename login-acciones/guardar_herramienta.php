<?php
/**
 * login-acciones/guardar_herramienta.php
 * ------------------------------------------------------------
 * Recibe vistas/herramienta_formulario.php (RF7). Según "accion":
 *   guardar   -> agrega (id = 0) o edita (id > 0) una herramienta
 *   baja      -> activa = 0 (NUNCA se borra: se conserva el historial)
 *   reactivar -> activa = 1
 *
 * Regla del stock: cantidad_disponible = cantidad_total - unidades prestadas.
 * Así nunca queda negativa ni mayor que el total.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/validaciones.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';

exigir_rol('panolero');

/**
 * Vuelve al formulario con un error y con lo que se había escrito.
 */
function volver_al_formulario($id, $mensaje)
{
    $_SESSION['error_form']  = $mensaje;
    $_SESSION['form_previo'] = [
        'nombre'         => isset($_POST['nombre']) ? $_POST['nombre'] : '',
        'categoria'      => isset($_POST['categoria']) ? $_POST['categoria'] : '',
        'cantidad_total' => isset($_POST['cantidad_total']) ? $_POST['cantidad_total'] : 1,
    ];
    header('Location: ../vistas/herramienta_formulario.php' . ($id > 0 ? '?id=' . $id : ''));
    exit;
}

/**
 * Vuelve a la lista con un mensaje: verde si salió bien, rojo si es un error.
 */
function volver_a_la_lista($mensaje, $tipo = 'exito')
{
    $_SESSION[$tipo === 'error' ? 'error_form' : 'exito_form'] = $mensaje;
    header('Location: ../vistas/herramientas.php');
    exit;
}

// PASO 1: solo por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/herramientas.php');
    exit;
}

$pdo    = conectar();
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// ---------------------------------------------------------
// BAJA y REACTIVAR: solo cambian "activa".
// ---------------------------------------------------------
if ($accion === 'baja' || $accion === 'reactivar') {
    $activa = $accion === 'reactivar' ? 1 : 0;
    $cambio = $pdo->prepare("UPDATE herramientas SET activa = ? WHERE id = ?");
    $cambio->execute([$activa, $id]);

    volver_a_la_lista($activa ? 'Herramienta reactivada.' : 'Herramienta dada de baja. Su historial se conserva.');
}

if ($accion !== 'guardar') {
    volver_a_la_lista('Acción no válida.', 'error');
}

// ---------------------------------------------------------
// GUARDAR: leemos y validamos los datos.
// ---------------------------------------------------------
$nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
$total     = isset($_POST['cantidad_total']) ? (int) $_POST['cantidad_total'] : 0;

if (mb_strlen($nombre, 'UTF-8') < 2 || mb_strlen($nombre, 'UTF-8') > 100) {
    volver_al_formulario($id, 'El nombre tiene que tener entre 2 y 100 letras.');
}
if (mb_strlen($categoria, 'UTF-8') < 2 || mb_strlen($categoria, 'UTF-8') > 50) {
    volver_al_formulario($id, 'La categoría tiene que tener entre 2 y 50 letras.');
}

if ($id === 0) {
    // ---------- AGREGAR ----------
    // Una herramienta nueva no tiene nada prestado: disponibles = total.
    $error = error_cantidad_total($total);
    if ($error !== null) {
        volver_al_formulario($id, $error);
    }

    $insertar = $pdo->prepare(
        "INSERT INTO herramientas (nombre, categoria, cantidad_total, cantidad_disponible, activa)
         VALUES (?, ?, ?, ?, 1)"
    );
    $insertar->execute([$nombre, $categoria, $total, $total]);

    volver_a_la_lista('Herramienta agregada: ' . $nombre . '.');
}

// ---------- EDITAR ----------
// El total no puede ser menor que lo que está prestado ahora.
$en_uso = unidades_en_uso($pdo, $id);
$error  = error_cantidad_total($total, $en_uso);
if ($error !== null) {
    volver_al_formulario($id, $error);
}

// Recalculamos las disponibles: total nuevo menos lo prestado.
$actualizar = $pdo->prepare(
    "UPDATE herramientas
        SET nombre = ?, categoria = ?, cantidad_total = ?, cantidad_disponible = ?
      WHERE id = ?"
);
$actualizar->execute([$nombre, $categoria, $total, calcular_disponible($total, $en_uso), $id]);

if ($actualizar->rowCount() === 0) {
    // rowCount 0 puede ser "no existe" o "no cambió nada": revisamos cuál.
    $existe = $pdo->prepare("SELECT COUNT(*) FROM herramientas WHERE id = ?");
    $existe->execute([$id]);
    if ((int) $existe->fetchColumn() === 0) {
        volver_a_la_lista('Esa herramienta no existe.', 'error');
    }
}

volver_a_la_lista('Cambios guardados: ' . $nombre . '.');
