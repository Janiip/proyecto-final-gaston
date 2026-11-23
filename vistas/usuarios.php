<?php
/**
 * vistas/usuarios.php
 * ------------------------------------------------------------
 * Cuentas del sistema (mockup: usuarios).
 * "Agregar" y "Editar" llevan a usuario_formulario.php (RF13).
 */

require_once __DIR__ . '/../config/sesion.php';
require_once __DIR__ . '/../config/conexion.php';
exigir_rol('panolero');

$usuarios = conectar()->query(
    "SELECT id, nombre, apellido, email, rol, activo, correo_verificado, password_cambiada
       FROM usuarios
      ORDER BY rol DESC, apellido, nombre"
)->fetchAll();

$titulo = 'Usuarios';
$pagina_activa = 'usuarios.php';
require __DIR__ . '/inicio_pagina.php';
?>

<div class="encabezado">
    <div>
        <p class="categoria">Administración</p>
        <h1>Usuarios</h1>
        <p>Consultá las cuentas habilitadas para usar el sistema.</p>
    </div>
    <a href="usuario_formulario.php" class="boton boton-principal">+ Agregar usuario</a>
</div>

<section class="tarjeta">
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acción</th></tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= e($u['nombre'] . ' ' . $u['apellido']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= $u['rol'] === 'panolero' ? 'Pañolero' : 'Alumno' ?></td>
                        <td>
                            <?php if (!$u['activo']): ?>
                                <span class="badge badge-inactivo">Inactivo</span>
                            <?php else: ?>
                                <span class="badge badge-activo">Activo</span>
                            <?php endif; ?>

                            <?php if (!$u['correo_verificado'] || !$u['password_cambiada']): ?>
                                <!-- Todavía no hizo el primer ingreso -->
                                <span class="badge badge-pendiente">Primer ingreso pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="usuario_formulario.php?id=<?= (int) $u['id'] ?>" class="boton boton-secundario boton-chico">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/fin_pagina.php'; ?>
