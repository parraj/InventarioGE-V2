<?php
/**
 * Script para obtener el primer access_token y refresh_token de Mercado Libre
 * 
 * Muestra los tokens directamente en la página.
 */

require_once 'meli.php'; // Tu SDK

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CONFIGURACIÓN ---
$redirectUri = $config['redirect_uri']; // Debe coincidir con tu config.php

// 1️⃣ Si no hay code en la URL, generar URL de autorización
if (!isset($_GET['code'])) {
    $authUrl = meli_auth_url();
    echo "<h2>Autorizar aplicación Mercado Libre</h2>";
    echo "<p><a href='$authUrl' target='_blank'>Haz clic aquí para autorizar</a></p>";
    echo "<p>Después de autorizar, serás redirigido a esta URL con <strong>code</strong> en la barra de dirección.</p>";
    exit;
}

// 2️⃣ Recibimos el code en la URL
$code = $_GET['code'];

try {
    // 3️⃣ Intercambiar code por tokens
    $tokenData = meli_get_token($code);

    // 4️⃣ Agregar timestamp de creación
    $tokenData['created_at'] = time();

    echo "<h2>Tokens obtenidos correctamente ✅</h2>";
    echo "<pre>" . json_encode($tokenData, JSON_PRETTY_PRINT) . "</pre>";

    // 5️⃣ Intentar guardar en tokens.json y mostrar resultado
    $tokenFile = __DIR__ . '/tokens.json';
    if (file_put_contents($tokenFile, json_encode($tokenData, JSON_PRETTY_PRINT))) {
        echo "<p>Archivo <strong>tokens.json</strong> creado con éxito en: <code>$tokenFile</code></p>";
    } else {
        echo "<p style='color:red;'>No se pudo crear tokens.json. Revisa permisos de escritura en la carpeta.</p>";
    }

} catch (Exception $e) {
    echo "<h2>Error obteniendo tokens ❌</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
