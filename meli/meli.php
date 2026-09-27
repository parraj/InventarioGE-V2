<?php
/**
 * ==========================================================
 * SDK Mercado Libre - PHP (Webhook Safe)
 * ==========================================================
 *
 * ✔ Manejo de tokens OAuth
 * ✔ Requests autenticados y públicos
 * ✔ Compatible con tu framework ($db, load.php)
 * ✔ Sin magia oculta
 * ✔ Logs claros
 *
 * Autor: Jaime Parra
 */

/* ==========================================================
   CONFIGURACIÓN BASE
========================================================== */

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/load.php';

$tokenFile = __DIR__ . '/tokens.json';

/* ==========================================================
   LOGGING
========================================================== */

/**
 * Guarda errores críticos del SDK
 */
function meli_log(string $message): void
{
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(
        $dir . '/meli_errors.log',
        date('c') . ' - ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

/* ==========================================================
   TOKEN OAUTH
========================================================== */

/**
 * Obtiene un access_token válido.
 * Renueva automáticamente si está vencido.
 *
 * @throws Exception
 */
function getAccessToken(): string
{
    global $tokenFile;

    if (!file_exists($tokenFile)) {
        throw new Exception('Archivo tokens.json no existe');
    }

    $data = json_decode(file_get_contents($tokenFile), true);

    if (
        !$data ||
        !isset(
            $data['access_token'],
            $data['refresh_token'],
            $data['expires_in'],
            $data['created_at']
        )
    ) {
        throw new Exception('tokens.json inválido');
    }

    $expiresAt = $data['created_at'] + $data['expires_in'] - 60;

    if (time() >= $expiresAt) {
        $new = meli_refresh_token($data['refresh_token']);
        $new['created_at'] = time();
        file_put_contents($tokenFile, json_encode($new, JSON_PRETTY_PRINT));
        return $new['access_token'];
    }

    return $data['access_token'];
}

/* ==========================================================
   HTTP CORE
========================================================== */

/**
 * Request base a la API de Mercado Libre
 *
 * @throws Exception
 */
function meli_request(
    string $method,
    string $endpoint,
    array $data = [],
    ?string $token = null
): array {
    global $config;

    $url = rtrim($config['api_url'], '/') . $endpoint;

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => $method !== 'GET' ? json_encode($data) : null,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        meli_log("CURL ERROR: {$error}");
        throw new Exception('Error CURL');
    }

    $decoded = json_decode($response, true);

    if ($status >= 400) {
        meli_log("HTTP {$status} | {$endpoint} | " . $response);
    }

    return $decoded ?? [];
}

/* ==========================================================
   OAUTH ENDPOINTS
========================================================== */

function meli_auth_url(): string
{
    global $config;

    return $config['auth_url'] . '/authorization'
        . '?response_type=code'
        . '&client_id=' . $config['client_id']
        . '&redirect_uri=' . urlencode($config['redirect_uri']);
}

function meli_get_token(string $code): array
{
    return meli_request('POST', '/oauth/token', [
        'grant_type'    => 'authorization_code',
        'client_id'     => $GLOBALS['config']['client_id'],
        'client_secret' => $GLOBALS['config']['client_secret'],
        'code'          => $code,
        'redirect_uri'  => $GLOBALS['config']['redirect_uri'],
    ]);
}

function meli_refresh_token(string $refreshToken): array
{
    return meli_request('POST', '/oauth/token', [
        'grant_type'    => 'refresh_token',
        'client_id'     => $GLOBALS['config']['client_id'],
        'client_secret' => $GLOBALS['config']['client_secret'],
        'refresh_token' => $refreshToken,
    ]);
}

/* ==========================================================
   ITEMS (SIEMPRE CON TOKEN EN WEBHOOK)
========================================================== */

/**
 * Obtiene un ítem (PRIVADO / SEGURO)
 */
function meli_item_get(string $token, string $itemId): array
{
    return meli_request('GET', "/items/{$itemId}", [], $token);
}

/**
 * Actualiza precio / stock
 */
function meli_item_update(string $token, string $itemId, array $data): array
{
    return meli_request('PUT', "/items/{$itemId}", $data, $token);
}

/* ==========================================================
   ÓRDENES
========================================================== */

function meli_order_get(string $token, string $orderId): array
{
    return meli_request('GET', "/orders/{$orderId}", [], $token);
}

/* ==========================================================
   DEVOLUCIONES
========================================================== */

function meli_returns(string $token, string $orderId): array
{
    return meli_request('GET', "/orders/{$orderId}/returns", [], $token);
}

/* ==========================================================
   DB – EVENTOS WEBHOOK
========================================================== */

/**
 * Inserta evento crudo
 */
function insert_meli_event(array $data): int
{
    global $db;

    $sql = "
        INSERT INTO meli_event (
            topic, resource, application_id, user_id, payload, account
        ) VALUES (
            '{$db->escape($data['topic'])}',
            '{$db->escape($data['resource'])}',
            " . ($data['application_id'] ? (int)$data['application_id'] : 'NULL') . ",
            " . ($data['user_id'] ? (int)$data['user_id'] : 'NULL') . ",
            '{$db->escape($data['payload'])}',
            " . (int)$data['account'] . "
        )
    ";

    if (!$db->query($sql)) {
        meli_log('DB ERROR EVENTO');
        throw new Exception('Error insertando evento ML');
    }

    return (int)$db->insert_id();
}

/**
 * Inserta detalle procesado
 */
function insert_meli_event_detail(array $data): int
{
    global $db;

    $sql = "
        INSERT INTO meli_event_detail (
            event_id, account, entity_type, entity_id, data
        ) VALUES (
            " . (int)$data['event_id'] . ",
            " . (int)$data['account'] . ",
            '{$db->escape($data['entity_type'])}',
            '{$db->escape($data['entity_id'])}',
            '{$db->escape($data['data'])}'
        )
    ";

    if (!$db->query($sql)) {
        meli_log('DB ERROR DETAIL');
        throw new Exception('Error insertando detalle ML');
    }

    return (int)$db->insert_id();
}

/**
 * Inserta una orden de Mercado Libre en el sistema.
 *
 * Registra la venta en `sales`, sus ítems en `sales_detail`,
 * descuenta stock de `products` y evita duplicados usando `order_ml`.
 *
 * @param array $order   JSON de la orden obtenido desde la API de Mercado Libre
 * @param int   $account ID de la cuenta interna (siempre 1)
 *
 * @return bool True si la orden fue insertada, false si ya existía
 *
 * @throws Exception Si ocurre un error crítico durante la inserción
 */
function insert_order_meli(array $order, int $account = 1): bool
{
    global $db;

    if (empty($order['id'])) {
        throw new Exception('Orden ML inválida');
    }

    $order_ml  = (string)$order['id'];
    $paid_amount  = $order['paid_amount'];
    $status_ml = $order['status'] ?? 'payment_required';
    $client_id = 22;
    $user      = 'ML';
    $date      = date('Y-m-d H:i:s');

    /**
     * =====================================
     * VALIDAR ORDEN DUPLICADA
     * =====================================
     */
    $check = $db->query("
        SELECT id   
        FROM sales 
        WHERE order_ml = '{$order_ml}'
        LIMIT 1
    ");

    if ($db->num_rows($check) > 0) {
        // Ya existe → no es error
        return false;
    }

    /**
     * =====================================
     * CALCULAR COMISIÓN ML (TOTAL)
     * =====================================
     */
    $commission_ml = null;

    if (!empty($order['payments'])) {
        $commission_ml = 0;
        foreach ($order['payments'] as $payment) {
            if (isset($payment['marketplace_fee'])) {
                $commission_ml += (float)$payment['marketplace_fee'];
            }
        }
    }

    /**
     * =====================================
     * INSERT SALES (total = 0)
     * =====================================
     */
    $sql_sale = "
        INSERT INTO sales
        (client_id, status, sale_type, total, account, account_sender, user, date, order_ml, status_ml, commission_ml)
        VALUES
        (
            '{$client_id}',
            'Emitida',
            'ML',
            '{$paid_amount}',
            '{$account}',
            '{$account}',
            '{$user}',
            '{$date}',
            '{$order_ml}',
            '{$status_ml}',
            " . ($commission_ml !== null ? $commission_ml : "NULL") . "
        )
    ";

    if (!$db->query($sql_sale)) {
        throw new Exception('Error insertando sales');
    }

    $sale_id    = $db->insert_id();
    $gran_total = 0;

    /**
     * =====================================
     * INSERT ITEMS
     * =====================================
     */
    foreach ($order['order_items'] as $row) {

        $item_ml      = $row['item']['id'];
        $variation_ml = $row['item']['variation_id'] ?? null;
        $qty          = (int)$row['quantity'];
        $price        = (float)$row['unit_price'];
        $line_total   = $price * $qty;
        
        $variationCondition = $variation_ml !== null
        ? "variation_ml = '{$variation_ml}'"
        : "(variation_ml IS NULL OR variation_ml = '')";
      
        /**
         * Buscar producto
         */
        $sql_product = "
            SELECT id, quantity
            FROM products
            WHERE item_ml = '{$item_ml}'
            AND {$variationCondition}
            AND account = '{$account}'
            LIMIT 1
        ";

        $rs = $db->query($sql_product);

        if ($db->num_rows($rs) === 0) {
            throw new Exception("Producto ML no encontrado: {$item_ml}");
        }

        $product = $db->fetch_assoc($rs);
        $product_id = $product['id'];

        /**
         * Insert sales_detail
         */
        $sql_detail = "
            INSERT INTO sales_detail
            (sale_id, product_id, qty, price, account, item_ml, variation_ml, date)
            VALUES
            (
                '{$sale_id}',
                '{$product_id}',
                '{$qty}',
                '{$price}',
                '{$account}',
                '{$item_ml}',
                " . ($variation_ml ? "'{$variation_ml}'" : "NULL") . ",
                '{$date}'
            )
        ";

        if (!$db->query($sql_detail)) {
            throw new Exception('Error insertando sales_detail');
        }

        /**
         * Descontar stock
         */
        $db->query("
            UPDATE products
            SET quantity = quantity - {$qty}
            WHERE id = '{$product_id}'
        ");

        $gran_total += $line_total;
    }

    return true;
}
