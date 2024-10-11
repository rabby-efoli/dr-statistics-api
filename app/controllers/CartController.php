<?php
class CartController {
    public function index() {
        returnResponse(200, "success", "Response data");
        return Cart::list();
    }

    public function store($data) {
        return Cart::create($data["cart"]);
    }

    public function update($data) {
        $existingCart = Cart::getByCartId($data["cart"]["id"]);
        if($existingCart) {
            return Cart::update($existingCart["id"], $data["cart"]);
        }
        else {
            return Cart::create($data["cart"]);
        }
    }
}