<?php
class Log {
    public static function createActivityLog($type, $shop, $subject, $body, $query, $variables) {
        // Set the current date
        $currentDate = new DateTime();
        
        // Create the log directory path
        $logDir = "./../logs/activity" 
                    . "/" . $currentDate->format('Y') 
                    . "/" . $currentDate->format('m') 
                    . "/" . $currentDate->format('d') 
                    . "/" . $currentDate->format('H') 
                    . "/" . $shop;

        // Determine the file name based on the log type
        $fileName = "/discount-ray.log";  // Default file name
        if ($type == "error") {
            $fileName = "/error.log";
        } else if ($type == "success") {
            $fileName = "/success.log";
        } else if ($type == "info") {
            $fileName = "/info.log";
        }
        
        // Full path to the log file
        $filePath = $logDir . $fileName;

        // Prepare log data
        $logData = json_encode([
            "shop" => $shop,
            "subject" => $subject,
            "body" => $body,
            "query" => $query,
            "variables" => $variables
        ]);

        // Format log data with timestamp and log type
        $formattedLogData = "[" . $currentDate->format('Y-m-d H:i:s') . "] " . strtoupper($type) . ": " . $logData;

        // Write the log to the file
        return self::createLogFile($logDir, $filePath, $formattedLogData);
    }

    public static function createWebhookLog($topic, $shop, $payload) {
        // Set the current date
        $currentDate = new DateTime();
        
        // Create the log directory path
        $logDir = "./../logs/webhook"
                    . "/" . $currentDate->format('Y') 
                    . "/" . $currentDate->format('m') 
                    . "/" . $currentDate->format('d') 
                    . "/" . $shop;
    
        // Create a log file with the current timestamp in milliseconds as the file name
        $filePath = $logDir . "/" . round(microtime(true) * 1000) . ".log";
    
        // Format log data with the current timestamp and topic
        $formattedLogData = "[" . $currentDate->format('Y-m-d H:i:s') . "] " . $topic . ": " . json_encode($payload);
    
        // Write the log to the file
        return self::createLogFile($logDir, $filePath, $formattedLogData);
    }

    private static function createLogFile($logDir, $filePath, $logData) {
        // Create the directory if it doesn't exist
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Write the log data to the file
        if(file_put_contents($filePath, $logData . PHP_EOL, FILE_APPEND)) {
            returnResponse(200, "success", "Log added successfully");
        }
        else {
            returnResponse(500, "error", "Failed to create log");
        }
    }
}