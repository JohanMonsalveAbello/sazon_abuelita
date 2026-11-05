<?php
$roles = ['admin' => 'Administrador', 'mesero' => 'Mesero', 'cocina' => 'Cocina', 'cliente' => 'Cliente'];
$acciones = ['ver', 'crear', 'editar', 'aprobar', 'eliminar'];
?>
<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Leída de la tabla rol_permisos: es la misma que aplica el servidor en cada ruta.']) ?>
<section class="tarjeta p-0">
  <div class="table-responsive" tabindex="0" role="region" aria-label="Matriz de permisos">
    <table class="table table-sm align-middle mb-0 tabla-matriz">
      <thead>
        <tr>
          <th scope="col">Módulo</th><th scope="col">Acción</th>
          <?php foreach ($roles as $nombre): ?><th scope="col" class="text-center"><?= e($nombre) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($matriz as $modulo => $porAccion): ?>
          <?php foreach ($acciones as $accion): ?>
            <?php if (!isset($porAccion[$accion])) { continue; } ?>
            <tr>
              <th scope="row"><?= e(ucfirst($modulo)) ?></th>
              <td><?= e($accion) ?></td>
              <?php foreach ($roles as $codigo => $nombre): ?>
                <td class="text-center">
                  <?php if (isset($porAccion[$accion][$codigo])): ?>
                    <i class="bi bi-check-lg" aria-hidden="true"></i><span class="visually-hidden">Permitido</span>
                  <?php else: ?>
                    <span aria-hidden="true" class="text-muted">—</span><span class="visually-hidden">No permitido</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
