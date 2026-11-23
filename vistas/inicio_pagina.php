<?php
/**
 * vistas/inicio_pagina.php
 * ------------------------------------------------------------
 * Todo lo que se repite ARRIBA en cada pantalla con sesión:
 * <head>, menú lateral y barra superior.
 *
 * Antes de incluirlo, cada vista define:
 *   $titulo        Nombre de la página (pestaña y ruta). Ej: 'Mis préstamos'
 *   $pagina_activa Archivo de la página, para resaltarla en el menú. Ej: 'mis_prestamos.php'
 *
 * Al final de la vista se incluye vistas/fin_pagina.php para cerrar.
 */

require_once __DIR__ . '/../config/formato.php';

// El menú cambia según el rol: [archivo, ícono, texto]
if ($_SESSION['rol'] === 'panolero') {
    $menu = [
        ['panel_panolero.php', '&#9638;', 'Gestión de préstamos'],
        ['herramientas.php',   '&#9874;', 'Herramientas'],
        ['usuarios.php',       '&#9817;', 'Usuarios'],
    ];
    $rol_texto = 'Panel pañolero';
} else {
    $menu = [
        ['panel_alumno.php',      '&#8962;', 'Inicio'],
        ['pedir_herramienta.php', '+',       'Pedir herramienta'],
        ['mis_prestamos.php',     '&#9636;', 'Mis préstamos'],
    ];
    $rol_texto = 'Panel alumno';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | TallerNet</title>
    <!-- Letra Inter de Google Fonts (si no hay internet, usa Segoe UI o Arial) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<div class="app">

    <!-- ===== Menú lateral ===== -->
    <aside class="menu-lateral">
        <p class="menu-titulo">Gestion<br>Herramientas</p>

        <p class="menu-seccion">MENÚ PRINCIPAL</p>
        <ul class="menu-enlaces">
            <?php foreach ($menu as $item): ?>
                <li>
                    <!-- Si es la página actual, le agregamos la clase "activo" -->
                    <a href="<?= $item[0] ?>" class="<?= $item[0] === $pagina_activa ? 'activo' : '' ?>">
                        <span class="menu-icono"><?= $item[1] ?></span>
                        <?= $item[2] ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="menu-pie">
            Sistema de préstamos<br>
            Panel de gestión · TallerNet
        </div>
    </aside>

    <div class="app-contenido">

        <!-- ===== Barra superior ===== -->
        <header class="barra-superior">
            <div class="ruta">TallerNet/ <strong><?= e($titulo) ?></strong></div>

            <div class="usuario">
                <div class="avatar"><?= e(iniciales($_SESSION['nombre'], $_SESSION['apellido'])) ?></div>
                <div>
                    <div class="usuario-nombre"><?= e($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></div>
                    <div class="usuario-rol"><?= $rol_texto ?></div>
                </div>
                <a href="../login-acciones/cerrar_sesion.php" class="boton boton-secundario boton-chico">Salir</a>
            </div>
        </header>

        <!-- ===== Acá empieza el contenido de cada página ===== -->
        <main class="pagina">
