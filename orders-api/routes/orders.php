<?php

require_once '../controllers/OrderController.php';

function listOrders() {
    $controller = new OrderController();
    $controller->getAllOrders();
}

function createOrder() {
    $controller = new OrderController();
    $controller->addOrder();
}

function deleteOrder($id) {
    $controller = new OrderController();
    $controller->removeOrder($id);
}
?>
