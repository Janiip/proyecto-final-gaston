<?php
/**
 * avisos.php
 * ------------------------------------------------------------
 * Los 4 avisos por correo del Menú A:
 *
 *   1. avisar_retiro()              -> cuando el pañolero entrega una herramienta
 *   2. enviar_avisos_cierre()       -> a las 16:30, a quien tenga algo sin devolver
 *   3. enviar_recordatorios()       -> un día antes del vencimiento y cuando vence
 *   4. enviar_codigo_verificacion() -> código de 6 números del primer ingreso
 *
 * Cada aviso anota en la base que ya se mandó, así no se repite:
 *   - prestamos.ultimo_aviso_cierre = fecha del último aviso de las 16:30
 *   - prestamos.aviso_enviado       = 0 ninguno, 1 recordatorio, 2 aviso de vencido
 *
 * REGLA DE ORO: el mail nunca frena el préstamo. Primero se guarda todo
 * en la base y después se intenta mandar el mail.
 */

require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/reglas_prestamos.php';
require_once __DIR__ . '/formato.php';

// =====================================================================
// 1. AVISO DE RETIRO
// =====================================================================

/**
 * Le avisa al alumno qué herramienta retiró y cuándo la tiene que devolver.
 * Se llama desde aprobar_prestamo(), DESPUÉS del commit.
 */
function avisar_retiro($pdo, $id_prestamo)
{
    $consulta = $pdo->prepare(
        "SELECT u.nombre, u.email, h.nombre AS herramienta, p.cantidad, p.fecha_devolucion_prevista
           FROM prestamos p
           JOIN usuarios u     ON u.id = p.id_usuario
           JOIN herramientas h ON h.id = p.id_herramienta
          WHERE p.id = ?"
    );
    $consulta->execute([$id_prestamo]);
    $p = $consulta->fetch();

    if (!$p) {
        return ['ok' => false, 'error' => 'No se encontró el préstamo.'];
    }

    $html = plantilla_correo('Retiraste una herramienta',
        '<p>Hola ' . e($p['nombre']) . ':</p>'
        . '<p>Retiraste <b>' . e($p['herramienta']) . '</b> (cantidad: ' . (int) $p['cantidad'] . ').</p>'
        . '<p>Tenés que devolverla el <b>' . fecha_corta($p['fecha_devolucion_prevista']) . '</b>, '
        . 'antes de las ' . HORA_CIERRE . ', que es cuando cierra el pañol.</p>'
    );

    return enviar_correo($p['email'], $p['nombre'], 'Retiraste: ' . $p['herramienta'], $html);
}

// =====================================================================
// 2. AVISO DE CIERRE (16:30)
// =====================================================================

/**
 * Manda UN mail por alumno con TODO lo que tiene sin devolver.
 * Solo funciona en el horario de la alerta (días hábiles, 16:30 a 17:30).
 * Cada préstamo se avisa una sola vez por día (ultimo_aviso_cierre).
 *
 * @return array ['enviados' => n, 'fallidos' => n]
 */
function enviar_avisos_cierre($pdo, $ahora = null)
{
    $resultado = ['enviados' => 0, 'fallidos' => 0];

    if (!alerta_de_cierre_activa($ahora)) {
        return $resultado; // no es el momento: no hacemos nada
    }
    $hoy = hoy($ahora);

    // PASO 1: préstamos retirados que HOY todavía no se avisaron.
    $consulta = $pdo->prepare(
        "SELECT p.id, p.id_usuario, p.cantidad, h.nombre AS herramienta,
                u.nombre, u.email
           FROM prestamos p
           JOIN usuarios u     ON u.id = p.id_usuario
           JOIN herramientas h ON h.id = p.id_herramienta
          WHERE p.estado = 'retirado'
            AND (p.ultimo_aviso_cierre IS NULL OR p.ultimo_aviso_cierre < ?)
          ORDER BY p.id_usuario"
    );
    $consulta->execute([$hoy]);

    // PASO 2: los agrupamos por alumno, así cada uno recibe un solo mail.
    $por_alumno = [];
    foreach ($consulta->fetchAll() as $fila) {
        $por_alumno[$fila['id_usuario']][] = $fila;
    }

    // PASO 3: un mail por alumno, con la lista de herramientas y cantidades.
    foreach ($por_alumno as $prestamos) {
        $alumno = $prestamos[0];
        $lista = '';
        $ids   = [];
        foreach ($prestamos as $p) {
            $lista .= '<li>' . e($p['herramienta']) . ' (cantidad: ' . (int) $p['cantidad'] . ')</li>';
            $ids[]  = (int) $p['id'];
        }

        $html = plantilla_correo('El pañol cierra en una hora',
            '<p>Hola ' . e($alumno['nombre']) . ':</p>'
            . '<p>Todavía tenés que devolver:</p><ul>' . $lista . '</ul>'
            . '<p><b>Acercate al pañol antes de las ' . HORA_CIERRE . '.</b> '
            . 'Lo que no se devuelva a tiempo queda vencido, y mientras tanto no vas a poder pedir otras herramientas.</p>'
        );

        $envio = enviar_correo($alumno['email'], $alumno['nombre'], 'Recordatorio: el pañol cierra a las ' . HORA_CIERRE, $html);

        // PASO 4: solo si salió, anotamos la fecha (si falló, se reintenta en la próxima pasada).
        if ($envio['ok']) {
            marcar_prestamos($pdo, $ids, 'ultimo_aviso_cierre', $hoy);
            $resultado['enviados']++;
        } else {
            $resultado['fallidos']++;
        }
    }

    return $resultado;
}

// =====================================================================
// 3. RECORDATORIO PREVIO Y AVISO DE VENCIDO (aviso_enviado)
// =====================================================================

/**
 * - Recordatorio (aviso_enviado 0 -> 1): préstamos que vencen MAÑANA.
 * - Vencido      (aviso_enviado 0 o 1 -> 2): préstamos que ya vencieron.
 *
 * @return array ['enviados' => n, 'fallidos' => n]
 */
function enviar_recordatorios($pdo, $ahora = null)
{
    $resultado = ['enviados' => 0, 'fallidos' => 0];
    $manana = date('Y-m-d', strtotime('+1 day', momento($ahora)));

    $sql = "SELECT p.id, p.cantidad, p.fecha_devolucion_prevista,
                   h.nombre AS herramienta, u.nombre, u.email
              FROM prestamos p
              JOIN usuarios u     ON u.id = p.id_usuario
              JOIN herramientas h ON h.id = p.id_herramienta
             WHERE p.estado = 'retirado' AND ";

    // --- a) Recordatorio un día antes ---
    $consulta = $pdo->prepare($sql . "p.aviso_enviado = 0 AND p.fecha_devolucion_prevista = ?");
    $consulta->execute([$manana]);

    foreach ($consulta->fetchAll() as $p) {
        $html = plantilla_correo('Mañana tenés que devolver una herramienta',
            '<p>Hola ' . e($p['nombre']) . ':</p>'
            . '<p>Te recordamos que mañana, <b>' . fecha_corta($p['fecha_devolucion_prevista']) . '</b>, '
            . 'tenés que devolver <b>' . e($p['herramienta']) . '</b> (cantidad: ' . (int) $p['cantidad'] . ') '
            . 'antes de las ' . HORA_CIERRE . '.</p>'
        );
        $envio = enviar_correo($p['email'], $p['nombre'], 'Mañana devolvés: ' . $p['herramienta'], $html);
        contar_envio($pdo, $envio, (int) $p['id'], 1, $resultado);
    }

    // --- b) Aviso de vencido (usa la misma regla que el resto del sistema) ---
    $consulta = $pdo->prepare($sql . "p.aviso_enviado < 2 AND p.fecha_devolucion_prevista < ?");
    $consulta->execute([fecha_limite_vencimiento($ahora)]);

    foreach ($consulta->fetchAll() as $p) {
        $html = plantilla_correo('Tenés una herramienta vencida',
            '<p>Hola ' . e($p['nombre']) . ':</p>'
            . '<p>Tenías que devolver <b>' . e($p['herramienta']) . '</b> (cantidad: ' . (int) $p['cantidad'] . ') '
            . 'el ' . fecha_corta($p['fecha_devolucion_prevista']) . ' y todavía no volvió al pañol.</p>'
            . '<p><b>Hasta que la devuelvas, no vas a poder pedir otras herramientas.</b></p>'
        );
        $envio = enviar_correo($p['email'], $p['nombre'], 'Préstamo vencido: ' . $p['herramienta'], $html);
        contar_envio($pdo, $envio, (int) $p['id'], 2, $resultado);
    }

    return $resultado;
}

/**
 * Si el mail salió, anota el nuevo valor de aviso_enviado y lo cuenta.
 */
function contar_envio($pdo, $envio, $id_prestamo, $nuevo_valor, &$resultado)
{
    if ($envio['ok']) {
        marcar_prestamos($pdo, [$id_prestamo], 'aviso_enviado', $nuevo_valor);
        $resultado['enviados']++;
    } else {
        $resultado['fallidos']++;
    }
}

/**
 * Actualiza una columna de varios préstamos a la vez.
 * $columna la elegimos nosotras (nunca viene del usuario),
 * por eso se puede escribir en el SQL.
 */
function marcar_prestamos($pdo, $ids, $columna, $valor)
{
    // Un "?" por cada id: (?, ?, ?)
    $signos = implode(', ', array_fill(0, count($ids), '?'));
    $consulta = $pdo->prepare("UPDATE prestamos SET $columna = ? WHERE id IN ($signos)");
    $consulta->execute(array_merge([$valor], $ids));
}

// =====================================================================
// 4. CÓDIGO DE VERIFICACIÓN (primer ingreso)
// =====================================================================

/**
 * Genera un código nuevo de 6 números, lo guarda encriptado con vencimiento
 * en 15 minutos y se lo manda al alumno.
 */
function enviar_codigo_verificacion($pdo, $id_usuario)
{
    $consulta = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
    $consulta->execute([$id_usuario]);
    $u = $consulta->fetch();

    if (!$u) {
        return ['ok' => false, 'error' => 'No se encontró el usuario.'];
    }

    // random_int() genera un número al azar seguro. str_pad completa con
    // ceros a la izquierda, así siempre tiene 6 dígitos (ej. "004215").
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // En la base guardamos el código encriptado, igual que una contraseña.
    $guardar = $pdo->prepare("UPDATE usuarios SET codigo_verificacion = ?, codigo_vence = ? WHERE id = ?");
    $guardar->execute([password_hash($codigo, PASSWORD_DEFAULT), date('Y-m-d H:i:s', strtotime('+15 minutes')), $id_usuario]);

    $html = plantilla_correo('Tu código de verificación',
        '<p>Hola ' . e($u['nombre']) . ':</p>'
        . '<p>Tu código para verificar el correo es:</p>'
        . '<p style="font-size:30px;font-weight:bold;letter-spacing:6px;color:#24295a">' . $codigo . '</p>'
        . '<p>Vence en 15 minutos. Si no fuiste vos, avisale al pañolero.</p>'
    );

    return enviar_correo($u['email'], $u['nombre'], 'Tu código de verificación: ' . $codigo, $html);
}

// =====================================================================
// TODO JUNTO (lo usa la tarea automática y el botón del panel)
// =====================================================================

/**
 * Corre los avisos automáticos: el de cierre (solo de 16:30 a 17:30)
 * y los recordatorios. Como cada aviso anota que ya se mandó,
 * se puede llamar muchas veces sin repetir mails.
 */
function ejecutar_avisos_automaticos($pdo, $ahora = null)
{
    $cierre        = enviar_avisos_cierre($pdo, $ahora);
    $recordatorios = enviar_recordatorios($pdo, $ahora);

    return [
        'enviados' => $cierre['enviados'] + $recordatorios['enviados'],
        'fallidos' => $cierre['fallidos'] + $recordatorios['fallidos'],
    ];
}
