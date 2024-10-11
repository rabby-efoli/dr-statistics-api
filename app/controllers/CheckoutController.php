<?php
class CheckoutController {
    public function index() {
        returnResponse(200, "success", "Response data");
        return Checkout::list();
    }

    public function createUpdate($data) {
        $existingCheckout = Checkout::getByCheckoutId($data["checkout"]["id"]);
        if($existingCheckout) {
            return Checkout::update($existingCheckout["id"], $data["checkout"]);
        }
        else {
            return Checkout::create($data["checkout"]);
        }
    }

    public function destroy($data) {
        $existingCheckout = Checkout::getByCheckoutId($data["checkout"]["id"]);
        if($existingCheckout) {
            return Checkout::delete($existingCheckout["id"]);
        }
        else {
            returnResponse(404, "error", "Checkout not found");
        }
    }
}