<?php
/**
 * vistas/login.php
 * ------------------------------------------------------------
 * Pantalla de inicio de sesión (mockup: login.png).
 * Solo muestra el formulario: la validación la hace
 * login-acciones/validar_login.php
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
    <title>Ingresar | TallerNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<div class="login">

    <!-- Columna izquierda: logo y nombre del sistema -->
    <section class="login-marca">
        <img src="../img/logo_eest.png" alt="Logo de la EEST N°1" class="login-logo">
        <p class="login-nombre">TallerNet</p>
        <p class="login-lema">gestor de herramientas</p>
    </section>

    <!-- Columna derecha: tarjeta blanca con el formulario -->
    <section class="login-formulario">
        <div class="login-tarjeta">
            <h1>¡Bienvenido/a!</h1>
            <p class="subtitulo">Ingresá tus datos para continuar.</p>

            <?php if ($error !== ''): ?>
                <!-- Franja roja con el error (e() evita que se ejecute código raro) -->
                <div class="mensaje mensaje-error"><?= e($error) ?></div>
            <?php endif; ?>

            <!-- El formulario manda los datos al controlador por POST
                 (POST: los datos no quedan a la vista en la barra de direcciones). -->
            <form action="../login-acciones/validar_login.php" method="POST" novalidate>

                <div class="campo">
                    <label for="identificador">DNI o correo electrónico</label>
                    <input type="text" id="identificador" name="identificador"
                           value="<?= e($identificador) ?>"
                           placeholder="Ingresá tu DNI o tu correo"
                           autocomplete="username" required>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password"
                           placeholder="Ingresá tu contraseña"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="boton boton-principal boton-ancho">Iniciar sesión</button>
            </form>

            <div class="login-enlaces">
                ¿Es tu primer ingreso? Usá la contraseña que te dio el pañolero.<br>
                <strong>¿Te olvidaste la contraseña? Pedile al pañolero que la restablezca.</strong>
            </div>
        </div>
    </section>

</div>

<script src="../js/login.js"></script>
</body>
</html>
