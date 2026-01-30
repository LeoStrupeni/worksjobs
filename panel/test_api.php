<?php

$ch = curl_init('http://192.168.1.4/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'leonardo.strupeni@gmail.com',
    'password' => '1234'
]));

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "TOKEN: " . ($data['data']['token'] ?? 'NO TOKEN') . "\n";
echo "\n--- Testing /jobs/today ---\n";

// Test /jobs/today
$token = $data['data']['token'];
$ch2 = curl_init('http://192.168.1.4/api/jobs/today');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
$response2 = curl_exec($ch2);
curl_close($ch2);
echo $response2 . "\n";

echo "\n--- Testing /jobs/calendar ---\n";

// Test /jobs/calendar
$ch3 = curl_init('http://192.168.1.4/api/jobs/calendar?start_date=2026-01-01&end_date=2026-01-31');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
$response3 = curl_exec($ch3);
curl_close($ch3);
echo $response3 . "\n";
