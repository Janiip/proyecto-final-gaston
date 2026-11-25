<?php
/**
 * horario.php
 * ------------------------------------------------------------
 * Horario del pañol (RNF8): lunes a viernes de 7:20 a 17:30.
 * Si la escuela lo cambia, se modifica SOLO acá.
 *
 * Todas las funciones reciben un parámetro opcional $ahora
 * (un timestamp). Si no se pasa, usan la hora actual.
 * Sirve para las pruebas: podemos preguntar "¿qué pasaría
 * un martes a las 16:45?" sin esperar a que sea martes.
 *
 * La zona horaria (America/Argentina/Buenos_Aires) se fija
 * en config/conexion.php con date_default_timezone_set().
 */

define('HORA_APERTURA', '07:20');
define('HORA_CIERRE', '21:50');
define('HORA_ALERTA_CIERRE', '20:05'); // una hora antes del cierre

/**
 * Devuelve el momento a usar: el que nos pasaron o el actual.
 */
function momento($ahora = null)
{
    return $ahora === null ? time() : $ahora;
}

/**
 * ¿Es fin de semana? date('N') devuelve 1 (lunes) ... 7 (domingo).
 */
function es_fin_de_semana($ahora = null)
{
    return (int) date('N', momento($ahora)) >= 6;
}

/**
 * ¿Estamos fuera del horario del pañol? (RF5: pedir justificación)
 * Comparamos las horas como texto "HH:MM": funciona porque
 * siempre tienen 2 dígitos ("07:20" < "16:30").
 */
function fuera_de_horario($ahora = null)
{
    $hora = date('H:i', momento($ahora));
    return es_fin_de_semana($ahora) || $hora < HORA_APERTURA || $hora >= HORA_CIERRE;
}

/**
 * ¿Hay que mostrar la alerta roja de cierre? (RF11)
 * Día hábil, desde las 16:30 hasta el cierre.
 */
function alerta_de_cierre_activa($ahora = null)
{
    $hora = date('H:i', momento($ahora));
    return !es_fin_de_semana($ahora) && $hora >= HORA_ALERTA_CIERRE && $hora < HORA_CIERRE;
}
