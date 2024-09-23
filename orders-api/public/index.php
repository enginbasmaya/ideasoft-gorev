<?php

require_once '../config/db.php';
require_once '../routes/orders.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/orders':
        if ($method === 'GET') {
            listOrders(); //! Sipariş listeleme
        } elseif ($method === 'POST') {
            createOrder(); //! Sipariş ekleme
        }
        break;

    case preg_match('/\/orders\/\d+/', $uri) ? true : false:
        if ($method === 'DELETE') {
            deleteOrder(basename($uri)); //! Sipariş silme
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint bulunamadı']);
        break;
}
?>
