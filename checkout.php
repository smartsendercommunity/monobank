<?php

// v1   19.11.2021
// Powered by Smart Sender
// https://smartsender.com

ini_set('max_execution_time', '1700');
set_time_limit(1700);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');

// Functions
{
function send_request($url, $header, $type = 'GET', $param = []) {
    $descriptor = curl_init($url);
    if ($type != "GET") {
        curl_setopt($descriptor, CURLOPT_POSTFIELDS, json_encode($param));
        $header[] = 'Content-Type: application/json';
    }
    $header[] = 'User-Agent: Soft-M(https://api.soft-m.ml)';
    curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($descriptor, CURLOPT_HTTPHEADER, $header); 
    curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $type);
    $itog = curl_exec($descriptor);
    curl_close($descriptor);
    return $itog;
}
}

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
}
if ($input["action"] == NULL) {
    $result["state"] = false;
    $result["message"]["action"] = "action is missing";
}
if ($result["state"] === false) {
    http_response_code(422);
    echo json_encode($result);
    exit;
}

// Формирование данных
$sendData["merchantPaymInfo"]["reference"] = $input["userId"]."-".mt_rand(1000000, 9999999);
if ($input["description"] != NULL) {
    $sendData["merchantPaymInfo"]["destination"] = $input["description"];
}
if ($input["redirectUrl"] != NULL) {
    $sendData["redirectUrl"] = $input["redirectUrl"];
}
$sendData["webHookUrl"] = $url."/callback.php?action=".$input["action"];

// Получение списка товаров в корзине пользователя
$headers[] = "Authorization: Bearer ".$ss_token;
$cursor = json_decode(send_request("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=1&limitation=20", $headers), true);
if ($cursor["error"] != NULL && $cursor["error"] != 'undefined') {
    $result["status"] = "error";
    $result["message"][] = "Ошибка получения данных из SmartSender";
    if ($cursor["error"]["code"] == 404 || $cursor["error"]["code"] == 400) {
        $result["message"][] = "Пользователь не найден. Проверте правильность идентификатора пользователя и приналежность токена к текущему проекту.";
    } else if ($cursor["error"]["code"] == 403) {
        $result["message"][] = "Токен проекта SmartSender указан неправильно. Проверте правильность токена.";
    }
    echo json_encode($result);
    exit;
} else if (empty($cursor["collection"])) {
    $result["status"] = "error";
    $result["message"][] = "Корзина пользователя пустая. Для тестирования добавте товар в корзину.";
    echo json_encode($result);
    exit;
}
$pages = $cursor["cursor"]["pages"];
for ($i = 1; $i <= $pages; $i++) {
    $checkout = json_decode(send_request("https://api.smartsender.com/v1/contacts/".$input["userId"]."/checkout?page=".$i."&limitation=20", $headers), true);
    $essences = $checkout["collection"];
    $currency = $essences[0]["cash"]["currency"];
    foreach ($essences as $product) {
        $items["name"] = $product["product"]["name"] . " " . $product["name"];
        $items["qty"] = $product["pivot"]["quantity"];
        $items["sum"] = $product["cash"]["amount"] * 100;
        $sum[] = $product["cash"]["amount"] * $product["pivot"]["quantity"] * 100;
        if (file_exists("media/" . $product["product"]["id"] . "/" . $product["id"] . ".jpg")) {
            $items["icon"] = $url . "/media/" . $product["product"]["id"] . "/" . $product["id"] . ".jpg";
        } else if (file_exists("media/" . $product["product"]["id"] . ".jpg")) {
            $items["icon"] = $url . "/media/" . $product["product"]["id"] . ".jpg";
        } else if (file_exists("media/default.jpg")) {
            $items["icon"] = $url . "/media/default.jpg";
        }
        // Податкова ставка (Тільки для "Вчасно.Каса")
        // $items["tax"] = [2];
        // Використовуйте одне з наступних значень:
        // 1 - ПДВ 20%
        // 2 - Без ПДВ
        // 3 - ПДВ 20% + акциз 5%
        // 4 - ПДВ 7%
        // 5 - ПДВ 0%
        // 6 - Без ПДВ + акциз 5%
        // 7 - Не є об'єктом ПДВ
        // 8 - ПДВ 20% + ПФ 7.5%
        // 9 - ПДВ 14%
        $sendData["merchantPaymInfo"]["basketOrder"][] = $items;
        unset($items);
    }
}
$sendData["amount"] = array_sum($sum);
if ($currency == "USD") {
    $sendData["ccy"] = 840;
} else if ($currency = "EUR") {
    $sendDara["ccy"] = 978;
}
unset($headers);
$headers[] = "X-Token: ".$mono_token;

$result["result"] = json_decode(send_request("https://api.monobank.ua/api/merchant/invoice/create", $headers, "POST", $sendData), true);
$result["sendData"] = $sendData;
$result["headers"] = $headers;


echo json_encode($result);






