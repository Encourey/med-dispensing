<?php
session_start();
if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
</head>

<body>
    <header>
        <h2 class="dashboard">Logged in as: <?php echo htmlspecialchars($_SESSION['doctor_name']); ?></h2>
        <br></br>
        <div class="menu">
            <button class="menu-btn" onclick="showSection('patient-info')">Patient Info</button>
            <button class="menu-btn" onclick="showSection('medications')">Medications</button>
            <button class="menu-btn" onclick="showSection('prescription-log')">Prescription Log</button>
            <button class="menu-btn" onclick="showSection('timed-medications')">Timed Medications</button>
        </div>

        <div id="timediv" class="time">
            
        </div>
        
        <a href="logout.php">
            <button class="logout-btn">Logout</button>
        </a>
    </header>

    <div class="status-container">
        <div id="machine-status" class="status status-checking">
            <span>Checking...</span>
            <div class="spinner"></div>
        </div>
    </div>

    <div class="container">
        <div id="patient-info" class="section">
            <h3>Patient Information</h3>
            <input type="text" id="search" placeholder="Search patient by name" onkeyup="searchPatient()" style="padding: 8px; width: 250px; font-size: 1em;">
            <br><br>
            <table class="patient-info-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="patientTable">
                    <?php
                    $sql = " SELECT patient_data.*, GROUP_CONCAT(medications.medication_name SEPARATOR ', ') AS medications
                    FROM patient_data
                    LEFT JOIN patient_medications ON patient_data.id = patient_medications.patient_id
                    LEFT JOIN medications ON patient_medications.medication_id = medications.medication_id
                    GROUP BY patient_data.id";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['patient_name']}</td>
                                    <td>{$row['age']}</td>
                                    <td>{$row['gender']}</td>
                                    <td class='button-group'>
                                        <form action='dispense_meds.php' method='POST' style='display: inline;'>
                                            <input type='hidden' name='patient_id' value='{$row['id']}'>
                                            <button type='submit' class='dispense-btn' disabled style='opacity: 0.5; cursor: not-allowed;'>Dispense Medication</button>
                                        </form>
                                        <form action='edit_patient.php' method='GET' style='display: inline;'>
                                            <input type='hidden' name='id' value='{$row['id']}'>
                                            <button type='submit' class='edit-btn'>Edit</button>
                                        </form>
                                        <form action='edit_patient_changepassword.php' method='GET' style='display: inline; margin-left: 5px;'>
                                            <input type='hidden' name='id' value='{$row['id']}'>
                                            <button type='submit' class='edit-btn' style='background-color: #f57c00;'>Change Password</button>
                                        </form>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="button-group2">
                <form action="add_patient.php" style='display: inline'>
                    <button class="add-patient-btn">Add New Patient</button>
                </form>
            </div>
        </div>

        <div id="medications" class="section" style="display: none;">
            <h3>Medications</h3>
                <table class="med-info-table" id="medTable">
                    <tr>
                        <th>Medication ID</th>
                        <th>Medication Name</th>
                        <th>Milligrams per Dosage</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $sql = "SELECT 
                                medications.medication_id, 
                                medications.medication_name, 
                                medications.mgpdosage, 
                                COUNT(patient_medications.patient_id) AS total_patients
                            FROM medications
                            LEFT JOIN patient_medications ON medications.medication_id = patient_medications.medication_id
                            GROUP BY medications.medication_id";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['medication_id']}</td>    
                                    <td>{$row['medication_name']}</td>
                                    <td>{$row['mgpdosage']} mg</td>
                                    <td class='button-group'>
                                        <form action='edit_medication.php' method='GET' style='display: inline;'>
                                            <input type='hidden' name='id' value='{$row['medication_id']}'>
                                            <button type='submit' class='edit-btn'>Edit</button>
                                        </form>
                                    </td>
                                </tr>";
                        }
                    } 
                    else {
                            echo "<tr><td colspan='4'>No medications found</td></tr>";
                    }
                    ?>
                </table>
        </div>    
        
        <div id="prescription-log" class="section" style="display: none;">
            <h3>Prescription Log</h3>
            <input type="text" id="patient-name" placeholder="Search log by patient name" onkeyup="searchPrescription()" style="padding: 8px; width: 250px; font-size: 1em;">
            <!-- Prescription Log Table -->
            <table class="prescription-log-table" id="prescriptionTable">
                <tr>
                    <th>Patient Name</th>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Prescribed By</th>
                    <th>Date & Time</th>
                    <th>Time Label</th>     <!-- ✅ New -->
                    <th>Time</th>           <!-- ✅ New -->
                    <th>Response</th>
                    <th>Action</th>
                </tr>
                <?php
                include 'db_connect.php'; // Ensure database connection

                // Base SQL query
                $sql = "SELECT dispensations.*, patient_data.patient_name, doctors.doctor_name 
                        FROM dispensations
                        JOIN patient_data ON dispensations.patient_id = patient_data.id
                        JOIN doctors ON dispensations.doctor_id = doctors.doctor_id
                        ORDER BY dispensations.dispense_date DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['patient_name']}</td>
                                <td>{$row['medication_name']}</td>
                                <td>{$row['dosage']}</td>
                                <td>Dr. {$row['doctor_name']}</td>
                                <td>{$row['dispense_date']}</td>
                                <td>{$row['dispense_time_label']}</td>
                                <td>{$row['dispense_time']}</td>
                                <td>{$row['response_message']}</td>
                                <td>
                                    <form action='delete_prescription.php' method='POST' style='display: inline;' onsubmit='return confirmDelete();'>
                                        <input type='hidden' name='dispense_id' value='{$row['dispense_id']}'>
                                        <button type='submit' class='delete-button'>Delete</button>
                                    </form>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No prescriptions found</td></tr>";
                }
                ?>
            </table>
        </div>

        <div id="timed-medications" class="section" style="display: none;">
            <h3>Timed Medication</h3>
            <table class="timed-medications-table" id="timedmedsTable">
                <tr>
                    <th>Patient Name</th>
                    <th>Medication Name</th>
                    <th>Dosage</th>
                    <th>Time to dispense</th>
                </tr>
                <?php
                $sql = "SELECT 
                            p.patient_name,
                            m.medication_name,
                            pm.custom_dosage AS dosage,
                            pm.set_time AS dispense_time
                        FROM patient_medications pm
                        JOIN patient_data p ON pm.patient_id = p.id
                        JOIN medications m ON pm.medication_id = m.medication_id
                        ORDER BY p.patient_name, m.medication_name";
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['patient_name']}</td>
                                <td>{$row['medication_name']}</td>
                                <td>{$row['dosage']} mg</td>
                                <td>{$row['dispense_time']}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No timed medications found.</td></tr>";
                }
                ?>
            </table>
            <td class='button-group2'>
                <form action="add_timed.php" style='display: inline'>
                    <button class="add-timed-btn">Add New Timed Medications</button>
                </form>
            </td>
            
        </div>
    </div>
</body>
</html>