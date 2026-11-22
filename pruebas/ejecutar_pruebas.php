<?php
/**
 * pruebas/ejecutar_pruebas.php
 * ------------------------------------------------------------
 * Pruebas automatizadas simples (sin instalar nada).
 * Se corren desde la consola, parados en la carpeta del proyecto:
 *
 *     php pruebas/ejecutar_pruebas.php
 *
 * Cada prueba compara lo que devuelve una función con lo que
 * esperamos. Al final dice cuántas pasaron y cuántas fallaron.
 */

require_once __DIR__ . '/../config/cargar_env.php';
require_once __DIR__ . '/../config/validaciones.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';

// Las pruebas de horario usan la hora de Argentina, igual que el sistema.
date_default_timezone_set('America/Argentina/Buenos_Aires');

$pasaron = 0;
$fallaron = 0;

/**
 * Compara el resultado con lo esperado y muestra OK o FALLÓ.
 */
function comprobar($nombre, $resultado, $esperado)
{
    global $pasaron, $fallaron;

    if ($resultado === $esperado) {
        echo "  OK     $nombre\n";
        $pasaron++;
    } else {
        echo "  FALLÓ  $nombre (esperaba " . var_export($esperado, true)
           . ', llegó ' . var_export($resultado, true) . ")\n";
        $fallaron++;
    }
}

// ---------------------------------------------------------
// Pruebas de tipo_identificador() (login con DNI o correo)
// ---------------------------------------------------------
echo "Login: DNI o correo\n";
comprobar('Un DNI de 8 números es DNI',            tipo_identificador('45111222'), 'dni');
comprobar('Un correo válido es email',             tipo_identificador('alumna@gmail.com'), 'email');
comprobar('Los espacios de más no molestan',       tipo_identificador('  45111222  '), 'dni');
comprobar('Un DNI con letras no sirve',            tipo_identificador('4511A222'), null);
comprobar('Un DNI muy corto no sirve',             tipo_identificador('123'), null);
comprobar('Un correo sin arroba no sirve',         tipo_identificador('alumna.gmail.com'), null);
comprobar('Un intento de inyección SQL no sirve',  tipo_identificador("' OR '1'='1"), null);

// ---------------------------------------------------------
// Pruebas de cargar_env() con un .env de prueba temporal
// ---------------------------------------------------------
echo "\nCargador de .env\n";
$archivo_prueba = sys_get_temp_dir() . '/env_de_prueba_panol';
file_put_contents($archivo_prueba,
    "# un comentario\n" .
    "PRUEBA_NOMBRE=panol_escuela\n" .
    "PRUEBA_COMILLAS=\"Pañol EEST N°1\"\n" .
    "PRUEBA_VACIA=\n" .
    "PRUEBA_CON_IGUAL=a=b\n"
);
cargar_env($archivo_prueba);

comprobar('Lee un valor simple',                    env('PRUEBA_NOMBRE'), 'panol_escuela');
comprobar('Saca las comillas del valor',            env('PRUEBA_COMILLAS'), 'Pañol EEST N°1');
comprobar('Un valor vacío queda vacío',             env('PRUEBA_VACIA'), '');
comprobar('Respeta un "=" dentro del valor',        env('PRUEBA_CON_IGUAL'), 'a=b');
comprobar('Si no existe, usa el valor por defecto', env('NO_EXISTE', 'defecto'), 'defecto');
comprobar('Un archivo que no existe devuelve false', cargar_env('/no/existe/.env'), false);

unlink($archivo_prueba); // borramos el archivo temporal

// ---------------------------------------------------------
// Pruebas del horario del pañol (lunes a viernes de 7:20 a 17:30)
// mktime(hora, minuto, segundo, mes, día, año)
// El 24/11/2026 es martes y el 28/11/2026 es sábado.
// ---------------------------------------------------------
echo "\nHorario del pañol\n";
$martes = function ($h, $m) { return mktime($h, $m, 0, 11, 24, 2026); };
$sabado = mktime(10, 0, 0, 11, 28, 2026);

comprobar('Martes 7:00 está fuera de horario',        fuera_de_horario($martes(7, 0)), true);
comprobar('Martes 7:20 ya está en horario',           fuera_de_horario($martes(7, 20)), false);
comprobar('Martes 17:29 todavía está en horario',     fuera_de_horario($martes(17, 29)), false);
comprobar('Martes 17:30 ya está fuera de horario',    fuera_de_horario($martes(17, 30)), true);
comprobar('Sábado 10:00 está fuera de horario',       fuera_de_horario($sabado), true);
comprobar('Martes 16:29 todavía no hay alerta',       alerta_de_cierre_activa($martes(16, 29)), false);
comprobar('Martes 16:30 aparece la alerta de cierre', alerta_de_cierre_activa($martes(16, 30)), true);
comprobar('Martes 17:30 la alerta ya no se muestra',  alerta_de_cierre_activa($martes(17, 30)), false);
comprobar('Sábado no hay alerta de cierre',           alerta_de_cierre_activa(mktime(16, 45, 0, 11, 28, 2026)), false);

// ---------------------------------------------------------
// Pruebas de vencimiento (vence a las 17:30 de su día de devolución)
// ---------------------------------------------------------
echo "\nVencimiento de préstamos\n";
comprobar('Retirado para hoy, a las 17:29: no vencido',    esta_vencido('retirado', '2026-11-24', $martes(17, 29)), false);
comprobar('Retirado para hoy, a las 17:30: vencido',       esta_vencido('retirado', '2026-11-24', $martes(17, 30)), true);
comprobar('Retirado para ayer, a las 9:00: vencido',       esta_vencido('retirado', '2026-11-23', $martes(9, 0)), true);
comprobar('Retirado para mañana, a las 18:00: no vencido', esta_vencido('retirado', '2026-11-25', $martes(18, 0)), false);
comprobar('Una solicitud vieja no vence (no la retiró)',   esta_vencido('solicitado', '2026-11-01', $martes(9, 0)), false);
comprobar('Un préstamo devuelto no vence',                 esta_vencido('devuelto', '2026-11-01', $martes(9, 0)), false);

// ---------------------------------------------------------
// Resumen
// ---------------------------------------------------------
echo "\nResultado: $pasaron pasaron, $fallaron fallaron.\n";

// Si alguna falló, salimos con código 1 (útil si después usamos CI).
exit($fallaron > 0 ? 1 : 0);
