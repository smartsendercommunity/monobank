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
$hInput = getallheaders();
$xSign = $hInput["X-Sign"];
//$s1 = base64_decode($xSign);
$s2 = base64_decode($xSign, true);
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

if ($input["invoiceId"] == NULL) {
    $result["state"] = false;
    $result["error"]["message"]["invoiceId"] = "invoiceId is missing";
}
if ($input["status"] != "success") {
    $result["state"] = false;
    $result["error"]["message"]["status"] = "wait is success";
}
if ($result["state"] === false) {
    echo json_encode($result);
    exit;
} else {
    $headers[] = "X-Token: ".$mono_token;
    $getInvoice = json_decode(send_request("https://api.monobank.ua/api/merchant/invoice/status?invoiceId=".$input["invoiceId"], $headers), true);
    if ($getInvoice["status"] != "success") {
        $result["state"] = false;
        $result["error"]["message"] = "webhook is fake";
        echo json_encode($result);
        exit;
    }
}

// Запуск триггера в Smart Sender
$userId = (explode("-", $input["reference"]))[0];
$trigger["name"] = $_GET["action"];
unset($headers);
$headers[] = "Authorization: Bearer ".$ss_token;
$result["SmartSender"] = json_decode(send_request("https://api.smartsender.com/v1/contacts/".$userId."/fire", $headers, "POST", $trigger), true);

echo json_encode($result);












