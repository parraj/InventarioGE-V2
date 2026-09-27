<?php
$page_title = 'Empleados';
require_once('includes/load.php');

// Verificar nivel de acceso
page_require_level(1);

// Obtener empleados del account actual
$employees = find_all_employees('employees');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">

        <!-- Título -->
        <h5 class="mb-3"><i class="bi bi-people me-2"></i>Listado de Empleados</h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">

            <thead>
            <tr>
                <th class="text-center" style="width: 10px;">#</th>
                <th>DNI</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Sede</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($employees as $employee): ?>

                <tr>

                    <td class="text-center">
                        <?php echo remove_junk($employee['id']); ?>
                    </td>

                    <td>
                        <?php echo remove_junk($employee['dni']); ?>
                    </td>

                    <td>
                        <?php echo remove_junk($employee['name']); ?>
                    </td>

                    <td>
                        <?php echo remove_junk($employee['phone']); ?>
                    </td>

                    <td>
                        <?php echo remove_junk($employee['mail']); ?>
                    </td>

                    <td>
                        <?php echo remove_junk($employee['account_name']); ?>
                    </td>

                    <td>
                        <?php echo read_date($employee['date']); ?>
                    </td>

                    <td class="text-center">

                        <div class="btn-group">

                            <a href="edit_employee.php?id=<?php echo (int)$employee['id']; ?>"
                               class="btn btn-primary btn-sm"
                               title="Editar">

                                <i class="bi bi-pencil-fill"></i>

                            </a>

                            <button onclick="delet(<?php echo (int)$employee['id']; ?>)"
                                    class="btn btn-danger btn-sm"
                                    title="Eliminar">

                                <i class="bi bi-trash-fill"></i>

                            </button>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>
            </tbody>

        </table>

    </div>

    <!-- Botón flotante -->
    <button id="btnFlotante"
            class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 d-flex align-items-center justify-content-center shadow"
            style="width:60px;height:60px;"
            data-bs-toggle="modal"
            data-bs-target="#addemployee">

        <i class="bi bi-plus fs-3"></i>

    </button>

</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Crear Empleado -->

<div class="modal fade" id="addemployee" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="post" action="add_employee.php" autocomplete="off">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Crear Empleado
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">DNI</label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-upc-scan"></i>
                            </span>

                            <input type="text"
                                   class="form-control"
                                   name="dni"
                                   placeholder="DNI">

                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>

                            <input type="text"
                                   class="form-control"
                                   name="name"
                                   placeholder="Nombre">

                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>

                            <input type="text"
                                   class="form-control"
                                   name="phone"
                                   placeholder="Teléfono">

                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>

                            <input type="email"
                                   class="form-control"
                                   name="mail"
                                   placeholder="Correo">

                        </div>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                            name="add_employee"
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

<!-- SweetAlert eliminar -->

<script>

function delet(id){

    Swal.fire({
        icon: "warning",
        title: "¿Eliminar este empleado?",
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

    }).then((result)=>{

        if(result.isConfirmed){

            window.location='delete_employee.php?id='+id;

        }

    });

}

</script>