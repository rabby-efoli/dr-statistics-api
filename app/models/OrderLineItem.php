<?php
class OrderLineItem {
    private static $fields = [
        'order_id',
        'line_item_gid',
        'attributed_staffs',
        'current_quantity',
        'fulfillable_quantity',
        'fulfillment_service',
        'fulfillment_status',
        'gift_card',
        'grams',
        'name',
        'price',
        'price_set',
        'product_exists',
        'product_id',
        'properties',
        'quantity',
        'requires_shipping',
        'sku',
        'taxable',
        'title',
        'total_discount',
        'total_discount_set',
        'variant_id',
        'variant_inventory_management',
        'variant_title',
        'vendor',
        'tax_lines',
        'duties',
        'discount_allocations',
        'stored_at',
        'modified_at'
    ];

    public static function getAll($cols = []) {
        $dbController = new DBController();

        $sql = "SELECT * FROM order_line_items";
        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM order_line_items";
        $result = $dbController->executeQuery($sql);

        $order_line_items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $order_line_items[] = $row;
        }

        return $order_line_items;
    }

    public static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM order_line_items WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $line_item = mysqli_fetch_assoc($result);

        return $line_item;
    }

    public static function create($data) {
        $dbController = new DBController();

        $data["line_item_gid"] = $data['admin_graphql_api_id'];
        unset($data['admin_graphql_api_id']);

        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $value = NULL;
                    if($field == "gift_card" || $field == "product_exists" || $field == "requires_shipping" || $field == "taxable") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "attributed_staffs" ||
                    $field == "price_set" ||
                    $field == "properties" ||
                    $field == "total_discount_set" ||
                    $field == "tax_lines" ||
                    $field == "duties" ||
                    $field == "discount_allocations") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $values[] = "'{$value}'";
                }
            }

            $sql = "INSERT INTO order_line_items (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
            if ($dbController->executeQuery($sql)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function update($id, $data) {
        $dbController = new DBController();

        $updates = [];
        foreach (self::$fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = '{$data[$field]}'";
            }
        }

        $sql = "UPDATE order_line_items SET " . implode(",", $updates) . " WHERE id = $id";
        if ($dbController->executeQuery($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public static function delete($id) {
        $dbController = new DBController();

        $sql = "DELETE FROM order_line_items WHERE id = $id";
        if ($dbController->executeQuery($sql)) {
            return true;
        } else {
            return false;
        }
    }
}

