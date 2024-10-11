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
        if($params["dataDuration"] == "15days") {
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
            $data["totalOrders"] = round($totalOrdersResult[0]);
            if($totalOrdersResult[1] == 0) {
                $data["totalOrderDifference"] = $totalOrdersResult[0] > 0 ? 100 : 0;
            }
            else {
                $totalOrderDifference = (($totalOrdersResult[0] - $totalOrdersResult[1]) / $totalOrdersResult[1]) * 100;
                $data["totalOrderDifference"] = round($totalOrderDifference, 1);
            }
        }
        if($dataField == "drOrders" || $dataField == "all") {
            $drOrdersResult = Order::getDrOrders($dataDurationStart, $dataDurationEnd);
            $data["drOrders"] = round($drOrdersResult[0]);
            if($drOrdersResult[1] == 0) {
                $data["drOrderDifference"] = $drOrdersResult[0] > 0 ? 100 : 0;
            }
            else {
                $drOrderDifference = (($drOrdersResult[0] - $drOrdersResult[1]) / $drOrdersResult[1]) * 100;
                $data["drOrderDifference"] = round($drOrderDifference, 1);
            }
        }
        if($dataField == "totalSale" || $dataField == "all") {
            $totalSaleResult = Order::getTotalSale($dataDurationStart, $dataDurationEnd);
            $data["totalSale"] = round($totalSaleResult[0]);
            if($totalSaleResult[1] == 0) {
                $data["totalSaleDifference"] = $totalSaleResult[0] > 0 ? 100 : 0;
            }
            else {
                $totalSaleDifference = (($totalSaleResult[0] - $totalSaleResult[1]) / $totalSaleResult[1]) * 100;
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
            foreach ($request['discounts'] as $ourDiscount) {
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
}
