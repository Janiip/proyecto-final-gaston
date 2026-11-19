<?php
/**
 * vistas/panel_alumno.php
 * ------------------------------------------------------------
 * Panel del alumno (por ahora solo el esqueleto).
 * Acá después va el catálogo de herramientas y "Mis préstamos".
 */

require_once __DIR__ . '/../config/sesion.php';
exigir_rol('alumno'); // si no es alumno (o no terminó el primer ingreso), no entra
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Alumno | Pañol EEST N°1</title>
    <link rel="stylesheet" href="../publico/css/estilos.css">
</head>
<body>
    <header class="barra">
        <strong>Pañol EEST N°1</strong>
        <span><?= e($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?> · <?= e($_SESSION['curso']) ?></span>
        <a href="../controladores/cerrar_sesion.php">Cerrar sesión</a>
    </header>

    <main class="contenido">
        <h1>Hola, <?= e($_SESSION['nombre']) ?></h1>
        <p>Acá vas a ver las herramientas del pañol y tus préstamos.</p>
    </main>
</body>
</html>
