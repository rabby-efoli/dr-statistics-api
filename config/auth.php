<?php
// Set the allowed IP address
$allowedIp = '127.0.0.1'; 

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

    // The last IP in the list is the client's real IP
    $clientIp = trim(end($ipList)); 
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'];
}

if ($clientIp != $allowedIp) {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode([
        'message' => "Access denied.",
    ]));
}