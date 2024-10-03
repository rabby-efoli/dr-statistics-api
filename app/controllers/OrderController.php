<?php

class OrderController {
    public function index() {
        die(json_encode([
            'req' => "Request",
            'cols' => $_GET["columns"]
        ]));
        return Order::list();
    }

    public function show($id) {
        return Order::view($id);
    }

    public function store($data) {
        $orderData = $data["order"];

        // Initialize an array to hold total discount allocations for each discount
        $totalDiscountAllocations = [];

        // Iterate over each discount application
        foreach ($orderData["discount_applications"] as $index => $discount) {
            $totalDiscountAllocations[$index] = [
                "title" => $discount["title"],
                "total_amount" => 0.0
            ];

            // Iterate over each line item
            foreach ($orderData["line_items"] as $line_item) {
                // Check if the line item has any discount allocations
                if (!empty($line_item["discount_allocations"])) {
                    foreach ($line_item["discount_allocations"] as $allocation) {
                        // Check if the discount application index matches
                        if ($allocation["discount_application_index"] == $index) {
                            // Sum the amount
                            $totalDiscountAllocations[$index]["total_amount"] += (float)$allocation["amount"];
                        }
                    }
                }
            }
        }

        $drDiscountAmounts = [];
        $drDiscountTotal = 0;

        foreach ($totalDiscountAllocations as $discount) {
            foreach ($data['discounts'] as $ourDiscount) {
                if ($ourDiscount['title'] === $discount['title']) {
                    $drDiscountAmounts[] = [
                        "id" => $ourDiscount['id'],
                        "title" => $discount['title'],
                        "amount" => $discount['total_amount'],
                    ]; // Store the amount for our discounts
                    $drDiscountTotal += $discount['total_amount']; // Sum up our discounts
                    break; // Break inner loop if found
                }
            }
        }

        $orderData["dr_discount_applied"] = !empty($drDiscountAmounts);
        $orderData["dr_discount_amounts"] = $drDiscountAmounts;
        $orderData["dr_discount_total"] = $drDiscountTotal;

        return Order::create($orderData);
    }

    public function update($id, $data) {
        return Order::update($id, $data);
    }

    public function destroy($id) {
        return Order::delete($id);
    }
}
