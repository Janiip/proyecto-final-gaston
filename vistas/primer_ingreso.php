<?php
/**
 * vistas/primer_ingreso.php
 * ------------------------------------------------------------
 * Primer ingreso del alumno (por ahora solo el esqueleto).
 * Acá después van los dos pasos: verificar el correo con el código
 * y cambiar la contraseña 1234 por una de 4 números.
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primer ingreso | Pañol EEST N°1</title>
    <link rel="stylesheet" href="../publico/css/estilos.css">
</head>
<body>
    <header class="barra">
        <strong>Pañol EEST N°1</strong>
        <a href="../controladores/cerrar_sesion.php">Cerrar sesión</a>
    </header>

    <main class="contenido">
        <h1>Primer ingreso</h1>
        <p>Paso 1 de 2: verificar tu correo. Paso 2 de 2: elegir tu contraseña nueva.</p>
    </main>
</body>
</html>
