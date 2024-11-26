<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "logins";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

//---------------------------------------------LOGIN---------------------------------------------//

$doctorTableQuery = "
    CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_name VARCHAR(100) NOT NULL,
        password VARCHAR(100) NOT NULL
    )";
$conn->query($doctorTableQuery);

$patientTableQuery = "
    CREATE TABLE IF NOT EXISTS patients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_name VARCHAR(100) NOT NULL,
        password VARCHAR(100) NOT NULL
    )";
$conn->query($patientTableQuery);

//---------------------------------------------MAIN---------------------------------------------//

$patientdataTableQuery = "
    CREATE TABLE IF NOT EXISTS patient_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_name VARCHAR(100) NOT NULL,
        age INT NOT NULL,
        gender VARCHAR(10) NOT NULL,
        medication_name VARCHAR(100),
        dosage VARCHAR(50),
        time_prescribe DATETIME(6)
    )";
$conn->query($patientdataTableQuery);

$medicationdataTableQuery = "
    CREATE TABLE IF NOT EXISTS medications (
        medication_id INT AUTO_INCREMENT PRIMARY KEY,
        medication_name VARCHAR(255) NOT NULL,
        dosage VARCHAR(100) NOT NULL
    )";
$conn->query($medicationdataTableQuery);

$patmeddataTableQuery = "
    CREATE TABLE IF NOT EXISTS patient_medications (
        patient_id INT NOT NULL,
        medication_id INT NOT NULL,
        FOREIGN KEY (patient_id) REFERENCES patient_data(id) ON DELETE CASCADE,
        FOREIGN KEY (medication_id) REFERENCES medications(medication_id) ON DELETE CASCADE,
        PRIMARY KEY (patient_id, medication_id)
    )";
$conn->query($patmeddataTableQuery);

// Fetching patient data (assuming you have patient_id from session or other means)
$patient_id = 1; // Replace with dynamic patient ID, e.g., from session or URL
$patientDataQuery = "SELECT * FROM patient_data WHERE id = $patient_id";
$patientDataResult = $conn->query($patientDataQuery);

if ($patientDataResult && $patientDataResult->num_rows > 0) {
    $patientData = $patientDataResult->fetch_assoc();
    
    // Now fetch medications for this patient
    $medicationQuery = "
        SELECT medications.medication_name, medications.mgpdosage
        FROM medications
        JOIN patient_medications ON medications.medication_id = patient_medications.medication_id
        WHERE patient_medications.patient_id = $patient_id";

    $medicationResult = $conn->query($medicationQuery);

} else {
    echo "Patient not found.<br>";
}

//connect data between patient medication , patient data and medication
$query = "
    SELECT 
        pm.record_id,
        p.patient_name,
        m.medication_name,
        pm.custom_dosage,
        pm.set_time
    FROM 
        patient_medications pm
    JOIN 
        patient_data p ON pm.patient_id = p.id
    JOIN 
        medications m ON pm.medication_id = m.medication_id
";
$conn->query($query);
?>
