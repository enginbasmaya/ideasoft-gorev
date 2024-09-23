<?php
header("Content-Type: application/json");

//! POST isteği ile sadece veriler alma
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Sadece POST isteği ile veri alınabilir']);
    exit();
}

//! İstekten JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);

//! İndirim kuralları fonksiyonları
function apply_discount_for_category_2($items) {
    $discount = 0.0;
    foreach ($items as $item) {
        if ($item['categoryId'] == 2 && $item['quantity'] >= 6) {
            $free_items = floor($item['quantity'] / 6);
            $discount += $free_items * $item['price'];
        }
    }
    return $discount;
}

function apply_discount_for_category_1($items) {
    $category_1_items = array_filter($items, function($item) {
        return $item['categoryId'] == 1;
    });

    if (count($category_1_items) >= 2) {
        $prices = array_column($category_1_items, 'price');
        sort($prices);
        $cheapest_item_price = $prices[0];
        return $cheapest_item_price * 0.2;
    }

    return 0.0;
}

function apply_10_percent_discount_if_over_1000($total) {
    if ($total >= 1000) {
        return $total * 0.1;
    }
    return 0.0;
}

//! İndirim hesaplama fonksiyonu
function calculate_discounts($order) {
    $items = $order['items'];
    $total = 0.0;

    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $discounts = [];
    $total_discount = 0.0;

    //! 6 al 1 bedava (Kategori 2 için)
    $category_2_discount = apply_discount_for_category_2($items);
    if ($category_2_discount > 0) {
        $discounts[] = [
            "discountReason" => "BUY_5_GET_1",
            "discountAmount" => number_format($category_2_discount, 2),
            "subtotal" => number_format($total, 2)
        ];
        $total_discount += $category_2_discount;
    }

    //! En ucuz kategori 1 ürününe %20 indirim
    $category_1_discount = apply_discount_for_category_1($items);
    if ($category_1_discount > 0) {
        $discounts[] = [
            "discountReason" => "CATEGORY_1_CHEAPEST_20_PERCENT",
            "discountAmount" => number_format($category_1_discount, 2),
            "subtotal" => number_format($total - $total_discount, 2)
        ];
        $total_discount += $category_1_discount;
    }

    //! Toplam 1000 TL üzerindeyse %10 indirim
    $over_1000_discount = apply_10_percent_discount_if_over_1000($total - $total_discount);
    if ($over_1000_discount > 0) {
        $discounts[] = [
            "discountReason" => "10_PERCENT_OVER_1000",
            "discountAmount" => number_format($over_1000_discount, 2),
            "subtotal" => number_format($total - $total_discount, 2)
        ];
        $total_discount += $over_1000_discount;
    }

    $discounted_total = $total - $total_discount;

    return [
        "orderId" => $order['orderId'],
        "discounts" => $discounts,
        "totalDiscount" => number_format($total_discount, 2),
        "discountedTotal" => number_format($discounted_total, 2)
    ];
}

//! İndirim hesaplamayı tetikleyip JSON olarak döndür
$response = calculate_discounts($input);
echo json_encode($response);

?>
