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
if ($input["amount"] == NULL) {
    $result["state"] = false;
    $result["message"]["amount"] = "amount is missing";
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
$amount = str_replace(array(",", " "), array(".", ""), $input["amount"]);
$sendData["amount"] = $amount * 100;
if ($input["currency"] == "USD") {
    $sendData["ccy"] = 840;
} else if ($input["currency"] == "EUR") {
    $sendData["ccy"] = 978;
}
$sendData["merchantPaymInfo"]["reference"] = $input["userId"]."-".mt_rand(1000000, 9999999);
if ($input["description"] != NULL) {
    $sendData["merchantPaymInfo"]["destination"] = $input["description"];
}
if ($input["redirectUrl"] != NULL) {
    $sendData["redirectUrl"] = $input["redirectUrl"];
}
$sendData["webHookUrl"] = $url."/callback.php?action=".$input["action"];
$headers[] = "X-Token: ".$mono_token;

$result["result"] = json_decode(send_request("https://api.monobank.ua/api/merchant/invoice/create", $headers, "POST", $sendData), true);
$result["sendData"] = $sendData;
$result["headers"] = $headers;


echo json_encode($result);




