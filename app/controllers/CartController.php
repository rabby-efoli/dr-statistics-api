<?php
class CartController {
    public function index() {
        // die(json_encode([
        //     'req' => "Request",
        //     'cols' => $_GET["columns"]
        // ]));
        return Cart::list();
    }

    public function show($id) {
        return Cart::view($id);
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

    public function destroy($id) {
        return Cart::delete($id);
    }
}