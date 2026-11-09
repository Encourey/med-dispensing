<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$patient_id = $_POST['patient_id'];
$medication_ids = $_POST['medication_ids']; // Format: medication_id_time (e.g., 1_08:00:00)

$responseLog = [];
$esp32_ip = "192.168.4.186";

foreach ($medication_ids as $entry) {
    list($medication_id, $dispense_time) = explode('_', $entry);

    $medQuery = $conn->prepare("SELECT medication_name, mgpdosage FROM medications WHERE medication_id = ?");
    $medQuery->bind_param("i", $medication_id);
    $medQuery->execute();
    $medQuery->bind_result($medication_name, $mgpdosage);
    $medQuery->fetch();
    $medQuery->close();

    $dosage = 1; // Default 1 capsule
    $total_dosage = $mgpdosage * $dosage;

    // Determine time_label
    $labelQuery = $conn->prepare("SELECT time_text FROM medications_time WHERE time = ? LIMIT 1");
    $labelQuery->bind_param("s", $dispense_time);
    $labelQuery->execute();
    $labelResult = $labelQuery->get_result();
    $labelRow = $labelResult->fetch_assoc();
    $dispense_time_label = $labelRow ? $labelRow['time_text'] : '';
    $labelQuery->close();

    // Communicate with ESP32 (simulate)
    $url = "http://$esp32_ip/dispense";
    $postData = [
        'patient_id' => $patient_id,
        'medication_id' => $medication_id,
        'dosage' => $total_dosage,
        "time" => date(format: "H:i:s") 
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $dispenseQuery = $conn->prepare(
        "INSERT INTO dispensations (doctor_id, patient_id, medication_name, dosage, dispense_date, dispense_time_label, dispense_time, response_message) 
         VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)"
    );
    $dispenseQuery->bind_param("iisssss", $doctor_id, $patient_id, $medication_name, $total_dosage, $dispense_time_label, $dispense_time, $response);
    $dispenseQuery->execute();
    $dispenseQuery->close();

    $responseLog[] = [
        'medication_id' => $medication_id,
        'status' => 'sent',
        'message' => $response,
        'time' => $dispense_time,
        'label' => $dispense_time_label
    ];
}

// Update patient_data
$updatePatient = $conn->prepare(
    "UPDATE patient_data 
     SET last_dispensed = NOW(), 
         medication_dispensed = COALESCE(medication_dispensed, 0) + 1 
     WHERE id = ?"
);
$updatePatient->bind_param("i", $patient_id);
$updatePatient->execute();
$updatePatient->close();

$_SESSION['dispense_responses'] = $responseLog;
file_put_contents("esp32_log.json", json_encode($responseLog, JSON_PRETTY_PRINT), FILE_APPEND);

header("Location: docmain.php?dispense=success");
exit();
?>
