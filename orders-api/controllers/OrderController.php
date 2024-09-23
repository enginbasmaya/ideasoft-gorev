<?php
require_once '../config/db.php';

class OrderController {

    public function getAllOrders() {
        global $pdo;
        $stmt = $pdo->query('SELECT * FROM orders');
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($orders);
    }

    public function addOrder() {
        global $pdo;
        $input = json_decode(file_get_contents('php://input'), true);
        //! input ve stok kontrolü yap
        if (!isset($input['customerId']) || !isset($input['items'])) {
            echo json_encode(['error' => 'Geçersiz veri']);
            http_response_code(400);
            exit;
        }

        //? Sipariş ekleme işlemleri
        $customerId = $input['customerId'];
        $items = $input['items'];

        //! Toplam fiyat hesabı ve stok kontrolü
        $total = 0;

        foreach ($items as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];

            //! Ürün db den kontrol
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['error' => "Ürün bulunamadı: $productId"]);
                http_response_code(404);
                exit;
            }

            //! Stok kontrolü
            if ($product['stock'] < $quantity) {
                echo json_encode(['error' => "Yetersiz stok: {$product['name']}"]);
                http_response_code(400);
                exit;
            }

            //! Toplam fiyat hesabı
            $unitPrice = $product['price'];
            $total += $unitPrice * $quantity;
        }

        //! Siparişi ekleme
        $stmt = $pdo->prepare('INSERT INTO orders (customerId, total) VALUES (:customerId, :total)');
        $stmt->execute(['customerId' => $customerId, 'total' => $total]);
        $orderId = $pdo->lastInsertId();

        //! Sipariş ekle ve stok güncelle
        foreach ($items as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];
            $unitPrice = $item['unitPrice'];

            $stmt = $pdo->prepare('INSERT INTO order_items (orderId, productId, quantity, unitPrice, total) VALUES (:orderId, :productId, :quantity, :unitPrice, :total)');
            $stmt->execute([
                'orderId' => $orderId,
                'productId' => $productId,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'total' => $unitPrice * $quantity
            ]);

            //! Stok güncelle
            $stmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :productId');
            $stmt->execute(['quantity' => $quantity, 'productId' => $productId]);
        }

        echo json_encode(['success' => 'Sipariş eklendi', 'orderId' => $orderId]);
    }

    public function removeOrder($id) {
        global $pdo;
        
        //? Sipariş silme işlemleri

        $orderId = $_GET['id'];

        //! Sipariş var mı kontrolü
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['error' => 'Sipariş bulunamadı']);
            http_response_code(404);
            exit;
        }

        //! Sipariş öğelerini silme
        $stmt = $pdo->prepare('DELETE FROM order_items WHERE orderId = :orderId');
        $stmt->execute(['orderId' => $orderId]);

        //! Siparişi silme
        $stmt = $pdo->prepare('DELETE FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);

        echo json_encode(['success' => 'Sipariş silindi']);
    }
}
?>
