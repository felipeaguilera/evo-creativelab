<?php
/**
 * contact.php — evo.cl
 * Handles contact form submissions via authenticated SMTP.
 * Shared handler for evo.cl homepage and all landing pages.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/smtp_send.php';

define('TO_EMAIL', 'aguilera.felipe@gmail.com');
define('TO_NAME',  'Felipe Aguilera');
define('SUBJECT_PREFIX', '[evo.cl]');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

$nombre  = clean($_POST['nombre']  ?? '');
$empresa = clean($_POST['empresa'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$mensaje = clean($_POST['mensaje'] ?? '');

// Identify origin page
$origin = clean($_SERVER['HTTP_REFERER'] ?? 'evo.cl');

if (!$nombre || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$mensaje) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

$subject = SUBJECT_PREFIX . ' Nuevo mensaje de ' . $nombre . ($empresa ? " ({$empresa})" : '');

// ── Plain text ────────────────────────────────────────────────────
$plain  = "Nuevo mensaje desde evo.cl\n";
$plain .= str_repeat('-', 40) . "\n";
$plain .= "Nombre:  {$nombre}\n";
if ($empresa) $plain .= "Empresa: {$empresa}\n";
$plain .= "Email:   {$email}\n";
$plain .= "Fecha:   " . date('d/m/Y H:i') . " UTC\n";
$plain .= "Origen:  {$origin}\n";
$plain .= str_repeat('-', 40) . "\n\n";
$plain .= $mensaje . "\n\n";
$plain .= "Responde directamente a este correo para contactar al remitente.\n";

// ── HTML ──────────────────────────────────────────────────────────
$msgHtml = nl2br(htmlspecialchars($mensaje));
$html  = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>";
$html .= "<body style='font-family:-apple-system,sans-serif;margin:0;background:#F5F3EE;'>";
$html .= "<div style='max-width:560px;margin:28px auto;background:#fff;border:1px solid #E5E2DC;'>";
$html .= "  <div style='background:#111111;padding:22px 28px;'>";
$html .= "    <p style='margin:0 0 6px;font-size:10px;color:#666;letter-spacing:.12em;text-transform:uppercase;font-family:monospace;'>evo.cl</p>";
$html .= "    <h1 style='margin:0;font-size:18px;font-weight:800;color:#fff;letter-spacing:-.01em;'>Nuevo mensaje</h1>";
$html .= "  </div>";
$html .= "  <div style='padding:28px;'>";
$html .= "    <table style='border-collapse:collapse;font-size:13px;margin-bottom:24px;width:100%;'>";
$html .= "      <tr><td style='color:#999;padding:5px 20px 5px 0;white-space:nowrap;'>Nombre</td><td style='color:#111;font-weight:600;'>" . htmlspecialchars($nombre) . "</td></tr>";
if ($empresa) {
    $html .= "      <tr><td style='color:#999;padding:5px 20px 5px 0;'>Empresa</td><td style='color:#111;'>" . htmlspecialchars($empresa) . "</td></tr>";
}
$html .= "      <tr><td style='color:#999;padding:5px 20px 5px 0;'>Email</td><td><a href='mailto:{$email}' style='color:#E4572E;text-decoration:none;'>{$email}</a></td></tr>";
$html .= "      <tr><td style='color:#999;padding:5px 20px 5px 0;'>Fecha</td><td style='color:#bbb;font-size:11px;font-family:monospace;'>" . date('d/m/Y H:i') . " UTC</td></tr>";
$html .= "      <tr><td style='color:#999;padding:5px 20px 5px 0;'>Origen</td><td style='color:#bbb;font-size:11px;'>" . htmlspecialchars($origin) . "</td></tr>";
$html .= "    </table>";
$html .= "    <div style='border-left:3px solid #E4572E;padding:14px 18px;font-size:14px;line-height:1.7;color:#333;background:#fafafa;'>{$msgHtml}</div>";
$html .= "    <p style='margin:24px 0 0;font-size:11px;color:#bbb;'>Responde directamente a este correo para contactar a {$nombre}.</p>";
$html .= "  </div></div></body></html>";

// ── Send ──────────────────────────────────────────────────────────
$sent = smtp_mail(
    TO_EMAIL, TO_NAME,
    $subject,
    $plain, $html,
    $nombre . ' <' . $email . '>'
);

echo json_encode(['ok' => $sent]);
