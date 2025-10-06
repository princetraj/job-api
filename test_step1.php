<?php

$data = json_encode([
    'email' => 'test' . time() . '@test.com',
    'mobile' => '+12345678' . rand(10, 99),
    'name' => 'Test User',
    'password' => 'password123',
    'gender' => 'M'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/auth/register/employee-step1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n";
