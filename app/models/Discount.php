<?php
class Discount {
    private static $fields = [
        "discount_id",
        "title",
        "order_count",
        "product_count",
        "variant_count",
        "total_discount",
        'stored_at',
        'modified_at',
    ];

    public static function save($drDiscountWithAmounts) {
        $discountInfo = [];

        foreach ($drDiscountWithAmounts as $discount) {
            $discount_id = $discount['discount_id'];

            // Initialize the discount_id if not already done
            if (!isset($discountInfo[$discount_id])) {
                $discountInfo[$discount_id] = [
                    'discount_id' => $discount_id,
                    'title' => $discount['title'],
                    'total_product' => 0,
                    'total_variants' => 0,
                    'total_amount' => 0,
                ];
            }

            // Increment total_product and totals
            $discountInfo[$discount_id]['total_product']++;
            $discountInfo[$discount_id]['total_variants'] += $discount['variants'];
            $discountInfo[$discount_id]['total_amount'] += $discount['amount'];
        }

        $dbController = new DBController();
        foreach ($discountInfo as $key => $drDiscount) {
            # code...
            $result = $dbController->executeQuery("SELECT * FROM discounts WHERE discount_id = $key");
            $existingDiscount = mysqli_fetch_assoc($result);
            if($existingDiscount) {
                self::update($existingDiscount, $drDiscount);
            }
            else {
                self::create($drDiscount);
            }
        }
    }

    private static function create($drDiscount) {
        $dbController = new DBController();
        $columns = [
            "discount_id",
            "title",
            "order_count",
            "product_count",
            "variant_count",
            "total_discount"
        ];
        $values = [
            "'" . $drDiscount["discount_id"] . "'",
            "'" . $drDiscount["title"] . "'",
            "'1'",
            "'" . $drDiscount["total_product"] . "'",
            "'" . $drDiscount["total_variants"] . "'",
            "'" . $drDiscount["total_amount"] . "'"
        ];

        $sql = "INSERT INTO discounts (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";
        return $dbController->executeQuery($sql);
    }
    private static function update($existingDiscount, $drDiscount) {
        $dbController = new DBController();

        $id = $existingDiscount["id"];
        $updates = [
            "title = '" . $drDiscount["title"] . "'",
            "order_count = '" . $existingDiscount["order_count"] + 1 . "'",
            "product_count = '" . $existingDiscount["product_count"] + $drDiscount["total_product"] . "'",
            "variant_count = '" . $existingDiscount["variant_count"] + $drDiscount["total_variants"] . "'",
            "total_discount = '" . $existingDiscount["total_discount"] + $drDiscount["total_amount"] . "'",
            "modified_at = '" . date('Y-m-d H:i:s.v') . "'",
        ];

        $sql = "UPDATE discounts SET " . implode(",", $updates) . " WHERE id = $id";
        return $dbController->executeQuery($sql);
    }
}