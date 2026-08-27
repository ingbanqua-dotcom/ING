<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/vendor/autoload.php';
$mailConfig = require __DIR__ . '/config.php';

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Méthode non autorisée.']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    respond(400, ['success' => false, 'message' => 'Données invalides.']);
}

$beneficiaryName = trim((string)($data['beneficiaryName'] ?? ''));
$beneficiaryEmail = trim((string)($data['beneficiaryEmail'] ?? ''));
$rib = trim((string)($data['rib'] ?? ''));
$amount = trim((string)($data['amount'] ?? ''));

if ($beneficiaryName === '' || !filter_var($beneficiaryEmail, FILTER_VALIDATE_EMAIL) || $rib === '' || $amount === '') {
    respond(422, ['success' => false, 'message' => 'Informations de virement incomplètes.']);
}

$smtpHost = $mailConfig['smtp_host'];
$smtpUsername = $mailConfig['smtp_username'];
$smtpPassword = $mailConfig['smtp_password'];
$smtpPort = $mailConfig['smtp_port'];
$smtpEncryption = $mailConfig['smtp_encryption'];
$senderEmail = $mailConfig['mail_from'] ?: $smtpUsername;
$senderName = $mailConfig['mail_from_name'];

if (!$smtpHost || !$smtpUsername || !$smtpPassword || !$senderEmail || $smtpHost === 'smtp.example.com') {
    respond(503, ['success' => false, 'message' => 'Configuration SMTP manquante.']);
}

$mailer = new PHPMailer(true);

try {
    $mailer->isSMTP();
    $mailer->Host = $smtpHost;
    $mailer->SMTPAuth = true;
    $mailer->Username = $smtpUsername;
    $mailer->Password = $smtpPassword;
    $mailer->Port = $smtpPort;
    $mailer->SMTPSecure = $smtpEncryption === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($senderEmail, $senderName);
    $mailer->addAddress($beneficiaryEmail, $beneficiaryName);
    $mailer->isHTML(true);
    $mailer->Subject = 'Confirmation de virement';
    $mailer->Body = '<p>Bonjour ' . htmlspecialchars($beneficiaryName, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Votre virement de <strong>' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</strong> est en cours de traitement.</p>'
        . '<p>RIB bénéficiaire : ' . htmlspecialchars($rib, ENT_QUOTES, 'UTF-8') . '</p>';
    $mailer->AltBody = 'Votre virement de ' . $amount . ' est en cours de traitement. RIB bénéficiaire : ' . $rib;
    $mailer->send();
    respond(200, ['success' => true, 'message' => 'Email envoyé.']);
} catch (Exception $exception) {
    respond(502, ['success' => false, 'message' => 'L’email n’a pas pu être envoyé.']);
}