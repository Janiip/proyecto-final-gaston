<?php
/**
 * vistas/herramienta_formulario.php
 * ------------------------------------------------------------
 * Formulario para AGREGAR una herramienta (sin ?id)
 * o EDITARLA (con ?id=5). Desde acá también se da de baja o se reactiva.
 * Lo procesa login-acciones/guardar_herramienta.php (RF7).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/reglas_prestamos.php';
exigir_rol('panolero');

$pdo = conectar();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Valores del formulario: vacíos para una herramienta nueva...
$herramienta = ['nombre' => '', 'categoria' => '', 'cantidad_total' => 1, 'cantidad_disponible' => 0, 'activa' => 1];
$en_uso = 0;

// ...o los de la base si estamos editando.
if ($id > 0) {
    $consulta = $pdo->prepare("SELECT * FROM herramientas WHERE id = ?");
    $consulta->execute([$id]);
    $herramienta = $consulta->fetch();

    if (!$herramienta) {
        $_SESSION['error_form'] = 'Esa herramienta no existe.';
        header('Location: herramientas.php');
        exit;
    }
    $en_uso = unidades_en_uso($pdo, $id);
}

// Si el controlador nos devolvió por un error, mostramos lo que había escrito.
if (isset($_SESSION['form_previo'])) {
    $herramienta = array_merge($herramienta, $_SESSION['form_previo']);
    unset($_SESSION['form_previo']);
}

$error = isset($_SESSION['error_form']) ? $_SESSION['error_form'] : '';
unset($_SESSION['error_form']);

// Categorías que ya existen, para sugerirlas al escribir (sin repetirse).
$categorias = $pdo->query("SELECT DISTINCT categoria FROM herramientas ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

$titulo = $id > 0 ? 'Editar herramienta' : 'Agregar herramienta';
$pagina_activa = 'herramientas.php'; // en el menú queda resaltado "Herramientas"
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Inventario</p>
        <h1><?= $titulo ?></h1>
        <p><?= $id > 0 ? 'Modificá los datos de la herramienta.' : 'Cargá una herramienta nueva del pañol.' ?></p>
    </div>
    <a href="herramientas.php" class="boton boton-secundario">&larr; Volver</a>
</div>

<section class="tarjeta formulario">

    <?php if ($error): ?><div class="mensaje mensaje-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($id > 0): ?>
        <!-- Estado actual del stock: ayuda a no cargar un total imposible -->
        <div class="info">
            Disponibles ahora: <b><?= (int) $herramienta['cantidad_disponible'] ?></b>
            · Prestadas ahora: <b><?= $en_uso ?></b>.
            Si cambiás el total, las disponibles se calculan solas (total menos prestadas).
        </div>
    <?php endif; ?>

    <form action="../login-acciones/guardar_herramienta.php" method="POST">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" required
                   value="<?= e($herramienta['nombre']) ?>" placeholder="Ej: Taladro percutor">
        </div>

        <div class="campo campo-corto">
            <label for="categoria">Categoría</label>
            <!-- list="..." muestra las categorías que ya existen como sugerencias -->
            <input type="text" id="categoria" name="categoria" maxlength="50" required list="lista-categorias"
                   value="<?= e($herramienta['categoria']) ?>" placeholder="Ej: Eléctricas">
            <datalist id="lista-categorias">
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= e($c) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="campo campo-corto">
            <label for="cantidad_total">Cantidad total en el pañol</label>
            <input type="number" id="cantidad_total" name="cantidad_total" required
                   min="<?= max(1, $en_uso) ?>" value="<?= (int) $herramienta['cantidad_total'] ?>">
        </div>

        <button type="submit" class="boton boton-principal boton-ancho">
            <?= $id > 0 ? 'Guardar cambios' : 'Agregar herramienta' ?>
        </button>
    </form>

    <?php if ($id > 0): ?>
        <!-- Baja o reactivación: formulario aparte, con su propia acción -->
        <form action="../login-acciones/guardar_herramienta.php" method="POST" class="zona-baja">
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($herramienta['activa']): ?>
                <p>Dar de baja la oculta para nuevos pedidos. No se borra: su historial de préstamos se conserva.</p>
                <button name="accion" value="baja" class="boton boton-secundario">Dar de baja</button>
            <?php else: ?>
                <p>Esta herramienta está dada de baja. Si la reactivás, se puede volver a pedir.</p>
                <button name="accion" value="reactivar" class="boton boton-suave">Reactivar</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
