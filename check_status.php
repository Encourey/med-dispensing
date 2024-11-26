<?php
// ESP32 IP address
$esp32_ip = "http://192.168.1.51/status"; // Replace with your ESP32's IP address

// Use cURL to send a request to the ESP32
$ch = curl_init($esp32_ip);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Set timeout to 2 seconds

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if the ESP32 responded successfully
if ($http_status === 200 && $response === "enabled") {
    // ESP32 is reachable and status is "enabled"
    echo json_encode(["status" => "enabled"]);
} else {
    // ESP32 is unreachable or status is unknown
    echo json_encode(["status" => "disabled"]);
}
?>
