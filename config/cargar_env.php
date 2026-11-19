<?php
/**
 * cargar_env.php
 * ------------------------------------------------------------
 * Lee el archivo ".env" de la raíz del proyecto y guarda cada
 * variable (CLAVE=valor) para poder usarla con env('CLAVE').
 *
 * Así las contraseñas de MySQL y del correo NO quedan escritas
 * en el código ni se suben al repositorio.
 */

/**
 * Carga las variables de un archivo .env.
 *
 * @param string $ruta Ruta completa del archivo .env
 */
function cargar_env($ruta)
{
    // Si el archivo no existe, no hacemos nada y devolvemos false.
    if (!file_exists($ruta)) {
        return false;
    }

    // file() lee el archivo y devuelve un array con una línea por posición.
    // FILE_IGNORE_NEW_LINES: saca el salto de línea del final.
    // FILE_SKIP_EMPTY_LINES: saltea las líneas vacías.
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        // Salteamos los comentarios (líneas que empiezan con #)
        // y las líneas que no tienen un "=".
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }

        // Cortamos la línea en el PRIMER "=": a la izquierda la clave,
        // a la derecha el valor (el valor puede tener otros "=").
        list($clave, $valor) = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);

        // Si el valor está entre comillas ("..." o '...'), se las sacamos.
        $largo = strlen($valor);
        if ($largo >= 2) {
            $primera = $valor[0];
            $ultima  = $valor[$largo - 1];
            if (($primera === '"' && $ultima === '"') || ($primera === "'" && $ultima === "'")) {
                $valor = substr($valor, 1, -1);
            }
        }

        // Guardamos la variable en $_ENV para leerla después.
        $_ENV[$clave] = $valor;
    }

    return true;
}

/**
 * Devuelve el valor de una variable de entorno.
 * Si no existe, devuelve el valor por defecto.
 *
 * Ejemplo: env('DB_NOMBRE', 'panol_escuela')
 */
function env($clave, $por_defecto = null)
{
    return isset($_ENV[$clave]) ? $_ENV[$clave] : $por_defecto;
}

// Apenas se incluye este archivo, cargamos el .env de la raíz.
// __DIR__ es la carpeta /config, así que "/../.env" es la raíz.
// Guardamos si se encontró, para que conexion.php pueda avisar si falta.
$env_cargado = cargar_env(__DIR__ . '/../.env');
