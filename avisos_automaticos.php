<?php
/**
 * avisos_automaticos.php
 * ------------------------------------------------------------
 * Tarea automática del Menú A. La ejecuta el Programador de tareas
 * de Windows (con avisos_automaticos.bat), sin que nadie abra el panel.
 *
 * Qué hace cada vez que corre:
 *   - Entre las 16:30 y las 17:30 de un día hábil: el aviso de cierre.
 *   - Siempre: recordatorios (vence mañana) y avisos de vencido.
 * Cada aviso anota que ya se mandó, así que correrlo cada 15 minutos
 * no repite mails.
 *
 * Para probarlo a mano, desde la carpeta del proyecto:
 *     C:\xampp\php\php.exe avisos_automaticos.php
 */

// Solo se puede correr desde la consola, no desde el navegador.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este archivo solo se ejecuta desde la tarea automática.');
}

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/config/avisos.php';

$resultado = ejecutar_avisos_automaticos(conectar());

// Lo que se imprime queda en el historial de la tarea (útil para revisar).
echo date('d/m/Y H:i') . ' - Avisos enviados: ' . $resultado['enviados']
   . ' - Fallidos: ' . $resultado['fallidos'] . PHP_EOL;
