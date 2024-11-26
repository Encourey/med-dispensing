<?php
session_start();
include 'db_connect.php';

// Redirect if the doctor is not logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch POST data
$doctor_id = $_SESSION['doctor_id'];
$patient_id = $_POST['patient_id'];
$medication_ids = $_POST['medication_ids']; // Array of selected medication IDs
$dosages = $_POST['dosages']; // Associative array with medication_id => dosage

// ESP32 IP Address
$esp32_ip = "192.168.1.51"; // Replace with your ESP32's IP address

$responseLog = []; // Array to store responses for each medication

// Process regular medications
foreach ($medication_ids as $medication_id) {
    // Fetch medication details
    $medQuery = $conn->prepare("SELECT medication_name, mgpdosage FROM medications WHERE medication_id = ?");
    $medQuery->bind_param("i", $medication_id);
    $medQuery->execute();
    $medQuery->bind_result($medication_name, $mgpdosage);
    $medQuery->fetch();
    $medQuery->close();

    // Calculate total dosage
    $selected_dosage = $dosages[$medication_id];
    $total_dosage = $mgpdosage * $selected_dosage;

    $dispenseQuery = $conn->prepare(
        "INSERT INTO dispensations (doctor_id, patient_id, medication_name, dosage, dispense_date, response_message) 
         VALUES (?, ?, ?, ?, NOW(), ?)"
    );
    $dispenseQuery->bind_param("iisss", $doctor_id, $patient_id, $medication_name, $total_dosage, $response);
    $dispenseQuery->execute();
    $dispenseQuery->close();

    // $checkQuery->close();

    // Communicate with ESP32 to dispense medication
    $url = "http://$esp32_ip/dispense";
    $postData = [
        'patient_id' => $patient_id,
        'medication_id' => $medication_id,
        'dosage' => $total_dosage
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // Error occurred during connection
        $error_message = curl_error($ch);
        $responseLog[] = [
            'medication_id' => $medication_id,
            'status' => 'failure',
            'message' => "Curl Error: " . htmlentities($error_message)
        ];
    } else {
        // Get HTTP Status Code
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_status == 200) {
            // Assuming ESP32 returns JSON response
            $decodedResponse = json_decode($response, true); // Decode JSON response from ESP32

            if ($decodedResponse && $decodedResponse['status'] == 'success') {
                $responseLog[] = [
                    'medication_id' => $medication_id,
                    'status' => 'success',
                    'message' => "ESP32 confirmed dispensing!"
                ];
            } else {
                $responseLog[] = [
                    'medication_id' => $medication_id,
                    'status' => 'failure',
                    'message' => "ESP32 Response Error: " . htmlentities($response)
                ];
            }
        } else {
            // If HTTP Status is not 200, log error
            $responseLog[] = [
                'medication_id' => $medication_id,
                'status' => 'failure',
                'message' => "ESP32 Response: HTTP $http_status, " . htmlentities($response)
            ];
        }
    }

    curl_close($ch);

    // Save dispense log to database
    $dispenseQuery = $conn->prepare(
        "INSERT INTO dispensations (doctor_id, patient_id, medication_name, dosage, dispense_date, response_message) 
         VALUES (?, ?, ?, ?, NOW(), ?)"
    );
    $dispenseQuery->bind_param("iisss", $doctor_id, $patient_id, $medication_name, $total_dosage, $response);
    $dispenseQuery->execute();
    $dispenseQuery->close();
}

// Update patient_data table
$updatePatient = $conn->prepare(
    "UPDATE patient_data 
     SET last_dispensed = NOW(), 
         medication_dispensed = COALESCE(medication_dispensed, 0) + 1 
     WHERE id = ?"
);

$updatePatient->bind_param("i", $patient_id);
$updatePatient->execute();
$updatePatient->close();

// Save responses for debugging or display in JSON format
$_SESSION['dispense_responses'] = $responseLog;
file_put_contents("esp32_log.json", json_encode($responseLog, JSON_PRETTY_PRINT), FILE_APPEND);

// Redirect to the dashboard with a success message
header("Location: docmain.php?dispense=success");
exit();
?>
