<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/* =========================
   SMTP CENTRAL
========================= */
function create_mailer()
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->Port = $_ENV['SMTP_PORT'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    return $mail;
}

/* =========================
   LAYOUT BASE (ESTILO SISTEMA)
   → basado en tu notificación interna original
========================= */
function email_layout_base($title, $content)
{
    return "
    <div style='font-family:Arial;background:#f1f5f9;padding:20px;'>

        <div style='max-width:650px;margin:auto;background:#fff;border-radius:10px;padding:20px;'>

            <div style='text-align:center;'>
                <h2 style='margin:0;color:#1e293b;'>Inversiones JT</h2>
                <small style='color:#64748b;'>Tenemos un mensaje para tí</small>
            </div>

            <hr style='margin:20px 0;'>

            <h3 style='margin:0 0 15px 0;color:#1e293b;text-align:center;'>
                {$title}
            </h3>

            {$content}

            <hr style='margin:20px 0;'>

            <div style='text-align:center;font-size:12px;color:#64748b;'>
                Notificación generada desde Inversiones JT
            </div>

        </div>
    </div>";
}

/* =========================
   CONTENIDO FACTURA
========================= */
function email_invoice_content($status_label, $status_color, $sale_id, $sale)
{
    return "
    <div style='text-align:center;margin-bottom:15px;'>
        <span style='color:{$status_color};font-weight:bold;font-size:16px;'>
            {$status_label}
        </span>
    </div>

    <table style='width:100%;font-size:14px;color:#334155;'>

        <tr><td><strong>Comprobante:</strong></td><td>#{$sale_id}</td></tr>
        <tr><td><strong>Cliente:</strong></td><td>{$sale['client_name']}</td></tr>
        <tr><td><strong>Total:</strong></td><td>$" . number_format($sale['total'], 2, ',', '.') . "</td></tr>
        <tr><td><strong>Tipo:</strong></td><td>{$sale['sale_type']}</td></tr>
        <tr><td><strong>Usuario:</strong></td><td>{$sale['user_status']}</td></tr>
        <tr><td><strong>Fecha:</strong></td><td>{$sale['date']}</td></tr>

    </table>

    <div style='margin-top:20px;padding:12px;background:#f8fafc;border-radius:8px;text-align:center;font-size:13px;'>
        Se adjunta el comprobante en PDF.
    </div>";
}

/* =========================
   CONTENIDO SIMPLE (MISMO ESTILO VISUAL)
========================= */
function email_simple_content($message)
{
    return "
    <div style='font-size:14px;color:#334155;line-height:1.6;'>

        <div style='padding:12px;background:#f8fafc;border-radius:8px;'>
            {$message}
        </div>

    </div>";
}

/* =========================
   PDF VENTA
========================= */
function generate_sale_pdf($sale_id, $db)
{
    ob_start();

    $_GET['id'] = (int)$sale_id;
    $_GET['raw'] = true;

    include __DIR__ . '/../invoice_sale.php';

    $pdf = ob_get_clean();
    unset($_GET['raw']);
    if (!$pdf || strlen($pdf) < 100) {
        error_log("PDF inválido venta {$sale_id}");
        return null;
    }

    return $pdf;
}

/* =========================
   ENVÍO SIMPLE / GENERAL
========================= */
function send_email($to, $subject, $message, $pdf = null)
{
    try {

        $mail = create_mailer();

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_NAME']);
        $mail->addAddress($to);

        if (!empty($pdf) && strlen($pdf) > 100) {
            $mail->addStringAttachment($pdf, 'Factura.pdf');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $mail->Body = email_layout_base(
            $subject,
            email_simple_content($message)
        );

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Error enviando correo a {$to}: " . $e->getMessage());
        return false;
    }
}

/* =========================
   NOTIFICACIÓN INTERNA VENTA
========================= */
function send_sale_notification_email_intern($sale_id, $status)
{
    global $db;

    try {

        $account_id = (int)$_SESSION['account'];
        $account = find_by_id('accounts', $account_id);

        $company_name = $account['name'] ?? 'Sistema';
        $company_email = 'notificaciones@inventariojt.com';
        $company_phone = $account['phone'] ?? '';

        $internal_emails = !empty($account['mail'])
            ? explode(',', $account['mail'])
            : [$company_email];

        $sale_id = (int)$sale_id;

        $sql = "SELECT s.*, c.name AS client_name
                FROM sales s
                LEFT JOIN clients c ON c.id = s.client_id
                WHERE s.id = {$sale_id}
                LIMIT 1";

        $sale = $db->query($sql)->fetch_assoc();

        if (!$sale) {
            return false;
        }

        $pdf_content = generate_sale_pdf($sale_id, $db);

        if (!$pdf_content) {
            return false;
        }

        $status = strtolower(trim($status));

        $status_label = ($status === 'cancelada')
            ? 'VENTA CANCELADA'
            : 'VENTA EMITIDA';

        $status_color = ($status === 'cancelada')
            ? '#dc2626'
            : '#16a34a';

        $mail = create_mailer();

        $mail->setFrom($company_email, $company_name);

        foreach ($internal_emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email);
            }
        }

        $mail->addStringAttachment($pdf_content, "Factura_{$sale_id}.pdf");

        $body_content = email_invoice_content(
            $status_label,
            $status_color,
            $sale_id,
            $sale
        );

        $mail->isHTML(true);
        $mail->Subject = "{$company_name} - {$status_label} #{$sale_id}";
        $mail->Body = email_layout_base(
            "{$status_label} #{$sale_id}",
            $body_content
        );

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Error correo interno venta {$sale_id}: " . $e->getMessage());
        return false;
    }
}