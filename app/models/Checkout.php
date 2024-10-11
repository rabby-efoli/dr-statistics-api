<?php
class Checkout {
    private static $fields = [
        'checkout_id',
        'token',
        'cart_token',
        'email',
        'gateway',
        'buyer_accepts_marketing',
        'buyer_accepts_sms_marketing',
        'created_at',
        'updated_at',
        'landing_site',
        'note',
        'note_attributes',
        'referring_site',
        'shipping_lines',
        'shipping_address',
        'taxes_included',
        'total_weight',
        'currency',
        'completed_at',
        'customer_locale',
        'name',
        'abandoned_checkout_url',
        'discount_codes',
        'tax_lines',
        'presentment_currency',
        'source_name',
        'total_line_items_price',
        'total_tax',
        'total_discounts',
        'subtotal_price',
        'total_price',
        'total_duties',
        'device_id',
        'user_id',
        'location_id',
        'source_identifier',
        'source_url',
        'source',
        'closed_at',
        'billing_address',
        'customer',
        'stored_at',
        'modified_at'
    ];

    public static function list($cols = []) {
        $dbController = new DBController();

        $columns = $cols ? implode(",", $cols) : "*";
        $sql = "SELECT $columns FROM checkouts";
        $result = $dbController->executeQuery($sql);

        $checkouts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $checkouts[] = $row;
        }

        returnResponse(200, "success", "Checkout list", $checkouts);
    }

    private static function getById($id) {
        $dbController = new DBController();

        $sql = "SELECT * FROM checkouts WHERE id = $id";
        $result = $dbController->executeQuery($sql);
        $checkout = mysqli_fetch_assoc($result);

        if ($checkout) {
            return $checkout;
        } else {
            return false;
        }
    }

    public static function view($id) {
        $checkout = self::getById($id);

        if ($checkout) {
            returnResponse(200, "success", "Checkout details", $checkout);
        } else {
            returnResponse(404, "error", "Checkout not found");
        }
    }

    public static function getByCheckoutId($checkout_id) {
        $dbController = new DBController();

        $sql = "SELECT id FROM checkouts WHERE checkout_id = '$checkout_id'";
        $result = $dbController->executeQuery($sql);
        $checkout = mysqli_fetch_assoc($result);

        return $checkout;
    }

    public static function create($data) {
        $dbController = new DBController();
        $connection = $dbController->getConnection();

        $data["checkout_id"] = $data['id'];
        $line_items = $data["line_items"];
        $data["created_at"] = date('Y-m-d H:i:s.v', strtotime($data['created_at']));
        $data["updated_at"] = date('Y-m-d H:i:s.v', strtotime($data['updated_at']));
        unset($data['id']);
        unset($data['line_items']);

        $dbController->executeQuery("START TRANSACTION");
        try {
            $columns = [];
            $values = [];

            foreach (self::$fields as $field) {
                if(isset($data[$field])) {
                    $columns[] = $field;
                    $value = NULL;
                    if($field == "buyer_accepts_marketing" || $field == "buyer_accepts_sms_marketing" || $field == "taxes_included") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "gateway" ||
                    $field == "note" ||
                    $field == "note_attributes" ||
                    $field == "shipping_lines" ||
                    $field == "shipping_address" ||
                    $field == "abandoned_checkout_url" ||
                    $field == "discount_codes" ||
                    $field == "tax_lines" ||
                    $field == "source_url" ||
                    $field == "source" ||
                    $field == "billing_address" ||
                    $field == "customer") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $values[] = "'{$value}'";
                }
            }

            $sql = "INSERT INTO checkouts (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
            $dbController->executeQuery($sql);
            $last_id = mysqli_insert_id($connection);

            if ($last_id) {
                $new_line_items_count = 0;
                foreach ($line_items as $line_item) {
                    $line_item["checkout_id"] = $last_id;
                    $new_line_items_count += CheckoutLineItem::create($line_item);
                }
                $dbController->executeQuery("COMMIT");
                returnResponse(200, "success", "Checkout created successfully with $new_line_items_count line items");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to create checkout");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }

    public static function update($id, $data) {
        $checkout = self::getById($id);
        if (!$checkout) {
            returnResponse(404, "error", "Checkout not found");
        }
        $dbController = new DBController();

        $data["checkout_id"] = $data['id'];
        $line_items = $data["line_items"];
        $data["created_at"] = date('Y-m-d H:i:s.v', strtotime($data['created_at']));
        $data["updated_at"] = date('Y-m-d H:i:s.v', strtotime($data['updated_at']));
        unset($data['id']);
        unset($data['line_items']);

        $dbController->executeQuery("START TRANSACTION");
        try {
            $updates = [];
            foreach (self::$fields as $field) {
                if (isset($data[$field])) {
                    $value = NULL;
                    if($field == "buyer_accepts_marketing" || $field == "buyer_accepts_sms_marketing" || $field == "taxes_included") {
                        $value = $data[$field] ? 1 : 0;
                    }
                    else if($field == "gateway" ||
                    $field == "note" ||
                    $field == "note_attributes" ||
                    $field == "shipping_lines" ||
                    $field == "shipping_address" ||
                    $field == "abandoned_checkout_url" ||
                    $field == "discount_codes" ||
                    $field == "tax_lines" ||
                    $field == "source_url" ||
                    $field == "source" ||
                    $field == "billing_address" ||
                    $field == "customer") {
                        $value = json_encode($data[$field]) ?? NULL;
                    }
                    else {
                        $value = $data[$field];
                    }
                    $updates[] = "$field = '{$value}'";
                }
            }

            $sql = "UPDATE checkouts SET " . implode(",", $updates) . " WHERE id = $id";

            if ($dbController->executeQuery($sql)) {
                $existing_line_items = CheckoutLineItem::getAll(['id'], $id);
                $existing_line_item_ids = array_column($existing_line_items, "id");
                
                foreach ($line_items as $line_item) {
                    $line_item["checkout_id"] = $id;
                    $existing_line_item = CheckoutLineItem::getByKey($id, $line_item["key"]);
                    if($existing_line_item) {
                        $line_item_updated = CheckoutLineItem::update($existing_line_item["id"], $line_item);
                        if($line_item_updated) {
                            if (($index = array_search($existing_line_item["id"], $existing_line_item_ids)) !== false) {
                                unset($existing_line_item_ids[$index]);
                            }
                        }
                    }
                    else {
                        CheckoutLineItem::create($line_item);
                    }
                }
                foreach ($existing_line_item_ids as $line_item_id) {
                    CheckoutLineItem::delete($line_item_id);
                }
                $dbController->executeQuery("COMMIT");
                returnResponse(200, "success", "Checkout updated successfully");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to update checkout");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }

    public static function delete($id) {
        $checkout = self::getById($id);
        if (!$checkout) {
            returnResponse(404, "error", "Checkout not found");
        }

        $dbController = new DBController();

        $dbController->executeQuery("START TRANSACTION");
        try {
            $sql = "UPDATE checkouts SET destroyed_at = NOW() WHERE id = $id;";
            if ($dbController->executeQuery($sql)) {
                $existing_line_items = CheckoutLineItem::getAll(['id'], $id);
                $existing_line_item_ids = array_column($existing_line_items, "id");

                foreach ($existing_line_item_ids as $line_item_id) {
                    CheckoutLineItem::delete($line_item_id);
                }

                $dbController->executeQuery("COMMIT");
                returnResponse(404, "success", "Checkout deleted successfully");
            } else {
                $dbController->executeQuery("ROLLBACK");
                returnResponse(500, "error", "Failed to delete checkout");
            }
        } catch (\Exception $e) {
            $dbController->executeQuery("ROLLBACK");
            returnResponse(500, "error", $e->getMessage());
        }
    }
}
