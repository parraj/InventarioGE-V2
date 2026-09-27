<?php
$page_title = 'Detalle Evento ML';

require_once('includes/load.php');
page_require_level(2);

$eventId = (int)$_GET['id'];

/**
 * Obtener evento principal
 */
$event = find_by_id('meli_event', $eventId);

/**
 * Obtener detalles del evento
 */
$details = find_meli_event_details($eventId);

if (!$event) {
    $session->msg('d', 'Evento no encontrado');
    redirect('meli_events.php');
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <h5 class="mb-3">
            <i class="bi bi-info-circle-fill me-2"></i>Detalle del Evento
        </h5>
    </div>

    <!-- Datos generales -->
    <div class="col-md-6">
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Información General</h6>
                <p><strong>Topic:</strong> <?php echo remove_junk($event['topic']); ?></p>
                <p><strong>Resource:</strong> <?php echo remove_junk($event['resource']); ?></p>
                <p><strong>User ML:</strong> <?php echo remove_junk($event['user_id']); ?></p>
                <p><strong>Fecha:</strong> <?php echo read_date($event['date']); ?></p>
            </div>
        </div>
    </div>

    <!-- Payload crudo -->
    <div class="col-md-6">
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Payload Crudo</h6>
                <pre class="bg-light p-2 small">
<?php echo json_encode(json_decode($event['payload']), JSON_PRETTY_PRINT); ?>
                </pre>
            </div>
        </div>
    </div>

    <!-- Detalles procesados -->
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Datos Procesados (API Mercado Libre)</h6>

                <?php foreach ($details as $detail): ?>
                    <div class="mb-4">
                        <span class="badge bg-success mb-2">
                            <?php echo remove_junk($detail['entity_type']); ?>
                        </span>

                        <pre class="bg-dark text-white p-3 small">
<?php echo json_encode(json_decode($detail['data']), JSON_PRETTY_PRINT); ?>
                        </pre>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>

<?php include_once('layouts/footer.php'); ?>
