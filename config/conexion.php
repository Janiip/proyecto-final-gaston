<?php
/**
 * conexion.php
 * ------------------------------------------------------------
 * Crea la conexión a la base de datos con PDO.
 * Cualquier archivo que necesite la base hace:
 *
 *     require_once __DIR__ . '/../config/conexion.php';
 *     $pdo = conectar();
 */

require_once __DIR__ . '/cargar_env.php';

// Sin .env no tenemos los datos de la base: avisamos cómo arreglarlo.
if (!$env_cargado) {
    die('Falta el archivo .env. Copiá ".env.example", renombralo a ".env" y completalo.');
}

// Usamos la hora de Argentina para todo el sistema
// (importa para el horario del pañol y la alerta de las 16:30).
date_default_timezone_set(env('ZONA_HORARIA', 'America/Argentina/Buenos_Aires'));

/**
 * Devuelve una conexión PDO lista para usar.
 * Usa "static" para conectarse una sola vez por página,
 * aunque se llame a conectar() muchas veces.
 */
function conectar()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo; // ya estaba conectada
    }

    // El DSN le dice a PDO a qué servidor y base conectarse.
    // charset=utf8mb4 para que se guarden bien las tildes y la ñ.
    $dsn = 'mysql:host=' . env('DB_HOST', 'localhost')
         . ';port=' . env('DB_PUERTO', '3306')
         . ';dbname=' . env('DB_NOMBRE', 'panol_escuela')
         . ';charset=utf8mb4';

    $opciones = [
        // Si una consulta falla, PDO lanza una excepción (no falla en silencio).
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Los resultados vienen como array con el nombre de la columna.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Sentencias preparadas REALES: MySQL recibe la consulta y los datos
        // por separado, así nadie puede "inyectar" SQL en un formulario.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, env('DB_USUARIO', 'root'), env('DB_CLAVE', ''), $opciones);
    } catch (PDOException $e) {
        // El error real se guarda en el log del servidor (para nosotras).
        error_log('Error de conexión: ' . $e->getMessage());
        // Al usuario le mostramos un mensaje simple, SIN datos de la base.
        die('No se pudo conectar con la base de datos. Avisale al pañolero.');
    }

    return $pdo;
}
