<?php
$page_title = 'Grupos';
require_once('includes/load.php');
page_require_level(1);

$all_groups = find_all('user_groups');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <h5 class="mb-3"><i class="bi bi-people me-2"></i>Listado de Grupos</h5>
            <table id="datatable" class="table text-nowrap mb-0 align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre del grupo</th>
                    <th class="text-center">Nivel del grupo</th>
                    <th class="text-center">Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($all_groups as $a_group): ?>
                    <tr>
                        <td><?php echo remove_junk($a_group['id']); ?></td>
                        <td><?php echo remove_junk(ucwords($a_group['group_name'])); ?></td>
                        <td class="text-center"><?php echo remove_junk($a_group['group_level']); ?></td>
                        <td class="text-center">
                            <?php if($a_group['group_status'] === '1'): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif;?>
                        </td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
