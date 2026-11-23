<?php
/**
 * vistas/herramientas.php
 * ------------------------------------------------------------
 * Inventario del pañol (mockup: herramientas).
 * "Agregar" y "Editar" llevan a herramienta_formulario.php (CRUD de Jana).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
exigir_rol('panolero');

$herramientas = conectar()->query(
    "SELECT id, nombre, categoria, cantidad_disponible, activa
       FROM herramientas
      ORDER BY activa DESC, nombre"
)->fetchAll();

$titulo = 'Herramientas';
$pagina_activa = 'herramientas.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Inventario</p>
        <h1>Herramientas</h1>
        <p>Consultá y mantené el listado de herramientas del pañol.</p>
    </div>
    <a href="herramienta_formulario.php" class="boton boton-principal">+ Agregar herramienta</a>
</div>

<section class="tarjeta">
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr><th>Herramienta</th><th>Categoría</th><th>Disponibles</th><th>Estado</th><th>Acción</th></tr>
            </thead>
            <tbody>
                <?php if (!$herramientas): ?>
                    <tr><td colspan="5" class="tabla-vacia">Todavía no hay herramientas cargadas.</td></tr>
                <?php endif; ?>

                <?php foreach ($herramientas as $h): ?>
                    <tr>
                        <td><?= e($h['nombre']) ?></td>
                        <td><?= e($h['categoria']) ?></td>
                        <td><?= (int) $h['cantidad_disponible'] ?></td>
                        <td>
                            <?php if (!$h['activa']): ?>
                                <span class="badge badge-inactivo">Dada de baja</span>
                            <?php elseif ($h['cantidad_disponible'] > 0): ?>
                                <span class="badge badge-disponible">Disponible</span>
                            <?php else: ?>
                                <span class="badge badge-sin-stock">Sin stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="herramienta_formulario.php?id=<?= (int) $h['id'] ?>" class="boton boton-secundario boton-chico">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
