<?php
class Order {
    private static $fields = [
        "dr_discount_applied",
        "dr_discount_amounts",
        "dr_discount_total",
        "order_id",
        "order_gid",
        "app_id",
        "browser_ip",
        "buyer_accepts_marketing",
        "cancel_reason",
        "cancelled_at",
        "cart_token",
        "checkout_id",
        "checkout_token",
        "client_details",
        "closed_at",
        "company",
        "confirmation_number",
        "confirmed",
        "contact_email",
        "created_at",
        "currency",
        "current_subtotal_price",
        "current_subtotal_price_set",
        "current_total_additional_fees_set",
        "current_total_discounts",
        "current_total_discounts_set",
        "current_total_duties_set",
        "current_total_price",
        "current_total_price_set",
        "current_total_tax",
        "current_total_tax_set",
        "customer_locale",
        "device_id",
        "discount_codes",
        "email",
        "estimated_taxes",
        "financial_status",
        "fulfillment_status",
        "landing_site",
        "landing_site_ref",
        "location_id",
        "merchant_of_record_app_id",
        "name",
        "note",
        "note_attributes",
        "number",
        "order_number",
        "order_status_url",
        "original_total_additional_fees_set",
        "original_total_duties_set",
        "payment_gateway_names",
        "po_number",
        "presentment_currency",
        "processed_at",
        "reference",
        "referring_site",
        "source_identifier",
        "source_name",
        "source_url",
        "subtotal_price",
        "subtotal_price_set",
        "tags",
        "tax_exempt",
        "tax_lines",
        "taxes_included",
        "test",
        "token",
        "total_discounts",
        "total_discounts_set",
        "total_line_items_price",
        "total_line_items_price_set",
        "total_outstanding",
        "total_price",
        "total_price_set",
        "total_shipping_price_set",
        "total_tax",
        "total_tax_set",
        "total_tip_received",
        "total_weight",
        "updated_at",
        "user_id",
        "billing_address",
        "customer",
        "discount_applications",
        "fulfillments",
        "line_items",
        "payment_terms",
        "refunds",
        "shipping_address",
        "shipping_lines",
        "stored_at",
        "modified_at",
    ];

    public static function list($cols = []) {
        $dbController = new DBController();

        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM orders";
        $result = $dbController->executeQuery($sql);

        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }

        returnResponse(200, "success", "Order list", $orders);
    }

    public static function view($id) {
        $order = self::getById($id);

        if ($order) {
            returnResponse(200, "success", "Order details", $order);
        } else {
            returnResponse(404, "error", "Order not found");
        }
    }

    private static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM orders WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $order = mysqli_fetch_assoc($result);

        if ($order) {
            return $order;
        } else {
            return false;
        }
    }

    public static function getTotalOrders($dataDurationStart, $dataDurationEnd) {
        $lastNdaysSql = "SELECT COUNT(*) AS orders_last_days FROM orders WHERE created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";
        $prevNdaysSql = "SELECT COUNT(*) AS orders_previous_days FROM orders WHERE created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationEnd DAY)) AND created_at < (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";

        $totalOrderData = self::getOrderAndSaleData($lastNdaysSql, $prevNdaysSql);
        return [$totalOrderData[0]["orders_last_days"], $totalOrderData[1]["orders_previous_days"]];
    }

    public static function getDrOrders($dataDurationStart, $dataDurationEnd) {
        $lastNdaysSql = "SELECT COUNT(*) AS dr_orders_last_days FROM orders WHERE dr_discount_applied = 1 AND created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";
        $prevNdaysSql = "SELECT COUNT(*) AS dr_orders_previous_days FROM orders WHERE dr_discount_applied = 1 AND created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationEnd DAY)) AND created_at < (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";

        $drOrderData = self::getOrderAndSaleData($lastNdaysSql, $prevNdaysSql);
        return [$drOrderData[0]["dr_orders_last_days"], $drOrderData[1]["dr_orders_previous_days"]];
    }

    public static function getTotalSale($dataDurationStart, $dataDurationEnd) {
        $lastNdaysSql = "SELECT SUM(total_price) AS sale_last_days FROM orders WHERE created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";
        $prevNdaysSql = "SELECT SUM(total_price) AS sale_previous_days FROM orders WHERE created_at >= (DATE_SUB(CURDATE(), INTERVAL $dataDurationEnd DAY)) AND created_at < (DATE_SUB(CURDATE(), INTERVAL $dataDurationStart DAY));";

        $totalSaleData = self::getOrderAndSaleData($lastNdaysSql, $prevNdaysSql);
        return [$totalSaleData[0]["sale_last_days"], $totalSaleData[1]["sale_previous_days"]];
    }

    private static function getOrderAndSaleData($lastNdaysSql, $prevNdaysSql) {
        // echo "$lastNdaysSql \r\n$prevNdaysSql";
        $dbController = new DBController();

        $lastNdaysResult = $dbController->executeQuery($lastNdaysSql);
        $lastNdaysData = mysqli_fetch_assoc($lastNdaysResult);

        $prevNdaysResult = $dbController->executeQuery($prevNdaysSql);
        $prevNdaysData = mysqli_fetch_assoc($prevNdaysResult);

        return [$lastNdaysData, $prevNdaysData];
    }

    public static function create($data) {
        $dbController = new DBController();
        $connection = $dbController->getConnection();

        $data["order_id"] = $data['id'];
        $data["order_gid"] = $data['admin_graphql_api_id'];
        $line_items = $data["line_items"];
        $data["customer_gid"] = $data['customer']['admin_graphql_api_id'];
        unset($data['id']);
        unset($data['admin_graphql_api_id']);
        unset($data['line_items']);
        unset($data['customer']);

        $dbController->executeQuery("START TRANSACTION");
        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $value = NULL;
                    if($field == "dr_discount_applied" || 
                    $field == "buyer_accepts_marketing" || 
                    $field == "confirmed" || 
                    $field == "estimated_taxes" || 
                    $field == "tax_exempt" || 
                    $field == "taxes_included" || 
                    $field == "test") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "dr_discount_amounts" ||
                    $field == "client_details" ||
                    $field == "current_subtotal_price_set" ||
                    $field == "current_total_additional_fees_set" ||
                    $field == "current_total_discounts_set" ||
                    $field == "current_total_duties_set" ||
                    $field == "current_total_price_set" ||
                    $field == "current_total_tax_set" ||
                    $field == "discount_codes" ||
                    $field == "note_attributes" ||
                    $field == "original_total_additional_fees_set" ||
                    $field == "original_total_duties_set" ||
                    $field == "payment_gateway_names" ||
                    $field == "subtotal_price_set" ||
                    $field == "tax_lines" ||
                    $field == "total_discounts_set" ||
                    $field == "total_line_items_price_set" ||
                    $field == "total_price_set" ||
                    $field == "total_shipping_price_set" ||
                    $field == "total_tax_set" ||
                    $field == "billing_address" ||
                    $field == "shipping_address" ||
                    $field == "discount_applications" || 
                    $field == "fulfillments" || 
                    $field == "payment_terms" || 
                    $field == "refunds" || 
                    $field == "shipping_address" || 
                    $field == "shipping_lines") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $values[] = "'{$value}'";
                }
            }

            $sql = "INSERT INTO orders (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
            $dbController->executeQuery($sql);
            $last_id = mysqli_insert_id($connection);

            if ($last_id) {
                foreach ($line_items as $line_item) {
                    $line_item["order_id"] = $last_id;
                    OrderLineItem::create($line_item);
                }
                $dbController->executeQuery("COMMIT");
                returnResponse(200, "success", "Order created successfully");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to create order");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }
}

