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

$body = file_get_contents('php://input');
$input = json_decode($body, true);
$hInput = getallheaders();
$xSign = $hInput["X-Sign"];
//$s1 = base64_decode($xSign);
$s2 = base64_decode($xSign, true);
include('config.php');
$logUrl .= "-callback";
$log["input"] = $input;

// Functions
{
    function send_forward($inputJSON, $link)
    {
        $request = "POST";
        $descriptor = curl_init($link);
        curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
        curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($descriptor, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);
        $itog = curl_exec($descriptor);
        curl_close($descriptor);
        return $itog;
    }
    function send_request($url, $header, $type = 'GET', $param = [])
    {
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

$check = false;
if (file_exists("pubkey")) {
    $pubkey = file_get_contents("pubkey");
    $signature = base64_decode(getallheaders()["X-Sign"]);
    $publicKey = openssl_get_publickey(base64_decode($getPublicKey["key"]));
    $check = openssl_verify($body, $signature, $publicKey, OPENSSL_ALGO_SHA256);
}
if ($check !== 1) {
    $getPublicKey = json_decode(send_request("https://api.monobank.ua/api/merchant/pubkey", ["X-Token: " . $mono_token]), true);
    $log["getKey"] = $getPublicKey;
    if ($getPublicKey["key"] == NULL) {
        $result["state"] = false;
        $result["error"]["message"][] = "failed get public key";
        send_forward(json_encode($log), $logUrl);
        echo json_encode($result);
        exit;
    } else {
        file_put_contents("pubkey", $getPublicKey["key"]);
    }
    $signature = base64_decode(getallheaders()["X-Sign"]);
    $publicKey = openssl_get_publickey(base64_decode($getPublicKey["key"]));
    $check = openssl_verify($body, $signature, $publicKey, OPENSSL_ALGO_SHA256);
    $log["sign"] = [
        "check" => $check,
    ];
    if ($check !== 1) {
        $result["state"] = false;
        $result["error"]["message"][] = "failed signature";
        send_forward(json_encode($log), $logUrl);
        echo json_encode($result);
        exit;
    }
}
$result["state"] = true;
if ($input["invoiceId"] == NULL) {
    $result["state"] = false;
    $result["error"]["message"]["invoiceId"] = "invoiceId is missing";
}
if ($input["status"] != "success") {
    $result["state"] = false;
    $result["error"]["message"]["status"] = "wait is success";
}
if ($result["state"] != true) {
    echo json_encode($result);
    exit;
}

// Запуск триггера в Smart Sender
$userId = (explode("-", $input["reference"]))[0];
$trigger["name"] = $_GET["action"];
unset($headers);
$headers[] = "Authorization: Bearer " . $ss_token;
$result["SmartSender"] = json_decode(send_request("https://api.smartsender.com/v1/contacts/" . $userId . "/fire", $headers, "POST", $trigger), true);
send_forward(json_encode($log), $logUrl);
echo json_encode($result);
