#!/usr/bin/env php
<?php

if ($argc < 4)
{
    echo "use like: php changePhpVersion.php user api_key 7.3\n";
    exit();
}

$user = $argv[1];
$apiKey = $argv[2];
$phpVersion = $argv[3];

function generateRequestSignature($host, $path, $timestamp, $userAgent, $apiKey)
{
    $data = $host . ":" .$path. ":" .$userAgent. ":" .gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    return hash_hmac('sha256', $data, $apiKey);
}

$host = "localhost:10081";
$path = "/ZendServer/Api/changePhpVersion";
$userAgent = "PhpVersionChanger";
$signature = generateRequestSignature($host, $path, time(), $userAgent, $apiKey);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "http://".$host.$path);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "phpVersion=".$phpVersion);

$headers = [
    'Accept: application/vnd.zend.serverapi+json;version=1.16',
    'User-Agent: '. $userAgent,
    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
    'Date: '.gmdate('D, d M Y H:i:s ').'GMT',
    'X-Zend-Signature: '. $user.";".$signature,
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$server_output = curl_exec($ch);
curl_close($ch);
