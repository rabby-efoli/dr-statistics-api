<?php
// Set the content type to JSON and enable CORS for all origins
header('Content-type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// This script check if the request is comming from proper source
require_once "../config/auth.php";

// These are necessary configuration files
require_once "../config/database.php";
require_once "../config/helpers.php";

// These are required Controllers and Models
include_once "../app/controllers/OrderController.php";
include_once "../app/models/Order.php";
include_once "../app/models/OrderLineItem.php";
include_once "../app/controllers/CartController.php";
include_once "../app/models/Cart.php";
include_once "../app/models/CartLineItem.php";

if($_SERVER['REQUEST_METHOD'] != "POST") {
    returnResponse(404, "error", "Method not allowed");
}

// Parse the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Decode the incoming JSON request body
$request = json_decode(file_get_contents('php://input'), true);

// Check if the URI has a valid resource identifier
if ($uri[2] && $uri[2] != "") {

    // Handle "orders" endpoint
    if ($uri[2] == "orders") {
        $orderController = new OrderController();

        // Handle specific actions for orders based on the URI
        if ($uri[3] && $uri[3] != "") {
            if ($uri[3] == "create") {
                return $orderController->store($request);
            } else if ($uri[3] == "update" && $uri[4] && $uri[4] != "") {
                return $orderController->update($uri[4], $request);
            } else if ($uri[3] == "view" && $uri[4] && $uri[4] != "") {
                return $orderController->show($uri[4]);
            } else if ($uri[3] == "delete" && $uri[4] && $uri[4] != "") {
                return $orderController->destroy($uri[4]);
            } else {
                // Return 404 if the action is not recognized
                returnResponse(404, "error", "Not found!");
            }
        } else {
            // Return a list of orders if no specific action is provided
            return $orderController->index();
        }
    }
    // Handle "carts" endpoint
    else if ($uri[2] == "carts") {
        $cartController = new CartController();

        // Handle specific actions for carts based on the URI
        if ($uri[3] && $uri[3] != "") {
            if ($uri[3] == "create") {
                return $cartController->store($request);
            } else if ($uri[3] == "update") {
                return $cartController->update($request);
            } else if ($uri[3] == "view" && $uri[4] && $uri[4] != "") {
                return $cartController->show($uri[4]);
            } else if ($uri[3] == "delete" && $uri[4] && $uri[4] != "") {
                return $cartController->destroy($uri[4]);
            } else {
                // Return 404 if the action is not recognized
                returnResponse(404, "error", "Not found!");
            }
        } else {
            // Return a list of carts if no specific action is provided
            return $cartController->index();
        }
    }
    else if ($uri[2] == "init") {
        $db = new DBController();
        returnResponse(200, "success", "System initialized");
    }
    // Return 404 if the resource is not recognized
    else {
        returnResponse(404, "error", "Not found!");
    }
} else {
    // Return 404 if the URI does not specify a valid resource
    returnResponse(404, "error", "Not found!");
}