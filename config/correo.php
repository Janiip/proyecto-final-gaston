<?php
/**
 * correo.php
 * ------------------------------------------------------------
 * Envío de mails del Menú A (servicio externo: correo SMTP).
 *
 * Una sola función para mandar: enviar_correo(). Nunca "rompe" el
 * sistema: si el mail falla, devuelve ok = false y el resto sigue.
 *
 * Tiene dos modos (variable CORREO_MODO del .env):
 *   - "archivo": NO manda nada. Guarda cada mail como página HTML en
 *                correos-enviados/. Sirve para probar en XAMPP sin Gmail.
 *   - "smtp":    manda el mail de verdad con PHPMailer
 *                (los archivos van en lib/PHPMailer/src/, ver LEEME.txt).
 */

require_once __DIR__ . '/cargar_env.php';
require_once __DIR__ . '/sesion.php'; // para e(), que escapa el texto en el HTML

// "use" le da un nombre corto a las clases de PHPMailer.
// Si la librería no está, estas líneas no dan error: solo fallaría al usarla.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExcepcionCorreo;

/**
 * Manda un mail.
 *
 * @param string $para_email  Correo del destinatario
 * @param string $para_nombre Nombre del destinatario
 * @param string $asunto      Asunto del mail
 * @param string $html        Cuerpo del mail en HTML (usar plantilla_correo())
 * @return array ['ok' => true/false, 'error' => 'motivo si falló']
 */
function enviar_correo($para_email, $para_nombre, $asunto, $html)
{
    // Sin un correo válido no hay a quién mandarle nada.
    if (!filter_var($para_email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'El correo del destinatario no es válido.'];
    }

    if (env('CORREO_MODO', 'archivo') === 'smtp') {
        return enviar_por_smtp($para_email, $para_nombre, $asunto, $html);
    }
    return guardar_en_archivo($para_email, $asunto, $html);
}

/**
 * Modo "smtp": manda el mail con PHPMailer.
 */
function enviar_por_smtp($para_email, $para_nombre, $asunto, $html)
{
    $carpeta = __DIR__ . '/../lib/PHPMailer/src/';

    // Si falta la librería, avisamos en vez de tirar un error de PHP.
    if (!file_exists($carpeta . 'PHPMailer.php')) {
        return ['ok' => false, 'error' => 'Falta PHPMailer en lib/PHPMailer/src (ver LEEME.txt).'];
    }
    require_once $carpeta . 'Exception.php';
    require_once $carpeta . 'PHPMailer.php';
    require_once $carpeta . 'SMTP.php';

    // new PHPMailer(true): si algo sale mal, lanza una excepción que atrapamos abajo.
    $mail = new PHPMailer(true);

    try {
        // Datos del servidor, todos leídos del .env (no hay claves en el código).
        $mail->isSMTP();
        $mail->Host     = env('SMTP_HOST', 'smtp.gmail.com');
        $mail->Port     = (int) env('SMTP_PUERTO', '587');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USUARIO');
        $mail->Password = env('SMTP_CLAVE');
        // Puerto 465 usa SSL; el 587 usa TLS (lo normal en Gmail).
        $mail->SMTPSecure = $mail->Port === 465 ? 'ssl' : 'tls';
        $mail->CharSet  = 'UTF-8';   // para que lleguen bien la ñ y las tildes
        $mail->Timeout  = 10;        // si el servidor no responde en 10 segundos, se corta

        // Quién manda y a quién
        $mail->setFrom(env('SMTP_USUARIO'), env('SMTP_NOMBRE_REMITENTE', 'Pañol EEST N°1'));
        $mail->addAddress($para_email, $para_nombre);

        // Contenido: versión HTML y versión de solo texto (para lectores simples)
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $html;
        $mail->AltBody = html_a_texto($html);

        $mail->send();
        return ['ok' => true, 'error' => ''];

    } catch (ExcepcionCorreo $e) {
        // El detalle técnico va al log del servidor (puede tener datos internos);
        // al sistema le devolvemos un mensaje simple.
        error_log('Error al enviar correo a ' . $para_email . ': ' . $mail->ErrorInfo);
        return ['ok' => false, 'error' => 'No se pudo enviar el correo.'];
    }
}

/**
 * Modo "archivo": guarda el mail como página HTML en correos-enviados/.
 * Abriendo ese archivo en el navegador se ve el mail tal como llegaría.
 */
function guardar_en_archivo($para_email, $asunto, $html)
{
    $carpeta = __DIR__ . '/../correos-enviados';
    if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
        return ['ok' => false, 'error' => 'No se pudo crear la carpeta correos-enviados.'];
    }

    // Nombre del archivo: fecha y hora + parte del correo, sin caracteres raros.
    $nombre = date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-z0-9]+/i', '-', $para_email) . '_' . mt_rand(100, 999) . '.html';

    $contenido = '<p style="font-family:Arial;font-size:13px;color:#555">'
               . '<b>Para:</b> ' . e($para_email) . '<br><b>Asunto:</b> ' . e($asunto) . '</p><hr>' . $html;

    if (file_put_contents($carpeta . '/' . $nombre, $contenido) === false) {
        return ['ok' => false, 'error' => 'No se pudo guardar el correo.'];
    }
    return ['ok' => true, 'error' => ''];
}

/**
 * Arma el HTML de un mail con el mismo estilo para todos los avisos.
 *
 * @param string $titulo    Título grande del mail
 * @param string $contenido HTML del cuerpo (ya escapado con e())
 */
function plantilla_correo($titulo, $contenido)
{
    $enlace = e(env('APP_URL', 'http://localhost/panol_escuela'));

    return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#1f2440">'
         . '<div style="background:#2a2f63;color:#fff;padding:18px 24px;border-radius:10px 10px 0 0">'
         . '<b style="font-size:18px">TallerNet</b> · Pañol EEST N°1</div>'
         . '<div style="border:1px solid #e5e9f0;border-top:none;padding:24px;border-radius:0 0 10px 10px">'
         . '<h2 style="margin:0 0 14px;color:#24295a;font-size:20px">' . e($titulo) . '</h2>'
         . $contenido
         . '<p style="margin-top:24px"><a href="' . $enlace . '" style="background:#3d6a96;color:#fff;'
         . 'padding:10px 16px;border-radius:7px;text-decoration:none">Entrar al sistema</a></p>'
         . '<p style="font-size:12px;color:#6b7280;margin-top:24px">Este es un aviso automático del pañol. '
         . 'No hace falta responder.</p></div></div>';
}

/**
 * Convierte el HTML en texto simple (para la versión sin formato del mail).
 */
function html_a_texto($html)
{
    $texto = preg_replace('/<(br|\/p|\/li|\/h2|\/div)>/i', "\n", $html);
    return trim(html_entity_decode(strip_tags($texto), ENT_QUOTES, 'UTF-8'));
}
