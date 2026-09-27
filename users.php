<?php
$page_title = 'Usuarios';
require_once('includes/load.php');
// Verificar que el usuario tiene nivel de acceso 1
page_require_level(1);

// Obtener todos los usuarios y los grupos
$all_users = find_all_user();
$groups = find_all('user_groups');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <!-- Título de la tabla -->
        <h5 class="mb-3"><i class="bi bi-person me-2"></i>Listado de Usuarios</h5>

        <!-- Tabla moderna -->
        <table id="datatable" class="table text-nowrap mb-0 align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol de usuario</th>
                <th>Estado</th>
                <th>Último login</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($all_users as $a_user): ?>
                <tr>
                    <td><?php echo remove_junk($a_user['id']); ?></td>
                    <td><?php echo remove_junk(ucwords($a_user['name'])); ?></td>
                    <td><?php echo remove_junk($a_user['username']); ?></td>
                    <td><?php echo remove_junk(ucwords($a_user['group_name'])); ?></td>
                    <td class="text-center">
                        <?php if($a_user['status'] === '1'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif;?>
                    </td>
                    <td><?php echo read_date($a_user['last_login']); ?></td>
                    <td class="text-center">
                        <div class="btn-group" role="group">
                            <a href="edit_user.php?id=<?php echo (int)$a_user['id'];?>" class="btn btn-primary btn-sm" title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button onclick="delet(<?php echo (int)$a_user['id']; ?>)" class="btn btn-danger btn-sm" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>

    <!-- Botón flotante para abrir modal -->
    <button id="btnFlotante"
            class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
            style="width: 60px; height: 60px; transition: all 0.2s;"
            data-bs-toggle="modal"
            data-bs-target="#adduser">
        <i class="bi bi-plus fs-3"></i>
    </button>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Add User -->
<div class="modal fade" id="adduser" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_user.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserLabel">Crear Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <!-- Nombre -->
                    <div class="mb-3">
                        <label for="full-name" class="form-label">Nombre completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" name="full-name" placeholder="Nombre completo" required>
                        </div>
                    </div>

                    <!-- Usuario -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                            <input type="text" class="form-control" name="username" placeholder="Username" required>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label for="level" class="form-label">Rol de usuario</label>
                        <select class="form-select" name="level" required>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['group_level']; ?>">
                                    <?php echo ucwords($group['group_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_user" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert eliminar usuario -->
<script>
    function delet(id){
        Swal.fire({
            icon: "warning",
            title: "¿Estás seguro de eliminar este usuario?",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#0d6efd",
            cancelButtonColor: "#dc3545",
            width: '350px',
            customClass: {
                title: 'swal-title-sm',
                content: 'swal-content-sm',
                confirmButton: 'swal-btn-sm',
                cancelButton: 'swal-btn-sm'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'delete_user.php?id=' + id;
            }
        });
    }
</script>
