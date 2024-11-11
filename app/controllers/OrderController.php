<?php

class OrderController {
    public function index() {
        returnResponse(200, "success", "Response data");
        return Order::list();
    }

    public function dashboardData($request) {
        $params = $request["params"];
        $dataField = $params["dataField"];
        $dataDurationStart = 0;
        $dataDurationEnd = 1;
        if($params["dataDuration"] == "7days") {
            $dataDurationStart = 7;
            $dataDurationEnd = 14;
        }
        else if($params["dataDuration"] == "15days") {
            $dataDurationStart = 15;
            $dataDurationEnd = 30;
        }
        else if($params["dataDuration"] == "30days") {
            $dataDurationStart = 30;
            $dataDurationEnd = 60;
        }

        $data = [];
        if($dataField == "totalOrders" || $dataField == "all") {
            $totalOrdersResult = Order::getTotalOrders($dataDurationStart, $dataDurationEnd);
            $totalOrders = $totalOrdersResult[0] ?? 0;
            $totalPrevOrders = $totalOrdersResult[1] ?? 0;
            $data["totalOrders"] = round($totalOrders);
            if($totalPrevOrders == 0) {
                $data["totalOrderDifference"] = $totalOrders > 0 ? 100 : 0;
            }
            else {
                $totalOrderDifference = (($totalOrders - $totalPrevOrders) / $totalPrevOrders) * 100;
                $data["totalOrderDifference"] = round($totalOrderDifference, 1);
            }
        }
        if($dataField == "drOrders" || $dataField == "all") {
            $drOrdersResult = Order::getDrOrders($dataDurationStart, $dataDurationEnd);
            $drOrders = $drOrdersResult[0] ?? 0;
            $drPrevOrders = $drOrdersResult[1] ?? 0;
            $data["drOrders"] = round($drOrders);
            if($drPrevOrders == 0) {
                $data["drOrderDifference"] = $drOrders > 0 ? 100 : 0;
            }
            else {
                $drOrderDifference = (($drOrders - $drPrevOrders) / $drPrevOrders) * 100;
                $data["drOrderDifference"] = round($drOrderDifference, 1);
            }
        }
        if($dataField == "totalSale" || $dataField == "all") {
            $totalSaleResult = Order::getTotalSale($dataDurationStart, $dataDurationEnd);
            $totalSale = $totalSaleResult[0] ?? 0;
            $totalPrevSale = $totalSaleResult[1] ?? 0;
            $data["totalSale"] = round($totalSale);
            if($totalPrevSale == 0) {
                $data["totalSaleDifference"] = $totalSale > 0 ? 100 : 0;
            }
            else {
                $totalSaleDifference = (($totalSale - $totalPrevSale) / $totalPrevSale) * 100;
                $data["totalSaleDifference"] = round($totalSaleDifference, 1);
            }
        }
        returnResponse(200, "success", "Dashboard data", $data);
    }

    public function store($request) {
        $orderData = $request["order"];

        // Initialize an array to hold total discount allocations for each discount
        $totalDiscountAllocations = [];

        // Iterate over each discount application
        foreach ($orderData["discount_applications"] as $index => $discountApplication) {
            $totalDiscountAllocations[$index] = [
                "title" => $discountApplication["title"],
                "total_amount" => 0.0,
                "variant_count" => 0,
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
                            $totalDiscountAllocations[$index]["variant_count"] += (int)$line_item["quantity"];
                        }
                    }
                }
            }
        }

        $drDiscountWithAmounts = [];
        $drDiscountTotal = 0;

        foreach ($totalDiscountAllocations as $discount) {
            foreach ($request['discounts'] as $ourDiscount) {
                if ($ourDiscount['title'] === $discount['title']) {
                    $drDiscountWithAmounts[] = [
                        "discount_id" => $ourDiscount['id'],
                        "title" => $discount['title'],
                        "amount" => $discount['total_amount'],
                        "variants" => $discount['variant_count'],
                    ]; // Store the amount for our discounts
                    $drDiscountTotal += $discount['total_amount']; // Sum up our discounts
                    break; // Break inner loop if found
                }
            }
        }

        $orderData["dr_discount_applied"] = !empty($drDiscountWithAmounts);
        $orderData["dr_discount_amounts"] = $drDiscountWithAmounts;
        $orderData["dr_discount_total"] = $drDiscountTotal;

        Discount::save($drDiscountWithAmounts);

        return Order::create($orderData);
    }
}
