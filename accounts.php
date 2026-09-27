<?php
$page_title = 'Cuentas';
require_once('includes/load.php');
page_require_level(1);

$accounts = find_all('accounts');

/* =====================================================
 * CARGAR TODOS LOS HORARIOS
 * ===================================================== */
$hours_by_account = [];

$sql = "SELECT id_account, day_week, opening_time
        FROM account_opening_hours
        ORDER BY id_account, day_week";

$result = $db->query($sql);

while ($row = $db->fetch_assoc($result)) {
    $hours_by_account[$row['id_account']][$row['day_week']] = $row['opening_time'];
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <h5 class="mb-3">
            <i class="ti ti-building me-2"></i> Listado de Cuentas
        </h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Apertura</th>
                <th>Logo</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($accounts as $a): ?>

                <tr>

                    <td><?php echo (int)$a['id']; ?></td>

                    <td><?php echo remove_junk($a['name']); ?></td>

                    <td><?php echo remove_junk($a['address']); ?></td>

                    <td><?php echo remove_junk($a['phone']); ?></td>

                    <td><?php echo remove_junk($a['mail']); ?></td>

                    <td>
                        <button type="button"
                                class="btn btn-primary btn-sm"
                                onclick='showHours(<?php echo json_encode($hours_by_account[$a["id"]] ?? []); ?>)'>
                            <i class="bi bi-clock me-1"></i> Ver horarios
                        </button>
                    </td>

                    <td>

                        <?php if(!empty($a['image']) && file_exists($a['image'])): ?>

                            <img src="<?php echo $a['image']; ?>"
                                 class="rounded-circle"
                                 style="width:45px;height:45px;object-fit:contain;">

                        <?php else: ?>

                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:45px;height:45px;">
                                <i class="ti ti-building text-muted"></i>
                            </div>

                        <?php endif; ?>

                    </td>

                    <td>

                        <div class="btn-group">

                            <a href="edit_acc.php?id=<?php echo (int)$a['id']; ?>"
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>

                            <?php if ($a['id'] != 1): ?>

                                <button onclick="deleteAccount(<?php echo (int)$a['id']; ?>)"
                                        class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash-fill"></i>
                                </button>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <!-- BOTÓN FLOTANTE -->
    <button class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 shadow"
            style="width:60px;height:60px;"
            data-bs-toggle="modal"
            data-bs-target="#addAccount">
        <i class="bi bi-plus fs-3"></i>
    </button>
</div>

<!-- ================= MODAL HORARIOS ================= -->
<div class="modal fade" id="hoursModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-clock me-2"></i>
                    Horarios de apertura
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <table class="table table-sm align-middle mb-0">

                    <tbody id="hoursBody"></tbody>

                </table>

            </div>

        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- ================= MODAL CREAR CUENTA ================= -->
<div class="modal fade" id="addAccount" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="post"
                  action="add_account.php"
                  autocomplete="off"
                  enctype="multipart/form-data">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-building me-1"></i> Crear Cuenta
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text"
                               name="address"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text"
                               name="phone"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email"
                               name="mail"
                               class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file"
                               name="image"
                               class="form-control"
                               accept="image/*">
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                            name="account"
                            class="btn btn-primary">
                        Guardar
                    </button>

                    <button type="button"
                            class="btn btn-danger"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
const dayNames = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo'
};

function showHours(hours){

    let html = '';

    for(let i=1;i<=7;i++){

        let time = hours[i] || '08:00:00';

        html += `
            <tr>
                <td><strong>${dayNames[i]}</strong></td>
                <td class="text-end">${time}</td>
            </tr>
        `;
    }

    document.getElementById('hoursBody').innerHTML = html;

    new bootstrap.Modal(document.getElementById('hoursModal')).show();
}

function deleteAccount(id) {
    Swal.fire({
        icon: "warning",
        title: "¿Estás seguro de eliminar esta cuenta?",
        text: "Se eliminarán todos los datos asociados",
        input: "password",
        inputPlaceholder: "Ingresa tu contraseña",
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
        },
        preConfirm: (value) => {
            if (!value) {
                Swal.showValidationMessage("La contraseña es obligatoria");
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = 'delete_account.php?id=' + id + '&pass=' + result.value;
        }
    });
}
</script>