<?php
/**
 * reglas_prestamos.php
 * ------------------------------------------------------------
 * TODAS las reglas de negocio de los préstamos (Menú B) en un solo lugar:
 *
 *   - Cuándo un préstamo está vencido.
 *   - Si un alumno tiene deuda (y por eso no puede pedir).
 *   - Qué herramientas faltan devolver (alerta de las 16:30).
 *   - Los cambios de estado: solicitado -> retirado -> devuelto,
 *     o solicitado -> rechazado. Cada uno mueve el stock como corresponde.
 *
 * Las pantallas (vistas/) y las acciones (login-acciones/) llaman a estas funciones, así la
 * regla se escribe una sola vez y todas las pantallas dicen lo mismo.
 */

require_once __DIR__ . '/horario.php';

// =====================================================================
// 1. FECHAS Y VENCIMIENTO
// =====================================================================

/**
 * Fecha de hoy en formato de la base (2026-11-21).
 *
 * Usamos la fecha de PHP (que está en hora de Argentina) y se la pasamos
 * a MySQL como dato, en vez de usar CURDATE(). Así no dependemos de cómo
 * esté configurado el reloj de MySQL.
 */
function hoy($ahora = null)
{
    return date('Y-m-d', momento($ahora));
}

/**
 * Fecha límite para saber si un préstamo está vencido.
 *
 * Regla: un préstamo vence a las 17:30 (cierre del pañol) del día en que
 * había que devolverlo.
 *   - Antes de las 17:30: vencido si su fecha de devolución es ANTERIOR a hoy.
 *   - Desde las 17:30:   también vence el de hoy, o sea, vencido si su fecha
 *                         es anterior a MAÑANA.
 *
 * Por eso devolvemos "hoy" o "mañana", y en la consulta preguntamos
 *   fecha_devolucion_prevista < (esta fecha)
 */
function fecha_limite_vencimiento($ahora = null)
{
    $ahora = momento($ahora);
    if (date('H:i', $ahora) >= HORA_CIERRE) {
        return date('Y-m-d', strtotime('+1 day', $ahora)); // mañana
    }
    return date('Y-m-d', $ahora); // hoy
}

/**
 * ¿Este préstamo está vencido? (para pintar una fila o un badge)
 * Solo puede vencer lo que el alumno tiene en su poder (estado "retirado").
 */
function esta_vencido($estado, $fecha_prevista, $ahora = null)
{
    if ($estado !== 'retirado' || empty($fecha_prevista)) {
        return false;
    }
    return $fecha_prevista < fecha_limite_vencimiento($ahora);
}

// =====================================================================
// 2. DEUDA Y BLOQUEO (RF14)
// =====================================================================

/**
 * Cuenta cuántos préstamos vencidos tiene un alumno.
 * Si es 1 o más, el alumno tiene deuda y no puede pedir nada nuevo.
 *
 * El bloqueo NO se guarda en la base: se calcula cada vez. Por eso,
 * apenas devuelve lo que debía, el bloqueo se levanta solo.
 */
function contar_deudas($pdo, $id_usuario, $ahora = null)
{
    $consulta = $pdo->prepare(
        "SELECT COUNT(*) FROM prestamos
          WHERE id_usuario = ?
            AND estado = 'retirado'
            AND fecha_devolucion_prevista < ?"
    );
    $consulta->execute([$id_usuario, fecha_limite_vencimiento($ahora)]);
    return (int) $consulta->fetchColumn();
}

function alumno_tiene_deuda($pdo, $id_usuario, $ahora = null)
{
    return contar_deudas($pdo, $id_usuario, $ahora) > 0;
}

// =====================================================================
// 3. ALERTA DE CIERRE (RF11)
// =====================================================================

/**
 * Lista de herramientas retiradas que todavía no volvieron.
 * Si se pasa $id_usuario, solo las de ese alumno (para su panel).
 * Si no, las de todos (para el panel del pañolero).
 */
function herramientas_sin_devolver($pdo, $id_usuario = null)
{
    $sql = "SELECT p.id, p.cantidad, p.fecha_devolucion_prevista,
                   h.nombre AS herramienta,
                   u.nombre, u.apellido, u.curso, u.email
              FROM prestamos p
              JOIN herramientas h ON h.id = p.id_herramienta
              JOIN usuarios u     ON u.id = p.id_usuario
             WHERE p.estado = 'retirado'";
    $datos = [];

    if ($id_usuario !== null) {
        $sql .= " AND p.id_usuario = ?";
        $datos[] = $id_usuario;
    }
    $sql .= " ORDER BY u.curso, u.apellido, h.nombre";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($datos);
    return $consulta->fetchAll();
}

/**
 * Datos para la alerta roja: solo devuelve algo desde las 16:30
 * hasta el cierre (días hábiles). Fuera de ese rato, una lista vacía.
 */
function datos_alerta_cierre($pdo, $id_usuario = null, $ahora = null)
{
    if (!alerta_de_cierre_activa($ahora)) {
        return [];
    }
    return herramientas_sin_devolver($pdo, $id_usuario);
}

// =====================================================================
// 4. CAMBIOS DE ESTADO (acciones del pañolero)
// ---------------------------------------------------------------------
// Las tres funciones devuelven un array:
//   ['ok' => true/false, 'mensaje' => 'texto para mostrar']
//
// Usan una TRANSACCIÓN: o se hacen todos los cambios, o ninguno.
// Ejemplo: si se resta el stock pero falla el cambio de estado,
// rollBack() deshace la resta y la base no queda a medias.
// =====================================================================

/**
 * Busca un préstamo y lo "bloquea" hasta terminar la transacción
 * (FOR UPDATE), para que dos clics al mismo tiempo no lo procesen dos veces.
 */
function buscar_prestamo_para_cambiar($pdo, $id_prestamo)
{
    $consulta = $pdo->prepare(
        "SELECT p.id, p.id_usuario, p.id_herramienta, p.cantidad, p.estado,
                p.fecha_devolucion_prevista, h.nombre AS herramienta
           FROM prestamos p
           JOIN herramientas h ON h.id = p.id_herramienta
          WHERE p.id = ?
          FOR UPDATE"
    );
    $consulta->execute([$id_prestamo]);
    return $consulta->fetch();
}

/**
 * APROBAR = el pañolero entrega la herramienta: solicitado -> retirado.
 * Recién acá se descuenta el stock (al pedir solo se reserva la intención).
 */
function aprobar_prestamo($pdo, $id_prestamo, $ahora = null)
{
    try {
        $pdo->beginTransaction();

        // PASO 1: el préstamo tiene que existir y estar "solicitado".
        $p = buscar_prestamo_para_cambiar($pdo, $id_prestamo);
        if (!$p) {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'El préstamo no existe.'];
        }
        if ($p['estado'] !== 'solicitado') {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'Solo se puede entregar una solicitud pendiente.'];
        }

        // PASO 2: descontamos el stock SOLO si alcanza.
        // El "AND cantidad_disponible >= ?" hace que MySQL no reste si no hay
        // suficiente. rowCount() dice cuántas filas cambió: 0 = no alcanzaba.
        $resta = $pdo->prepare(
            "UPDATE herramientas
                SET cantidad_disponible = cantidad_disponible - ?
              WHERE id = ? AND activa = 1 AND cantidad_disponible >= ?"
        );
        $resta->execute([$p['cantidad'], $p['id_herramienta'], $p['cantidad']]);

        if ($resta->rowCount() === 0) {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'No hay stock suficiente de ' . $p['herramienta'] . ' para entregar.'];
        }

        // PASO 3: pasamos el préstamo a "retirado".
        // Si la fecha de devolución que eligió el alumno ya pasó
        // (porque la solicitud se aprobó tarde), la movemos a hoy,
        // así no queda vencido en el momento de recibir la herramienta.
        $fecha_devolucion = max($p['fecha_devolucion_prevista'], hoy($ahora));

        $cambio = $pdo->prepare(
            "UPDATE prestamos
                SET estado = 'retirado', fecha_devolucion_prevista = ?
              WHERE id = ? AND estado = 'solicitado'"
        );
        $cambio->execute([$fecha_devolucion, $id_prestamo]);

        $pdo->commit(); // guardamos los dos cambios juntos

        // PASO 4 (Menú A): avisamos al alumno por mail.
        // Va DESPUÉS del commit: si el mail falla, el préstamo ya quedó guardado.
        // El try propio hace que, pase lo que pase con el mail,
        // el pañolero vea "entregada" (porque ya está guardada).
        require_once __DIR__ . '/avisos.php';
        try {
            $aviso = avisar_retiro($pdo, $id_prestamo);
        } catch (Exception $e) {
            error_log('Error en el aviso de retiro: ' . $e->getMessage());
            $aviso = ['ok' => false, 'error' => 'error inesperado'];
        }

        $mensaje = 'Herramienta entregada: ' . $p['herramienta'] . '.';
        $mensaje .= $aviso['ok']
            ? ' Se le avisó por mail al alumno.'
            : ' Atención: no se pudo enviar el mail al alumno. ' . $aviso['error'];

        return ['ok' => true, 'mensaje' => $mensaje];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // deshacemos todo lo que se haya hecho
        }
        error_log('Error al aprobar préstamo: ' . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'No se pudo entregar. Probá de nuevo.'];
    }
}

/**
 * RECHAZAR: solicitado -> rechazado.
 * El stock NO cambia, porque en una solicitud todavía no se había descontado
 * nada (el stock se descuenta recién al entregar).
 */
function rechazar_prestamo($pdo, $id_prestamo)
{
    // Un solo UPDATE: el "AND estado = 'solicitado'" impide rechazar algo
    // que ya se entregó o se devolvió.
    $cambio = $pdo->prepare(
        "UPDATE prestamos SET estado = 'rechazado'
          WHERE id = ? AND estado = 'solicitado'"
    );
    $cambio->execute([$id_prestamo]);

    if ($cambio->rowCount() === 0) {
        return ['ok' => false, 'mensaje' => 'Solo se puede rechazar una solicitud pendiente.'];
    }
    return ['ok' => true, 'mensaje' => 'Solicitud rechazada.'];
}

/**
 * DEVOLVER: retirado -> devuelto.
 * Suma el stock, guarda la fecha real, avisa si hubo atraso
 * y si el alumno quedó sin deudas (bloqueo levantado).
 */
function devolver_prestamo($pdo, $id_prestamo, $ahora = null)
{
    try {
        $pdo->beginTransaction();

        // PASO 1: solo se puede devolver lo que se retiró.
        $p = buscar_prestamo_para_cambiar($pdo, $id_prestamo);
        if (!$p) {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'El préstamo no existe.'];
        }
        if ($p['estado'] !== 'retirado') {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'Solo se puede devolver una herramienta que fue retirada.'];
        }

        // PASO 2: ¿hubo atraso? Lo calculamos ANTES de cambiar el estado.
        $con_atraso = esta_vencido($p['estado'], $p['fecha_devolucion_prevista'], $ahora);

        // PASO 3: el préstamo pasa a "devuelto" con la fecha de hoy.
        $cambio = $pdo->prepare(
            "UPDATE prestamos
                SET estado = 'devuelto', fecha_devolucion_real = ?
              WHERE id = ? AND estado = 'retirado'"
        );
        $cambio->execute([hoy($ahora), $id_prestamo]);

        // PASO 4: sumamos el stock, sin pasarnos del total
        // (por si alguien tocó el número a mano en la base).
        $suma = $pdo->prepare(
            "UPDATE herramientas
                SET cantidad_disponible = CASE
                        WHEN cantidad_disponible + ? > cantidad_total THEN cantidad_total
                        ELSE cantidad_disponible + ?
                    END
              WHERE id = ?"
        );
        $suma->execute([$p['cantidad'], $p['cantidad'], $p['id_herramienta']]);

        $pdo->commit();

        // PASO 5: armamos el mensaje para el pañolero.
        $mensaje = 'Devolución registrada: ' . $p['herramienta'] . '.';

        if ($con_atraso) {
            $dias = (int) ((strtotime(hoy($ahora)) - strtotime($p['fecha_devolucion_prevista'])) / 86400);
            $mensaje .= $dias > 0
                ? " Se devolvió con $dias día(s) de atraso."
                : ' Se devolvió después del cierre de las ' . HORA_CIERRE . '.';

            // ¿Le queda alguna otra deuda? Si no, ya puede volver a pedir.
            $mensaje .= contar_deudas($pdo, $p['id_usuario'], $ahora) === 0
                ? ' El alumno ya no tiene deudas: se levantó el bloqueo.'
                : ' El alumno todavía tiene otros préstamos vencidos.';
        }

        return ['ok' => true, 'mensaje' => $mensaje];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error al devolver préstamo: ' . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'No se pudo registrar la devolución. Probá de nuevo.'];
    }
}

// =====================================================================
// 5. VALIDACIÓN DE UN PEDIDO (la usan el alumno y el pañolero)
// =====================================================================

/**
 * Revisa todas las reglas de un pedido nuevo. La usan:
 *   - login-acciones/guardar_solicitud.php        (el alumno pide)
 *   - login-acciones/guardar_prestamo_directo.php (el pañolero registra)
 * Así las dos formas de pedir siguen EXACTAMENTE las mismas reglas.
 *
 * @param array $datos id_herramienta, cantidad, fecha_devolucion_prevista, justificacion
 * @return array ['error' => texto o null, 'herramienta' => fila, 'justificacion' => texto o null]
 */
function validar_pedido($pdo, $id_usuario, $datos, $ahora = null)
{
    require_once __DIR__ . '/validaciones.php';

    $id_herramienta = (int) $datos['id_herramienta'];
    $cantidad       = (int) $datos['cantidad'];
    $fecha          = trim($datos['fecha_devolucion_prevista']);
    $justificacion  = trim($datos['justificacion']);

    $error = function ($texto) {
        return ['error' => $texto, 'herramienta' => null, 'justificacion' => null];
    };

    // 1. Bloqueo por deuda (RF14): si debe algo, no seguimos.
    if (alumno_tiene_deuda($pdo, $id_usuario, $ahora)) {
        return $error('El alumno tiene una herramienta vencida. Tiene que devolverla para poder pedir otra.');
    }

    // 2. Datos básicos
    if ($id_herramienta <= 0) {
        return $error('Elegí una herramienta.');
    }
    if ($cantidad < 1) {
        return $error('La cantidad tiene que ser 1 o más.');
    }
    if (!es_fecha_valida($fecha)) {
        return $error('La fecha de devolución no es válida.');
    }
    if ($fecha < hoy($ahora)) { // las fechas AAAA-MM-DD se comparan como texto
        return $error('La fecha de devolución no puede ser anterior a hoy.');
    }

    // 3. Horario (RF5): fuera de horario, justificación obligatoria.
    if (fuera_de_horario($ahora)) {
        if (mb_strlen($justificacion, 'UTF-8') < 10) {
            return $error('Es fuera de horario: hay que explicar por qué y para qué se necesita (al menos 10 letras).');
        }
    } else {
        $justificacion = null; // en horario no se guarda
    }

    // 4. La herramienta existe, está activa y alcanza el stock.
    $consulta = $pdo->prepare("SELECT id, nombre, cantidad_disponible FROM herramientas WHERE id = ? AND activa = 1");
    $consulta->execute([$id_herramienta]);
    $herramienta = $consulta->fetch();

    if (!$herramienta) {
        return $error('Esa herramienta no existe o fue dada de baja.');
    }
    if ($cantidad > (int) $herramienta['cantidad_disponible']) {
        return $error('No hay suficientes unidades disponibles (quedan ' . (int) $herramienta['cantidad_disponible'] . ').');
    }

    return ['error' => null, 'herramienta' => $herramienta, 'justificacion' => $justificacion];
}

// =====================================================================
// 6. PRÉSTAMO DIRECTO (el pañolero entrega sin solicitud previa)
// =====================================================================

/**
 * El pañolero registra y entrega en un solo paso: el préstamo se guarda
 * directamente como "retirado" y se descuenta el stock.
 * Antes hay que llamar a validar_pedido().
 */
function registrar_prestamo_directo($pdo, $id_usuario, $id_herramienta, $cantidad, $fecha, $justificacion)
{
    try {
        $pdo->beginTransaction();

        // PASO 1: descontamos el stock SOLO si alcanza (igual que al aprobar).
        // Se vuelve a controlar acá por si otro préstamo se llevó las
        // unidades entre la validación y este momento.
        $resta = $pdo->prepare(
            "UPDATE herramientas
                SET cantidad_disponible = cantidad_disponible - ?
              WHERE id = ? AND activa = 1 AND cantidad_disponible >= ?"
        );
        $resta->execute([$cantidad, $id_herramienta, $cantidad]);

        if ($resta->rowCount() === 0) {
            $pdo->rollBack();
            return ['ok' => false, 'mensaje' => 'Ya no hay stock suficiente de esa herramienta.'];
        }

        // PASO 2: guardamos el préstamo directamente como "retirado".
        $insertar = $pdo->prepare(
            "INSERT INTO prestamos
                (id_usuario, id_herramienta, cantidad, fecha_pedido, fecha_devolucion_prevista, estado, justificacion)
             VALUES (?, ?, ?, ?, ?, 'retirado', ?)"
        );
        $insertar->execute([$id_usuario, $id_herramienta, $cantidad, date('Y-m-d H:i:s'), $fecha, $justificacion]);
        $id_prestamo = (int) $pdo->lastInsertId();

        $pdo->commit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error en préstamo directo: ' . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'No se pudo registrar el préstamo. Probá de nuevo.'];
    }

    // PASO 3 (Menú A): avisamos al alumno, DESPUÉS de guardar.
    require_once __DIR__ . '/avisos.php';
    try {
        $aviso = avisar_retiro($pdo, $id_prestamo);
    } catch (Exception $e) {
        $aviso = ['ok' => false, 'error' => 'error inesperado'];
    }

    return [
        'ok' => true,
        'mensaje' => 'Préstamo registrado y entregado.' . ($aviso['ok']
            ? ' Se le avisó por mail al alumno.'
            : ' Atención: no se pudo enviar el mail al alumno.'),
    ];
}

/**
 * Cuántas unidades de una herramienta están prestadas ahora (retiradas).
 * Se usa al editar la cantidad total de una herramienta.
 */
function unidades_en_uso($pdo, $id_herramienta)
{
    $consulta = $pdo->prepare(
        "SELECT COALESCE(SUM(cantidad), 0) FROM prestamos
          WHERE id_herramienta = ? AND estado = 'retirado'"
    );
    $consulta->execute([$id_herramienta]);
    return (int) $consulta->fetchColumn();
}
