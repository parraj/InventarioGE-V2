<?php
$page_title = 'Cuentas Financieras';
require_once('includes/load.php');
page_require_level(1);

$financial_accounts = find_all_with_account('financial_accounts');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <h5 class="mb-3">
            <i class="bi bi-bank me-2"></i>Listado de Cuentas Financieras
        </h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Saldo</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($financial_accounts as $fa): ?>
                <tr>
                    <td><?php echo (int)$fa['id']; ?></td>
                    <td><?php echo remove_junk($fa['name']); ?></td>
                    <td><?php echo remove_junk($fa['description']); ?></td>
                    <td><?php echo remove_junk($fa['type']); ?></td>
                    <td>
                        <?php echo number_format($fa['balance'], 2, ',', '.'); ?>
                    </td>
                    <td>
                        <?php echo $fa['status'] == 1 ? 'Activa' : 'Inactiva'; ?>
                    </td>
                    <td><?php echo read_date($fa['date']); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="edit_financial.php?id=<?php echo (int)$fa['id']; ?>"
                               class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Botón flotante -->
    <button id="btnFlotante"
        class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-3 shadow"
        style="width:60px;height:60px"
        data-bs-toggle="modal"
        data-bs-target="#addFinancial">
        <i class="bi bi-plus fs-3"></i>
    </button>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Add Financial Account -->
<div class="modal fade" id="addFinancial" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_financial.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Cuenta Financiera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select">
                            <option value="Caja">Caja</option>
                            <option value="Banco">Banco</option>
                            <option value="Datáfono">Datáfono</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_financial" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>