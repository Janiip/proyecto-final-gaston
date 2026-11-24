<?php
/**
 * login-acciones/enviar_avisos.php
 * ------------------------------------------------------------
 * Botón "Enviar avisos ahora" del panel del pañolero.
 * Corre los mismos avisos que la tarea automática (Menú A).
 * Sirve para probar y para mandarlos si la compu estuvo apagada.
 * Como cada aviso anota que ya se mandó, apretarlo dos veces no repite mails.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/avisos.php';

exigir_rol('panolero');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../vistas/panel_panolero.php');
    exit;
}

$resultado = ejecutar_avisos_automaticos(conectar());

if ($resultado['fallidos'] > 0) {
    $_SESSION['error_form'] = 'Se enviaron ' . $resultado['enviados'] . ' aviso(s), pero '
        . $resultado['fallidos'] . ' no se pudieron enviar. Se van a reintentar en la próxima pasada.';
} elseif ($resultado['enviados'] > 0) {
    $_SESSION['exito_form'] = 'Se enviaron ' . $resultado['enviados'] . ' aviso(s) por mail.';
} else {
    $_SESSION['exito_form'] = 'No había avisos pendientes para enviar.';
}

header('Location: ../vistas/panel_panolero.php');
exit;
