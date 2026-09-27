<?php
$page_title = 'Ubicación';
require_once('includes/load.php');
page_require_level(2);

$all_locations = find_all_with_account('locations');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">

    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>


    <!-- TABLA LOCACIONES -->

    <div class="col-md-12 table-responsive">

        <h5 class="mb-3">
            <i class="ti ti-map-pin me-2"></i>Ubicaciones
        </h5>

        <table id="datatable" class="table text-nowrap align-middle mb-0">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Tipo Ubicación</th> <!-- NUEVO -->
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($all_locations as $location): ?>

                    <tr>

                        <td>
                            <?php echo (int) $location['id']; ?>
                        </td>

                        <td>
                            <?php echo remove_junk($location['name']); ?>
                        </td>

                        <td>
                            <?php echo remove_junk($location['address']); ?>
                        </td>

                        <!-- NUEVO -->
                        <td>
                            <?php if($location['location_type'] == 'Interna'): ?>
                                <span class="badge bg-primary">Interna</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Externa</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php echo read_date($location['date']); ?>
                        </td>

                        <td class="text-center">
                            <div class="btn-group">
                                <a href="edit_location.php?id=<?php echo (int)$location['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button onclick="delet(<?php echo (int)$location['id']; ?>)" class="btn btn-danger btn-sm" title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>


    <!-- BOTON FLOTANTE -->

    <button id="btnFlotante"
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
        style="width:60px;height:60px;" data-bs-toggle="modal" data-bs-target="#addlocation">

        <i class="bi bi-plus fs-3"></i>

    </button>

</div>

<?php include_once('layouts/footer.php'); ?>


<!-- MODAL CREAR LOCACION -->

<div class="modal fade" id="addlocation" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="post" action="add_location.php" autocomplete="off">

                <div class="modal-header">

                    <h5 class="modal-title">Crear Ubicación</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">


                    <!-- NOMBRE -->

                    <div class="mb-3">

                        <label class="form-label">Nombre</label>

                        <input type="text" class="form-control" name="name" placeholder="Nombre de la locación"
                            required>

                    </div>


                    <!-- DIRECCION -->

                    <div class="mb-3">

                        <label class="form-label">Dirección</label>

                        <input type="text" class="form-control" name="address" placeholder="Dirección" required>

                    </div>


                    <!-- NUEVO: TIPO DE UBICACION -->

                    <div class="mb-3">

                        <label class="form-label">Tipo de ubicación</label>

                        <select name="location_type" class="form-select" required>

                            <option value="Interna">Interna</option>

                            <option value="Externa">Externa</option>

                        </select>

                    </div>


                </div>

                <div class="modal-footer">

                    <button type="submit" name="add_location" class="btn btn-primary">

                        Guardar

                    </button>

                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">

                        Cancelar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- SweetAlert eliminar -->
<script>
function delet(id) {
    Swal.fire({
        icon: "warning",
        title: "¿Estás seguro de eliminar esta Ubicación?",
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
        if(result.isConfirmed) {
            window.location = 'delete_location.php?id=' + id;
        }
    });
}
</script>