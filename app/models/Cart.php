<?php
class Cart {
    private static $fields = [
        'cart_id',
        'token',
        'note',
        'created_at',
        'updated_at',
        'stored_at',
        'modified_at',
    ];

    public static function list($cols = []) {
        $dbController = new DBController();

        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM carts";
        $result = $dbController->executeQuery($sql);

        $carts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $carts[] = $row;
        }

        returnResponse(200, "success", "Cart list", $carts);
    }

    private static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM carts WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $cart = mysqli_fetch_assoc($result);

        if ($cart) {
            return $cart;
        } else {
            return false;
        }
    }

    public static function view($id) {
        $cart = self::getById($id);

        if ($cart) {
            returnResponse(200, "success", "Cart details", $cart);
        } else {
            returnResponse(404, "error", "Cart not found");
        }
    }

    public static function getByCartId($cart_id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM carts WHERE cart_id = '$cart_id'";
        $result = $dbController->executeQuery($sql);
        $cart = mysqli_fetch_assoc($result);

        return $cart;
    }

    public static function create($data) {
        $dbController = new DBController();
        $connection = $dbController->getConnection();

        $data["cart_id"] = $data['id'];
        $data["created_at"] = date('Y-m-d H:i:s.v', strtotime($data['created_at']));
        $data["updated_at"] = date('Y-m-d H:i:s.v', strtotime($data['updated_at']));
        unset($data['id']);

        $dbController->executeQuery("START TRANSACTION");
        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $values[] = "'{$data[$field]}'";
                }
            }

            $sql = "INSERT INTO carts (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
            $dbController->executeQuery($sql);
            $last_id = mysqli_insert_id($connection);

            if ($last_id) {
                $new_line_items_count = 0;
                foreach ($data["line_items"] as $line_item) {
                    $line_item["cart_id"] = $last_id;
                    $new_line_items_count += CartLineItem::create($line_item);
                }
                $dbController->executeQuery("COMMIT");
                returnResponse(200, "success", "Cart created successfully with $new_line_items_count line items");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to create cart");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }

    public static function update($id, $data) {
        $cart = self::getById($id);
        if (!$cart) {
            returnResponse(404, "error", "Cart not found");
        }
        $dbController = new DBController();

        $data["cart_id"] = $data['id'];
        $data["created_at"] = date('Y-m-d H:i:s.v', strtotime($data['created_at']));
        $data["updated_at"] = date('Y-m-d H:i:s.v', strtotime($data['updated_at']));
        unset($data['id']);

        $dbController->executeQuery("START TRANSACTION");
        try {
            $updates = [];
            foreach (self::$fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = '{$data[$field]}'";
                }
            }

            $sql = "UPDATE carts SET " . implode(",", $updates) . " WHERE id = $id";

            if ($dbController->executeQuery($sql)) {
                $existing_line_items = CartLineItem::getAll(['id'], $id);
                $existing_line_item_ids = array_column($existing_line_items, "id");
                
                foreach ($data["line_items"] as $line_item) {
                    $line_item["cart_id"] = $id;
                    $existing_line_item = CartLineItem::getByVariantId($line_item["variant_id"]);
                    if($existing_line_item) {
                        $line_item_updated = CartLineItem::update($existing_line_item["id"], $line_item);
                        if($line_item_updated) {
                            if (($key = array_search($existing_line_item["id"], $existing_line_item_ids)) !== false) {
                                unset($existing_line_item_ids[$key]);
                            }
                        }
                    }
                    else {
                        CartLineItem::create($line_item);
                    }
                }
                // die(json_encode($existing_line_item_ids));
                foreach ($existing_line_item_ids as $line_item_id) {
                    CartLineItem::delete($line_item_id);
                }
                $dbController->executeQuery("COMMIT");
                returnResponse(200, "success", "Cart updated successfully");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to update cart");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }

    public static function delete($id) {
        $cart = self::getById($id);
        if (!$cart) {
            returnResponse(404, "error", "Cart not found");
        }

        $dbController = new DBController();

        $sql = "DELETE FROM carts WHERE id = $id";
        if ($dbController->executeQuery($sql)) {
            returnResponse(404, "success", "Cart deleted successfully");
        } else {
            returnResponse(500, "error", "Failed to delete cart");
        }
    }
}
