<?php
/**
 * controladores/cerrar_sesion.php
 * ------------------------------------------------------------
 * Borra la sesión y vuelve al login.
 */

require_once __DIR__ . '/../config/sesion.php';
iniciar_sesion();

$_SESSION = [];       // vaciamos todos los datos
session_destroy();    // destruimos la sesión en el servidor

header('Location: ../vistas/login.php');
exit;
