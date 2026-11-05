<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => [['Nuevo usuario', '/usuarios/crear', 'bi-plus-lg']]]) ?>
<?= partial('filtros', ['campos' => [
    ['q', 'Buscar por nombre o correo', 'text'],
    ['rol', 'Rol', 'select', $roles],
    ['estado', 'Estado', 'select', ['activo' => 'Activo', 'inactivo' => 'Inactivo']],
]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-people', 'titulo' => 'No hay usuarios con esos filtros']) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col"><?= orden_columna('nombre', 'Nombre') ?></th>
        <th scope="col"><?= orden_columna('correo', 'Correo') ?></th>
        <th scope="col"><?= orden_columna('rol', 'Rol') ?></th>
        <th scope="col">Estado</th>
        <th scope="col"><span class="visually-hidden">Acciones</span></th>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $u): ?>
        <tr>
          <td data-etiqueta="Nombre"><?= e($u['nombre']) ?></td>
          <td data-etiqueta="Correo"><?= e($u['correo']) ?></td>
          <td data-etiqueta="Rol"><?= e($u['rol_nombre']) ?></td>
          <td data-etiqueta="Estado"><?= insignia_estado((int) $u['activo'] === 1 ? 'activo' : 'inactivo') ?></td>
          <td class="acciones-fila">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/usuarios/' . $u['id'] . '/editar')) ?>">Editar</a>
            <?php if ($u['id'] !== usuario()['id']): ?>
            <form method="post" action="<?= e(url('/usuarios/' . $u['id'] . '/estado')) ?>" <?= (int) $u['activo'] === 1 ? 'data-confirmar="' . e('¿Desactivar a ' . $u['nombre'] . '? Ya no podrá iniciar sesión.') . '"' : '' ?>>
              <?= csrf_field() ?>
              <button class="btn btn-sm <?= (int) $u['activo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><?= (int) $u['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= partial('paginacion', ['p' => $lista]) ?>
  <?php endif; ?>
</section>
