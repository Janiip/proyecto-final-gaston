<?php
/**
 * sesion.php
 * ------------------------------------------------------------
 * Funciones para manejar la sesión del usuario.
 * Las usan todas las pantallas que necesitan login.
 */

/**
 * Inicia la sesión de PHP si todavía no está iniciada.
 */
function iniciar_sesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        // Parámetros de la cookie de sesión, en orden:
        // dura hasta cerrar el navegador (0), vale para todo el sitio ('/'),
        // dominio actual (''), funciona sin https (false),
        // y NO se puede leer desde JavaScript (true = httponly).
        // (Usamos esta forma porque es compatible con PHP 7.1 de XAMPP.)
        session_set_cookie_params(0, '/', '', false, true);
        session_start();
    }
}

/**
 * Corta la página si el usuario no inició sesión o no tiene el rol pedido.
 * Ejemplo: exigir_rol('panolero') al principio de panel_panolero.php
 *
 * @param string $rol 'alumno' o 'panolero'
 */
function exigir_rol($rol)
{
    iniciar_sesion();

    // Las direcciones empiezan con "../vistas/" para que funcionen
    // tanto desde /vistas como desde /login-acciones (son carpetas hermanas).

    // Sin sesión: lo mandamos al login.
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../vistas/login.php');
        exit;
    }

    // Con sesión pero de otro rol: lo mandamos a SU panel.
    if ($_SESSION['rol'] !== $rol) {
        header('Location: ' . panel_segun_rol($_SESSION['rol']));
        exit;
    }

    // Un alumno que no terminó el primer ingreso no puede usar el sistema.
    if ($rol === 'alumno' && empty($_SESSION['primer_ingreso_completo'])) {
        header('Location: ../vistas/primer_ingreso.php');
        exit;
    }
}

/**
 * Devuelve la pantalla principal de cada rol.
 * Empieza con "../vistas/" para que sirva desde /vistas y desde /login-acciones.
 */
function panel_segun_rol($rol)
{
    return $rol === 'panolero' ? '../vistas/panel_panolero.php' : '../vistas/panel_alumno.php';
}

/**
 * Escapa un texto antes de mostrarlo en el HTML.
 * Evita que alguien meta código (XSS) en un nombre, por ejemplo.
 */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
