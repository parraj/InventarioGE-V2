<?php
// Título de la página
$page_title = 'Categorías';

// Cargar funciones y configuración del sistema
require_once('includes/load.php');

// Verificar que el usuario tiene nivel de acceso 1 (Administrador)
page_require_level(2);

// Obtener todas las categorías con conteo de productos activos y stock total
$all_categories = find_all_categories_with_products_summary();

// Procesar creación de nueva categoría
if (isset($_POST['add_cat'])) {
    $req_field = array('categorie-name');
    validate_fields($req_field);

    $cat_name = remove_junk($db->escape($_POST['categorie-name']));
    $account = $db->escape($_SESSION['account']);

    if (empty($errors)) {
        $sql = "INSERT INTO categories (name, account) VALUES ('{$cat_name}','{$account}')";
        if ($db->query($sql)) {
            $session->msg("s", "Categoría creada exitosamente.");
            redirect('categorie.php', false);
        } else {
            $session->msg("d", "Ocurrió un error, no se pudo crear la categoría.");
            redirect('categorie.php', false);
        }
    } else {
        $session->msg("d", $errors);
        redirect('categorie.php', false);
    }
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <!-- Mostrar mensajes de alerta -->
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row">

    <!-- Formulario para agregar categoría -->
    <div class="col-md-4">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Agregar Categoría</h5>
        <form method="post" action="categorie.php" autocomplete="off">
            <div class="mb-3">
                <input type="text" class="form-control" name="categorie-name" placeholder="Nombre de la categoría" required>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" name="add_cat" class="btn btn-primary me-2">Agregar</button>
                <button type="reset" class="btn btn-danger">Limpiar</button>
            </div>
        </form>
    </div>

    <!-- Lista de categorías -->
    <div class="col-md-8">
        <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Listado de Categorías</h5>
        <div class="table-responsive">
            <table id="datatable" class="table text-nowrap mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Categorías</th>
                        <th class="text-center">Productos</th>
                        <th class="text-center">Stock Total</th>
                        <th class="text-center" style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_categories as $cat): ?>
                        <tr>
                            <td><?php echo remove_junk(ucfirst($cat['id'])); ?></td>

                            <td><?php echo remove_junk(ucfirst($cat['name'])); ?></td>

                            <td class="text-center"><?php echo (int) $cat['total_products']; ?></td>

                            <td class="text-center"><?php echo (int) $cat['total_stock']; ?></td>

                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="product_by_category.php?id=<?php echo (int) $cat['id']; ?>"
                                        class="btn btn-success btn-sm" title="Ver productos">
                                        <i class="bi bi-box-seam"></i>
                                    </a>

                                    <a href="edit_categorie.php?id=<?php echo (int) $cat['id']; ?>"
                                        class="btn btn-primary btn-sm" title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <button onclick="delet(<?php echo (int) $cat['id']; ?>)"
                                        class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- SweetAlert para eliminar categoría -->
<script>
    function delet(id) {
        Swal.fire({
            icon: "warning",
            title: "¿Estás seguro de eliminar esta categoría?",
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
                window.location = 'delete_categorie.php?id=' + id;
            }
        });
    }
</script>