<?php
class CheckoutLineItem {
    private static $fields = [
        'checkout_id',
        'checkout_line_item_key',
        'fulfillment_service',
        'gift_card',
        'grams',
        'presentment_title',
        'presentment_variant_title',
        'product_id',
        'quantity',
        'requires_shipping',
        'sku',
        'tax_lines',
        'taxable',
        'title',
        'variant_id',
        'variant_title',
        'variant_price',
        'vendor',
        'unit_price_measurement',
        'compare_at_price',
        'line_price',
        'price',
        'applied_discounts',
        'destination_location_id',
        'user_id',
        'checkout_line_item_rank',
        'origin_location_id',
        'properties',
        'stored_at',
        'modified_at'
    ];

    public static function getAll($cols = [], $checkout_id = null) {
        $dbController = new DBController();

        $sql = "SELECT * FROM checkout_line_items";
        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM checkout_line_items";
        if($checkout_id) {
            $sql .= " WHERE checkout_id = $checkout_id";
        }
        $result = $dbController->executeQuery($sql);

        $checkout_line_items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $checkout_line_items[] = $row;
        }

        return $checkout_line_items;
    }

    public static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM checkout_line_items WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $line_item = mysqli_fetch_assoc($result);

        return $line_item;
    }

    public static function getByKey($checkout_id, $key) {
        $dbController = new DBController();

        $sql = "SELECT id FROM checkout_line_items WHERE checkout_id = $checkout_id AND checkout_line_item_key = $key";
        $result = $dbController->executeQuery($sql);
        $line_item = mysqli_fetch_assoc($result);

        return $line_item;
    }

    public static function create($data) {
        $dbController = new DBController();

        $data["checkout_line_item_key"] = $data['key'];
        unset($data['key']);

        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $value = NULL;
                    if($field == "gift_card" || $field == "requires_shipping" || $field == "taxable") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "tax_lines" ||
                    $field == "unit_price_measurement" ||
                    $field == "applied_discounts" ||
                    $field == "properties") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $values[] = "'{$value}'";
                }
            }

            $sql = "INSERT INTO checkout_line_items (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
            if ($dbController->executeQuery($sql)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function update($id, $data) {
        $dbController = new DBController();

        $data["checkout_line_item_key"] = $data['key'];
        $data["modified_at"] = date('Y-m-d H:i:s.v');
        unset($data['key']);

        try {
            $updates = [];
            foreach (self::$fields as $field) {
                if (isset($data[$field])) {
                    $value = NULL;
                    if($field == "gift_card" || $field == "requires_shipping" || $field == "taxable") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "tax_lines" ||
                    $field == "unit_price_measurement" ||
                    $field == "applied_discounts" ||
                    $field == "properties") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $updates[] = "$field = '{$value}'";
                }
            }

            $sql = "UPDATE checkout_line_items SET " . implode(",", $updates) . " WHERE id = $id";
            if ($dbController->executeQuery($sql)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function delete($id) {
        $dbController = new DBController();

        $sql = "UPDATE checkout_line_items SET destroyed_at = NOW() WHERE id = $id;";
        if ($dbController->executeQuery($sql)) {
            return true;
        } else {
            return false;
        }
    }
}

