<?php
$page_title = 'Eventos Mercado Libre';

require_once('includes/load.php');
page_require_level(2);

/**
 * Obtener eventos webhook
 */
$events = find_all('meli_event');
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <?php echo display_msg($msg); ?>
    </div>

    <div class="table-responsive">
        <h5 class="mb-3">
            <i class="bi bi-bell"></i>Historial de Eventos Mercado Libre
        </h5>

        <table id="datatable" class="table text-nowrap mb-0 align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Topic</th>
                <th>Resource</th>
                <th>User ML</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo (int)$event['id']; ?></td>
                    <td>
                        <span class="badge bg-primary">
                            <?php echo remove_junk($event['topic']); ?>
                        </span>
                    </td>
                    <td><?php echo remove_junk($event['resource']); ?></td>
                    <td><?php echo remove_junk($event['user_id']); ?></td>
                    <td><?php echo read_date($event['date']); ?></td>
                    <td>
                        <a href="meli_event_detail.php?id=<?php echo (int)$event['id']; ?>"
                           class="btn btn-primary btn-sm"
                           title="Ver detalle">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>