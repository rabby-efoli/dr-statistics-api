<?php
function returnResponse($code, $response, $message = "", $data = []) {
    http_response_code($code);
    exit(json_encode([
        'response' => $response,
        'message' => $message,
        "data" => $data
    ]));
}

function writeFile($data) {
    $file = "../data.log";
    file_put_contents($file, $data);
}

function dd($data) {
    die(json_encode($data));
}