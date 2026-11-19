<?php
/**
 * validaciones.php
 * ------------------------------------------------------------
 * Funciones chiquitas para validar datos de los formularios.
 * Están separadas para poder probarlas solas (ver /pruebas).
 */

/**
 * Mira lo que escribió el usuario en "DNI o correo" y dice qué es.
 *
 * @param string $texto Lo que escribió el usuario
 * @return string|null 'email', 'dni' o null si no es ninguno de los dos
 */
function tipo_identificador($texto)
{
    $texto = trim($texto);

    // ¿Tiene formato de correo? (algo@algo.algo)
    if (filter_var($texto, FILTER_VALIDATE_EMAIL)) {
        return 'email';
    }

    // ¿Son solo números, entre 7 y 10? Entonces es un DNI.
    if (ctype_digit($texto) && strlen($texto) >= 7 && strlen($texto) <= 10) {
        return 'dni';
    }

    // No es ni correo ni DNI.
    return null;
}
