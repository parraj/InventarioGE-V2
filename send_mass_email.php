<?php
require_once('includes/load.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $session->msg('d', 'Acceso no permitido.');
    redirect('email_campaigns.php', false);
    exit;
}

$type = $_POST['type'] ?? '';

$ids = $_POST['ids'] ?? [];
$subject = trim($_POST['subject'] ?? '');
$message = $_POST['message'] ?? '';
global $db;


if (empty($ids) || empty($subject) || empty($message)) {
    $session->msg('d', 'Faltan datos para enviar la campaña.');
    redirect('email_campaigns.php', false);
    exit;
}

$sent = 0;
$errors = 0;

foreach ($ids as $index => $id) {

    $id = (int) $id;

    try {

        if ($type === 'sales') {

            $sale = find_by_id('sales', $id);
            if (!$sale)
                continue;

            $client = find_by_id('clients', $sale['client_id']);
            if (!$client || empty($client['mail']))
                continue;

            // generar PDF
            $pdf = generate_sale_pdf($id, $db);

            $ok = send_email(
                $client['mail'],
                $subject,
                $message,
                $pdf
            );

        } else {

            $client = find_by_id('clients', $id);
            if (!$client || empty($client['mail']))
                continue;

            $ok = send_email(
                $client['mail'],
                $subject,
                $message
            );
        }

        if ($ok)
            $sent++;
        else
            $errors++;

    } catch (Exception $e) {
        error_log("Error campaña ID {$id}: " . $e->getMessage());
        $errors++;
    }

    /**
     * =========================
     * CONTROL DE VELOCIDAD
     * =========================
     */

    // Pausa base (evita saturar SMTP)
    sleep(1); // 1 correo cada 1 segundos

    // Pausa más larga cada 20 envíos
    if (($index + 1) % 20 === 0) {
        sleep(5);
    }
}

$session->msg('s', "Correos enviados: {$sent} | Errores: {$errors}");
if ($type == 'sales') {
    redirect('sales.php', false);
}
redirect('client.php', false);
exit;