<?php
require_once "../config/helpers.php";

$body = file_get_contents('php://input');
$receivedSignature = $_SERVER['HTTP_X_SIGNATURE'];
$secret = getEnvValue("API_SECRET");

$shiftedText = base64_decode($receivedSignature);
$decryptedText = '';
for ($i = 0; $i < strlen($shiftedText); $i++) {
    $shift = ord($secret[$i % strlen($secret)]);
    $decryptedCharCode = ord($shiftedText[$i]) - $shift;
    $decryptedText .= chr($decryptedCharCode);
}

if (!in_array($decryptedText, ["log-data", "stat-data"])) {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode([
        'message' => "Access denied.",
    ]));
}