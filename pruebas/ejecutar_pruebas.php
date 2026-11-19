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
// Resumen
// ---------------------------------------------------------
echo "\nResultado: $pasaron pasaron, $fallaron fallaron.\n";

// Si alguna falló, salimos con código 1 (útil si después usamos CI).
exit($fallaron > 0 ? 1 : 0);
