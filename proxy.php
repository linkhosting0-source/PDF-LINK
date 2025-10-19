<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
$url = urldecode($_GET['url']);
if (strpos($url, 'ipapi.co') === false && strpos($url, 'api.telegram.org') === false) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid URL']);
    exit;
}
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
}
$response = curl_exec($ch);
curl_close($ch);
echo $response;
?>