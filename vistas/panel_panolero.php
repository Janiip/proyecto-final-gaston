<?php
/**
 * vistas/panel_panolero.php
 * ------------------------------------------------------------
 * Panel del pañolero (por ahora solo el esqueleto).
 * Acá después va la alerta de cierre, el tablero y los accesos
 * a Gestión de préstamos, Herramientas y Usuarios.
 */

require_once __DIR__ . '/../config/sesion.php';
exigir_rol('panolero'); // solo el pañolero puede ver esta página
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pañolero | Pañol EEST N°1</title>
    <link rel="stylesheet" href="../publico/css/estilos.css">
</head>
<body>
    <header class="barra">
        <strong>Pañol EEST N°1</strong>
        <span><?= e($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?> · Pañolero</span>
        <a href="../controladores/cerrar_sesion.php">Cerrar sesión</a>
    </header>

    <main class="contenido">
        <h1>Panel del pañolero</h1>
        <p>Acá van a estar la alerta de cierre, el tablero y la gestión de préstamos.</p>
    </main>
</body>
</html>
