<?php
$logs = file_exists('logs/alert_meli.log') ? file('logs/alert_meli.log') : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel de Alertas ML</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; text-align: left; }
        tr:nth-child(even) { background: #f9f9f9; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }
        .json-toggle { cursor: pointer; color: blue; text-decoration: underline; }
        .json-content { display: none; }
    </style>
    <script>
        function toggleJSON(id) {
            const el = document.getElementById(id);
            el.style.display = (el.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <h2>Alertas Mercado Libre</h2>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Topic</th>
            <th>Resource</th>
            <th>Received</th>
            <th>Sent</th>
            <th>Intentos</th>
            <th>Contenido Completo</th>
        </tr>
        <?php foreach (array_reverse($logs) as $index => $line): ?>
        <?php
            $line = trim($line);
            // Extraer fecha ISO al inicio
            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2}|Z)?)(.*)$/', $line, $matches)) {
                $date = $matches[1];
                $contentRaw = trim($matches[2], " -");
            } else {
                $date = '';
                $contentRaw = $line;
            }

            // Intentar decodificar JSON
            $json = json_decode($contentRaw, true);

            $topic = $json['topic'] ?? '';
            $resource = $json['resource'] ?? '';
            $received = $json['received'] ?? '';
            $sent = $json['sent'] ?? '';
            $attempts = $json['attempts'] ?? '';
        ?>
        <tr>
            <td><?= htmlspecialchars($date) ?></td>
            <td><?= htmlspecialchars($topic) ?></td>
            <td><?= htmlspecialchars($resource) ?></td>
            <td><?= htmlspecialchars($received) ?></td>
            <td><?= htmlspecialchars($sent) ?></td>
            <td><?= htmlspecialchars($attempts) ?></td>
            <td>
                <span class="json-toggle" onclick="toggleJSON('json<?= $index ?>')">Ver JSON</span>
                <pre id="json<?= $index ?>" class="json-content"><?= htmlspecialchars($contentRaw) ?></pre>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
