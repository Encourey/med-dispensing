<?php
header('Content-Type: application/json');

// Read JSON input if any
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// If JSON input is detected from ESP32
if ($data && isset($data['patient_id']) && isset($data['medications'])) {
    // ESP32 is reporting dispense result
    echo json_encode([
        "status" => "received",
        "source" => "ESP32",
        "medications" => $data['medications'],
        "time" => date("H:i:s")
    ]);
    exit;
}

// Otherwise, this is a trigger from a web interface or admin call
$esp32_ip = "192.168.4.186";
$url = "http://$esp32_ip/dispense";
$postData = [
    'patient_id' => 1,
    'medication_id' => 3,
    'dosage' => 500,
    "time" => date(format: "H:i:s") 
];

// Send form-encoded POST to ESP32
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/x-www-form-urlencoded'
]); // <-- Add this
$response = curl_exec($ch);
curl_close($ch);

echo json_encode([
    "status" => "sent",
    "source" => "Web",
    "forwarded_to_esp32" => true,
    "time" => date(format: "H:i:s")
]);
exit;
