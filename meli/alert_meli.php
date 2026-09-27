<?php
/**
 * ==========================================================
 * Webhook Oficial Mercado Libre
 * ==========================================================
 *
 * Recibe notificaciones oficiales de Mercado Libre,
 * registra el payload crudo, guarda el evento en DB
 * y consulta la API para obtener el estado REAL
 * del recurso afectado.
 *
 * ----------------------------------------------------------
 * REQUISITOS
 * ----------------------------------------------------------
 * - meli.php cargado
 * - DB inicializada ($db)
 * - Funciones existentes:
 *   getAccessToken()
 *   insert_meli_event()
 *   insert_meli_event_detail()
 *   meli_order_get(string $token, string $orderId)
 *   meli_item_get(string $token, string $itemId)
 *   meli_returns(string $token, string $orderId)
 */

require_once __DIR__ . '/meli.php';

/* ==========================================================
   CONFIGURACIÓN
========================================================== */

$accountId = 1;

$logDir   = __DIR__ . '/logs';
$logMain  = $logDir . '/alert_meli.log';
$logError = $logDir . '/alert_meli_errors.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

/* ==========================================================
   LEER PAYLOAD
========================================================== */

try {

    $payload = file_get_contents('php://input');

    file_put_contents(
        $logMain,
        date('c') . ' - CRUDO: ' . $payload . PHP_EOL,
        FILE_APPEND
    );

    $data = json_decode($payload, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

} catch (Throwable $e) {

    file_put_contents(
        $logError,
        date('c') . ' - ERROR PAYLOAD | ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(400);
    exit;
}

/* ==========================================================
   VALIDAR NOTIFICACIÓN
========================================================== */

$topic    = $data['topic']    ?? null;
$resource = $data['resource'] ?? null;

if (!$topic || !$resource) {
    http_response_code(200);
    exit;
}

/* ==========================================================
   INSERTAR EVENTO CRUDO
========================================================== */

try {

    $eventId = insert_meli_event([
        'topic'          => $topic,
        'resource'       => $resource,
        'application_id' => $data['application_id'] ?? null,
        'user_id'        => $data['user_id'] ?? null,
        'payload'        => json_encode($data),
        'account'        => $accountId
    ]);

} catch (Throwable $e) {

    file_put_contents(
        $logError,
        date('c') . ' - ERROR DB EVENTO | ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);
    exit;
}

/* ==========================================================
   OBTENER TOKEN
========================================================== */

try {
    $accessToken = getAccessToken();
} catch (Throwable $e) {

    file_put_contents(
        $logError,
        date('c') . ' - ERROR TOKEN | ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);
    exit;
}

/* ==========================================================
   PROCESAR EVENTO SEGÚN TOPIC
========================================================== */

try {

    $resourceParts = explode('/', trim($resource, '/'));

    $entityId   = null;
    $entityType = null;
    $detailData = null;

    /**
     * Cada topic explícito
     * SIN AGRUPAR
     */

    switch ($topic) {

        case 'orders_v2':
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'orders';
            $detailData = meli_order_get($accessToken, $entityId);
            break;

        case 'claims':
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'claims';
            $detailData = meli_returns($accessToken, $entityId);
            break;

        case 'items':
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'items';
            $detailData = meli_item_get($accessToken, $entityId);
            break;

        case 'items_prices':
            /**
             * ML manda:
             * /items/{ID}/prices
             * pero la API REAL se consulta con:
             * GET /items/{ID}
             */
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'items_prices';
            $detailData = meli_item_get($accessToken, $entityId);
            break;

        case 'payments':
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'payments';
            $detailData = $data;
            break;

        case 'shipments':
            $entityId   = $resourceParts[1] ?? null;
            $entityType = 'shipments';
            $detailData = $data;
            break;

        default:
            $entityId   = $resource;
            $entityType = 'unknown';
            $detailData = $data;
            break;
    }

    insert_meli_event_detail([
        'event_id'    => $eventId,
        'account'     => $accountId,
        'entity_type' => $entityType,
        'entity_id'   => (string)$entityId,
        'data'        => json_encode($detailData)
    ]);

     if ($topic === 'orders_v2') {
            insert_order_meli($detailData, $accountId);
    }

} catch (Throwable $e) {

    file_put_contents(
        $logError,
        date('c') .
        " - ERROR PROCESAMIENTO | topic={$topic} | {$e->getMessage()}" .
        PHP_EOL,
        FILE_APPEND
    );
}

/* ==========================================================
   RESPUESTA FINAL
========================================================== */

http_response_code(200);
echo json_encode(['status' => 'ok']);