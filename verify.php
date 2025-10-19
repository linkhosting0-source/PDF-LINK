<?php
// Your Cloudflare Turnstile secret key (updated)
$secret = "0x4AAAAAABh8YcIQd2HRXyi6y5HpRNDc87I";

// Get Turnstile response token from POST
$token = $_POST['cf-turnstile-response'] ?? '';
$remoteip = $_SERVER['REMOTE_ADDR'] ?? '';
$hash = $_POST['hash'] ?? '';

// Verify Turnstile with Cloudflare API
$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "content" => http_build_query([
            "secret" => $secret,
            "response" => $token,
            "remoteip" => $remoteip,
        ])
    ]
];
$context = stream_context_create($options);
$verifyResponse = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, $context);

// Decode JSON response from Cloudflare
$result = json_decode($verifyResponse, true);

// If CAPTCHA is successful
if (!empty($result['success'])) {
    // Redirect to check.html preserving the hash if any
    $location = 'check.html' . $hash;
    header("Location: $location");
    exit;
} else {
    // CAPTCHA failed message
    http_response_code(403);
    echo '<h2 style="color: white; background:#1d1d1d; text-align:center; padding:40px; font-family:sans-serif;">';
    echo 'CAPTCHA verification failed. Please <a href="index.html" style="color:#0099ff;">try again</a>.';
    echo '</h2>';
}
?>
