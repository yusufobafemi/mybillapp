<?php

$secret = 'userPassword112233'; // This should match GitHub webhook secret

$headers = getallheaders();
$signature = $headers['X-Hub-Signature-256'] ?? '';
$payload = file_get_contents('php://input');

// Compute expected signature
// $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// // Securely compare signatures
// if (!hash_equals($expected, $signature)) {
//     http_response_code(403);
//     exit('Invalid signature.');
// }

// Run your deployment script
$output = shell_exec('/home/mybifqgl/main.mybillapp.com/deploy.sh 2>&1');

echo "<pre>$output</pre>";
