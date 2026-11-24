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

/**
 * ¿Es un DNI válido? Solo números, entre 7 y 10 dígitos.
 */
function es_dni_valido($dni)
{
    return ctype_digit($dni) && strlen($dni) >= 7 && strlen($dni) <= 10;
}

/**
 * ¿Es un correo con formato válido? (algo@algo.algo)
 */
function es_email_valido($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * ¿Es una fecha real con formato AAAA-MM-DD?
 * checkdate() rechaza fechas que no existen, como un 31 de febrero.
 */
function es_fecha_valida($fecha)
{
    $partes = explode('-', $fecha);
    return count($partes) === 3
        && ctype_digit(implode('', $partes))
        && checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]);
}

/**
 * Valida la cantidad total que el pañolero carga para una herramienta.
 * Al EDITAR, no puede ser menor que las unidades que están prestadas ahora
 * (si hay 3 afuera, no puede decir que el pañol tiene 2 en total).
 *
 * @param int $total  Cantidad total nueva
 * @param int $en_uso Unidades que están retiradas en este momento
 * @return string|null El error, o null si está bien
 */
function error_cantidad_total($total, $en_uso = 0)
{
    if ($total < 1) {
        return 'La cantidad total tiene que ser 1 o más.';
    }
    if ($total < $en_uso) {
        return "No puede ser menor que las unidades prestadas ahora ($en_uso).";
    }
    return null;
}

/**
 * Calcula las unidades disponibles: el total menos las que están prestadas.
 * Así cantidad_disponible nunca baja de 0 ni supera el total.
 */
function calcular_disponible($total, $en_uso)
{
    return max(0, min($total, $total - $en_uso));
}
