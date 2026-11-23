<?php
/**
 * vistas/primer_ingreso.php
 * ------------------------------------------------------------
 * Primer ingreso del alumno (RF2). Usa el mismo diseño que el login,
 * porque todavía no puede usar el menú del sistema.
 * Paso 1: verificar el correo con el código (login-acciones/verificar_codigo.php)
 * Paso 2: elegir contraseña nueva (login-acciones/cambiar_password.php)
 */

require_once __DIR__ . '/../config/sesion.php';
iniciar_sesion();

// Sin sesión, al login.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Si ya lo completó (o es el pañolero), no tiene nada que hacer acá.
if ($_SESSION['rol'] !== 'alumno' || !empty($_SESSION['primer_ingreso_completo'])) {
    header('Location: ' . panel_segun_rol($_SESSION['rol']));
    exit;
}

// ¿En qué paso está? Lo guarda el controlador cuando verifica el código.
$paso = !empty($_SESSION['correo_verificado']) ? 2 : 1;

$error = isset($_SESSION['error_form']) ? $_SESSION['error_form'] : '';
unset($_SESSION['error_form']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primer ingreso | TallerNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<div class="login">
    <section class="login-marca">
        <img src="../img/logo_eestn1.png" alt="Logo de la EEST N°1" class="login-logo">
        <p class="login-nombre">TallerNet</p>
        <p class="login-lema">gestor de herramientas</p>
    </section>

    <section class="login-formulario">
        <div class="login-tarjeta">
            <p class="categoria">Primer ingreso · Paso <?= $paso ?> de 2</p>

            <?php if ($error): ?><div class="mensaje mensaje-error"><?= e($error) ?></div><?php endif; ?>

            <?php if ($paso === 1): ?>
                <h1>Verificá tu correo</h1>
                <p class="subtitulo">Te mandamos un código de 6 números al correo que cargó el pañolero.</p>

                <form action="../login-acciones/verificar_codigo.php" method="POST">
                    <div class="campo">
                        <label for="codigo">Código de verificación</label>
                        <input type="text" id="codigo" name="codigo" inputmode="numeric"
                               maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>
                    </div>
                    <button type="submit" class="boton boton-principal boton-ancho">Verificar</button>
                </form>

                <div class="login-enlaces">
                    <a href="../login-acciones/verificar_codigo.php?reenviar=1">Reenviar código</a><br>
                    Si el correo no es tuyo, avisale al pañolero.
                </div>

            <?php else: ?>
                <h1>Elegí tu contraseña</h1>
                <p class="subtitulo">Tiene que ser de 4 números y distinta de 1234.</p>

                <form action="../login-acciones/cambiar_password.php" method="POST">
                    <div class="campo">
                        <label for="nueva">Contraseña nueva</label>
                        <input type="password" id="nueva" name="nueva" inputmode="numeric"
                               maxlength="4" pattern="[0-9]{4}" required>
                    </div>
                    <div class="campo">
                        <label for="repetir">Repetir contraseña</label>
                        <input type="password" id="repetir" name="repetir" inputmode="numeric"
                               maxlength="4" pattern="[0-9]{4}" required>
                    </div>
                    <button type="submit" class="boton boton-principal boton-ancho">Guardar</button>
                </form>
            <?php endif; ?>

            <div class="login-enlaces">
                <a href="../login-acciones/cerrar_sesion.php">Cerrar sesión</a>
            </div>
        </div>
    </section>
</div>

</body>
</html>
