<?php
function returnResponse($code, $response, $message = "", $data = []) {
    http_response_code($code);
    exit(json_encode([
        'response' => $response,
        'message' => $message,
        "data" => $data
    ]));
}

function getEnvValue($target) {
    $env = file_get_contents("../.env");
    $lines = explode("\n", $env);

    foreach($lines as $line){
        $var = explode("=", $line);
        if($var[0] == $target) {
            return $var[1] ?? "";
        }
    }
    return "";
}

function createLog($type, $subject, $data) {
    // Define the file path
    $filePath = __DIR__ . './../data.log';

    // Get the current date and time
    $dateTime = date('Y-m-d H:i:s');

    // Convert array data to string if needed
    if (is_array($data)) {
        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Prepare the log entry
    $logEntry = "[$dateTime] $type: $subject | $data" . PHP_EOL;

    // Append to the file or create it if it doesn't exist
    file_put_contents($filePath, $logEntry, FILE_APPEND);
}

function dd($data) {
    die(json_encode($data));
}