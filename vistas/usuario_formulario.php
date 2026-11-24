<?php
/**
 * vistas/usuario_formulario.php
 * ------------------------------------------------------------
 * Formulario para DAR DE ALTA un alumno (sin ?id) o EDITAR
 * un usuario (con ?id=5). Desde acá también se restablece la contraseña.
 * Lo procesa login-acciones/guardar_usuario.php (RF13).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
exigir_rol('panolero');

$pdo = conectar();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Valores vacíos para un alumno nuevo...
$usuario = ['nombre' => '', 'apellido' => '', 'dni' => '', 'email' => '', 'curso' => '',
            'rol' => 'alumno', 'activo' => 1, 'correo_verificado' => 0, 'password_cambiada' => 0];

// ...o los de la base si estamos editando (nunca traemos la contraseña).
if ($id > 0) {
    $consulta = $pdo->prepare(
        "SELECT nombre, apellido, dni, email, curso, rol, activo, correo_verificado, password_cambiada
           FROM usuarios WHERE id = ?"
    );
    $consulta->execute([$id]);
    $usuario = $consulta->fetch();

    if (!$usuario) {
        $_SESSION['error_form'] = 'Ese usuario no existe.';
        header('Location: usuarios.php');
        exit;
    }
}

// Si volvimos por un error, mostramos lo que se había escrito.
if (isset($_SESSION['form_previo'])) {
    $usuario = array_merge($usuario, $_SESSION['form_previo']);
    unset($_SESSION['form_previo']);
}

$error = isset($_SESSION['error_form']) ? $_SESSION['error_form'] : '';
unset($_SESSION['error_form']);

// ¿Está editando su propia cuenta? Entonces no puede desactivarse.
$es_mi_cuenta = $id === (int) $_SESSION['usuario_id'];

$titulo = $id > 0 ? 'Editar usuario' : 'Agregar alumno';
$pagina_activa = 'usuarios.php'; // en el menú queda resaltado "Usuarios"
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Administración</p>
        <h1><?= $titulo ?></h1>
        <p><?= $id > 0 ? 'Modificá los datos de la cuenta.' : 'El alumno va a entrar con su DNI y la contraseña 1234.' ?></p>
    </div>
    <a href="usuarios.php" class="boton boton-secundario">&larr; Volver</a>
</div>

<section class="tarjeta formulario">

    <?php if ($error): ?><div class="mensaje mensaje-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($id === 0): ?>
        <div class="info">
            La primera vez que entre, el alumno va a tener que verificar este correo con un código
            y cambiar la contraseña 1234 por una propia.
        </div>
    <?php endif; ?>

    <form action="../login-acciones/guardar_usuario.php" method="POST">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="60" required value="<?= e($usuario['nombre']) ?>">
        </div>

        <div class="campo">
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" maxlength="60" required value="<?= e($usuario['apellido']) ?>">
        </div>

        <div class="campo campo-corto">
            <label for="dni">DNI (solo números)</label>
            <input type="text" id="dni" name="dni" inputmode="numeric" maxlength="10" pattern="[0-9]{7,10}" required
                   value="<?= e($usuario['dni']) ?>">
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" maxlength="100" required value="<?= e($usuario['email']) ?>">
        </div>

        <?php if ($usuario['rol'] === 'alumno'): ?>
            <div class="campo campo-corto">
                <label for="curso">Curso</label>
                <input type="text" id="curso" name="curso" maxlength="10" required
                       value="<?= e($usuario['curso']) ?>" placeholder="Ej: 6° 1°">
            </div>
        <?php endif; ?>

        <?php if ($id > 0 && !$es_mi_cuenta): ?>
            <!-- Un usuario inactivo no puede iniciar sesión, pero su historial queda -->
            <div class="campo campo-check">
                <label><input type="checkbox" name="activo" value="1" <?= $usuario['activo'] ? 'checked' : '' ?>>
                    Cuenta activa (puede iniciar sesión)</label>
            </div>
        <?php endif; ?>

        <button type="submit" class="boton boton-principal boton-ancho">
            <?= $id > 0 ? 'Guardar cambios' : 'Agregar alumno' ?>
        </button>
    </form>

    <?php if ($id > 0 && $usuario['rol'] === 'alumno'): ?>
        <!-- Restablecer: vuelve a 1234 y repite el primer ingreso -->
        <form action="../login-acciones/guardar_usuario.php" method="POST" class="zona-baja">
            <input type="hidden" name="id" value="<?= $id ?>">
            <p>¿Se olvidó la contraseña? Restablecerla la vuelve a <b>1234</b> y el alumno tiene que repetir
                el primer ingreso (verificar el correo y elegir una contraseña nueva).</p>
            <button name="accion" value="restablecer" class="boton boton-secundario">Restablecer contraseña</button>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
