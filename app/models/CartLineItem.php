<?php
class CartLineItem {
    private static $fields = [
        'cart_id',
        'line_item_id',
        'properties',
        'quantity',
        'variant_id',
        'line_item_key',
        'discounted_price',
        'discounted_price_set',
        'discounts',
        'gift_card',
        'grams',
        'line_price',
        'line_price_set',
        'original_line_price',
        'original_line_price_set',
        'original_price',
        'price',
        'price_set',
        'product_id',
        'sku',
        'taxable',
        'title',
        'total_discount',
        'total_discount_set',
        'vendor',
        'stored_at',
        'modified_at',
    ];

    public static function getAll($cols = [], $cart_id = null) {
        $dbController = new DBController();

        $sql = "SELECT * FROM cart_line_items";
        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM cart_line_items";
        if($cart_id) {
            $sql .= " WHERE cart_id = $cart_id";
        }
        $result = $dbController->executeQuery($sql);

        $cart_line_items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cart_line_items[] = $row;
        }

        return $cart_line_items;
    }

    public static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM cart_line_items WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $line_item = mysqli_fetch_assoc($result);

        return $line_item;
    }

    public static function getByLineItemId($cart_id, $id) {
        $dbController = new DBController();

        $sql = "SELECT id FROM cart_line_items WHERE cart_id = $cart_id AND line_item_id = $id";
        $result = $dbController->executeQuery($sql);
        $line_item = mysqli_fetch_assoc($result);

        return $line_item;
    }

    public static function create($data) {
        $dbController = new DBController();

        $data["line_item_id"] = $data['id'];
        $data["line_item_key"] = $data['key'];
        unset($data['id']);
        unset($data['key']);

        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $value = NULL;
                    if($field == "gift_card" || $field == "taxable") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "properties" ||
                    $field == "discounted_price_set" ||
                    $field == "discounts" ||
                    $field == "line_price_set" ||
                    $field == "original_line_price_set" ||
                    $field == "price_set" ||
                    $field == "total_discount_set") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $values[] = "'{$value}'";
                }
            }

            $sql = "INSERT INTO cart_line_items (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
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

        $data["line_item_id"] = $data['id'];
        $data["line_item_key"] = $data['key'];
        $data["modified_at"] = date('Y-m-d H:i:s.v');
        unset($data['id']);
        unset($data['key']);

        try {
            $updates = [];
            foreach (self::$fields as $field) {
                if (isset($data[$field])) {
                    $value = NULL;
                    if($field == "gift_card" || $field == "taxable") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "properties" ||
                    $field == "discounted_price_set" ||
                    $field == "discounts" ||
                    $field == "line_price_set" ||
                    $field == "original_line_price_set" ||
                    $field == "price_set" ||
                    $field == "total_discount_set") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $updates[] = "$field = '{$value}'";
                }
            }
            $updates[] = "destroyed_at = NULL";

            $sql = "UPDATE cart_line_items SET " . implode(",", $updates) . " WHERE id = $id";
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

        $sql = "UPDATE cart_line_items SET destroyed_at = NOW() WHERE id = $id;";
        if ($dbController->executeQuery($sql)) {
            return true;
        } else {
            return false;
        }
    }
}

