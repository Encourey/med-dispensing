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
$esp32_ip = "192.168.1.56"; // Replace with your ESP32's IP address

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

    // Update patient_medications table
    $checkQuery = $conn->prepare("SELECT * FROM patient_medications WHERE patient_id = ? AND medication_id = ?");
    $checkQuery->bind_param("ii", $patient_id, $medication_id);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows === 0) {
        $insertPatMed = $conn->prepare("INSERT INTO patient_medications (patient_id, medication_id) VALUES (?, ?)");
        $insertPatMed->bind_param("ii", $patient_id, $medication_id);
        $insertPatMed->execute();
        $insertPatMed->close();
    }

    $checkQuery->close();

    // Log into dispensations table
    $dispenseQuery = $conn->prepare(
        "INSERT INTO dispensations (doctor_id, patient_id, medication_name, dosage, dispense_date) 
         VALUES (?, ?, ?, ?, NOW())"
    );
    $dispenseQuery->bind_param("iiss", $doctor_id, $patient_id, $medication_name, $total_dosage);
    $dispenseQuery->execute();
    $dispenseQuery->close();

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
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($http_status == 200 && trim($response) == "SUCCESS") {
        echo "ESP32 confirmed dispensing!";
    } else {
        echo "Failed to communicate with ESP32 for medication ID: $medication_id.";
    }
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

// Redirect to the dashboard with a success message
header("Location: docmain.php?dispense=success");
exit();
?>
