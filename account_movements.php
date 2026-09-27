<?php
$page_title = 'Movimientos Financieros';
require_once('includes/load.php');
page_require_level(2);

$movements = find_all_account_movements_with_financial();
$financial_accounts = find_all_with_account('financial_accounts');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <h5 class="mb-3">
            <i class="bi bi-arrow-left-right me-2"></i>Listado de Movimientos
        </h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cuenta</th>
                    <th>Tipo</th>
                    <th>Origen</th>
                    <th>Monto</th>
                    <th>Referencia</th>
                    <th>Nota</th>

                </tr>
            </thead>
            <tbody>
            <?php foreach ($movements as $m): ?>
                <tr>
                    <td><?php echo (int)$m['id']; ?></td>
                    <td><?php echo read_date($m['date']); ?></td>
                    <td><?php echo remove_junk($m['account_name']); ?></td>
                    <td>
                        <?php if ($m['movement_type'] == 'Crédito'): ?>
                            <span class="badge bg-success">Crédito</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Débito</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo remove_junk($m['related_table']); ?></td>
                    <td><?php echo number_format($m['amount'], 2, ',', '.'); ?></td>
                    <td><?php echo remove_junk($m['reference']); ?></td>
                    <td><?php echo remove_junk($m['note']); ?></td>
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
        data-bs-target="#addMovement">
        <i class="bi bi-plus fs-3"></i>
    </button>
</div>

<?php include_once('layouts/footer.php'); ?>

<!-- Modal Ajuste Manual -->
<div class="modal fade" id="addMovement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="add_movement.php" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title">Ajuste Manual de Saldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Cuenta Financiera</label>
                        <select name="financial_account_id" class="form-select" required>
                            <option value="">Seleccionar cuenta</option>
                            <?php foreach ($financial_accounts as $fa): ?>
                                <option value="<?php echo (int)$fa['id']; ?>">
                                    <?php echo remove_junk($fa['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select name="movement_type" class="form-select" required>
                            <option value="Crédito">Entrada (Crédito)</option>
                            <option value="Débito">Salida (Débito)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monto</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referencia</label>
                        <input type="text" name="reference" class="form-control" placeholder="Referencia">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nota</label>
                        <input type="text" name="note" class="form-control" placeholder="Motivo del ajuste">
                    </div>

                    <input type="hidden" name="related_table" value="Ajuste Manual">

                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_movement" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>