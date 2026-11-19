<?php
/**
 * vistas/login.php
 * ------------------------------------------------------------
 * Pantalla de inicio de sesión.
 * Solo muestra el formulario: la validación la hace
 * controladores/validar_login.php
 */

require_once __DIR__ . '/../config/sesion.php';
iniciar_sesion();

// Si ya inició sesión, no tiene sentido mostrarle el login:
// lo mandamos directo a su panel.
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . panel_segun_rol($_SESSION['rol']));
    exit;
}

// Leemos el mensaje de error (si hubo) y lo borramos de la sesión,
// así no vuelve a aparecer si recarga la página.
$error = isset($_SESSION['error_login']) ? $_SESSION['error_login'] : '';
unset($_SESSION['error_login']);

// Lo que había escrito en "DNI o correo", para no hacerle escribirlo de nuevo.
$identificador = isset($_SESSION['identificador_previo']) ? $_SESSION['identificador_previo'] : '';
unset($_SESSION['identificador_previo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar | Pañol EEST N°1</title>
    <link rel="stylesheet" href="../publico/css/estilos.css">
</head>
<body class="fondo-login">

    <main class="caja-login">
        <h1>Pañol EEST N°1</h1>
        <p class="subtitulo">Ingresá para ver y pedir herramientas</p>

        <?php if ($error !== ''): ?>
            <!-- Franja roja con el error (e() evita que se ejecute código raro) -->
            <div class="mensaje mensaje-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- El formulario manda los datos al controlador por POST
             (POST: los datos no quedan a la vista en la barra de direcciones). -->
        <form action="../controladores/validar_login.php" method="POST" novalidate>

            <label for="identificador">DNI o correo electrónico</label>
            <input type="text" id="identificador" name="identificador"
                   value="<?= e($identificador) ?>"
                   placeholder="Ej: 45111222 o nombre@gmail.com"
                   autocomplete="username" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>

            <button type="submit" class="boton-principal">Ingresar</button>
        </form>

        <p class="nota">¿Te olvidaste la contraseña? Pedile al pañolero que la restablezca.</p>
    </main>

    <script src="../publico/js/login.js"></script>
</body>
</html>
