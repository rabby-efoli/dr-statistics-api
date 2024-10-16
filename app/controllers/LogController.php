<?php
class LogController {
    public function storeActivityLog($request) {
        $type = $request["type"];
        $shop = $request["shop"] ?? "discountray";
        $subject = $request["subject"];
        $body = $request["body"] ?? null;
        $query = $request["query"] ?? "";
        $variables = $request["variables"] ?? "";

        return Log::createActivityLog($type, $shop, $subject, $body, $query, $variables);
    }

    public function storeWebhookLog($request) {
        $topic = $request["topic"];
        $shop = $request["shop"];
        $payload = $request["payload"];

        return Log::createWebhookLog($topic, $shop, $payload);
    }
}