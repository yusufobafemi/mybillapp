<?php

// Optional: Shared secret for GitHub webhook
$secret = 'userPassword112233'; // Make this match GitHub webhook secret
$headers = getallheaders();
$signature = $headers['X-Hub-Signature'] ?? '';
$payload = file_get_contents('php://input');

// Verify signature (if using secret)
if ($secret && !hash_equals('sha1=' . hash_hmac('sha1', $payload, $secret), $signature)) {
    http_response_code(403);
    exit('Invalid signature.');
}

// Run the deploy script
$output = shell_exec('/bin/bash /home/mybifqgl/main.mybillapp.com/deploy.sh 2>&1');
echo "<pre>$output</pre>";
