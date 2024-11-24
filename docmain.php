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
    <title>Doctor's Dashboard</title>
    <style>
        /* Add spacing around the entire page */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
            color: #333;
        }

        /* Adjust header styling */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px; /* Increased padding */
        }

        header h2 {
            margin: 0;
            font-size: 1.8em; /* Slightly larger font size */
            font-weight: bold; /* Make text bold */
            text-align: left;  /* Align text to the left */
        }
        .menu {
            display: flex;
            gap: 15px;
        }

        .menu-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 2 em;
            transition: background-color 0.3s;
            font-weight: bold; /* Make text bold */
        }

        .menu-btn:hover {
            background-color: #3a8b3c;
        }

        .section {
            margin-top: 20px;
        }

        .status-container {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            width: 300px;
        }

        .status {
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease-in-out;
        }

        .status-enabled {
            color: #4CAF50; /* Green */
        }

        .status-disabled {
            color: #FF5722; /* Red */
        }

        .status-checking {
            color: #FF9800; /* Orange */
        }

        .status-message {
            font-size: 16px;
            color: #777;
        }

        .spinner {
            margin-top: 10px;
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #3498db; 
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-checking .spinner {
            visibility: visible;
        }

        .status-enabled .spinner, .status-disabled .spinner {
            visibility: hidden;
        }

        .logout-btn {
            padding: 8px 15px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h3 {
            color: #4CAF50;
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .patient-info-table, .prescription-log-table, .med-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .patient-info-table th, .patient-info-table td,
        .prescription-log-table th, .prescription-log-table td,
        .med-info-table th, .med-info-table td {
            padding: 12px;
            text-align: left;
            font-size: 0.95em;
            border-bottom: 1px solid #ddd;
        }

        .patient-info-table th {
            background-color: #f2f2f2;
            color: #333;
        }

        .med-info-table th {
            background-color: #e6f7e6;
            color: #333;
        }
        
        .prescription-log-table th {
            background-color: #e6f7e6;
            color: #333;
        }

        .patient-info-table tr:nth-child(even),
        .med-info-table tr:nth-child(even),
        .prescription-log-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .patient-info-table tr:hover,
        .med-info-table tr:hover,
        .prescription-log-table tr:hover {
            background-color: #f1f1f1;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 8px 15px;
            font-size: 0.9em;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            color: white;
            transition: background-color 0.3s;
        }

        /* Change button colors */
        .dispense-btn, .edit-btn {
            background-color: #45a049;
        }

        .dispense-btn:hover, .edit-btn:hover {
            background-color: #3a8b3c;
        }

        .button-group2 {
            display: flex;
            gap: 10px;
        }

        .add-patient-btn {
            display: inline-block ;
            background-color: #4CAF50;
            margin:  10px ;
        }

        .add-meds-btn {
            display: inline-block ;
            background-color: #4CAF50;
            margin:  10px ;
        }

        .delete-button {
            background-color: #e74c3c;
        }

        .delete-button:hover {
            background-color: #c0392b;
        }
    </style>
    <script>
        // Top Bar
        function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.style.display = 'none';
        });

        // Show the selected section
        document.getElementById(sectionId).style.display = 'block';
    }
        //function for dispensing and deleting entries
        function confirmDispense() {
            return confirm("Are you sure you want to Dispense.");
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this entry? This action cannot be undone.");
        }
        //for real time patient searching
        function searchPatient() {
           const searchTerm = document.getElementById("search").value;
            console.log("Searching for:", searchTerm); // Debugging line to verify input

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "search_patient.php?search=" + encodeURIComponent(searchTerm), true);
        
            // Log errors if the request fails
            xhr.onerror = function () {
                console.error("Request failed");
                };

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            console.log("Search results:", xhr.responseText); // Log response for debugging
                            document.getElementById("patientTable").innerHTML = xhr.responseText;
                        } else {
                            console.error("Error: " + xhr.status); // Log status if an error occurs
                        }
                    }
                };
            xhr.send();
        }

        function searchPrescription() {
            const patientName = document.getElementById("patient-name").value;

            console.log("Searching prescriptions for:", patientName); // Debugging line to verify input

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "search_prescript.php?search=" + encodeURIComponent(patientName), true); // Send only patient_name

            xhr.onerror = function() {
                console.error("Request failed");
            };

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        console.log("Prescription search results:", xhr.responseText); // Log response for debugging
                        document.getElementById("prescriptionTable").innerHTML = xhr.responseText;
                    } else {
                        console.error("Error: " + xhr.status); // Log status if an error occurs
                    }
                }
            };

            xhr.send();
        }

        async function checkStatus() {
            const statusElement = document.getElementById('machine-status');
            try {
                const response = await fetch('check_status.php'); // Adjust the path if needed
                console.log("Response status:", response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                console.log("Response body:", data);

                if (data.status === "enabled") {
                    statusElement.textContent = "Machine is Enabled";
                    statusElement.style.color = "green";
                } else {
                    statusElement.textContent = "Machine is Disabled";
                    statusElement.style.color = "red";
                }
            } catch (error) {
                console.error("Error connecting to the server:", error);
                statusElement.textContent = "Error Connecting";
                statusElement.style.color = "gray";
            }
        }
        setInterval(checkStatus, 2000);
        checkStatus();
    </script>
</head>
<body>

<header>
    <h2>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['doctor_name']); ?></h2>
    <div class="menu">
        <button class="menu-btn" onclick="showSection('patient-info')">Patient Info</button>
        <button class="menu-btn" onclick="showSection('medications')">Medications</button>
        <button class="menu-btn" onclick="showSection('prescription-log')">Prescription Log</button>
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
            <table class="patient-info-table" id="patientTable">
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Action</th>
                </tr>

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
                                        <button type='submit' class='dispense-btn'>Dispense Medication</button>
                                    </form>
                                    <a href='edit_patient.php?id={$row['id']}'>
                                        <button class='edit-btn'>Edit</button>
                                    </a>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No records found</td></tr>";
                }
                ?>
            </table>
            <td class='button-group2'>
                <form action="add_patient.php" style='display: inline'>
                    <button class="add-patient-btn">Add New Patient</button>
                </form>                 
            </td>
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
                    // Query to fetch medication data
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
                                        <a href='edit_medication.php?id=" . htmlspecialchars($row['medication_id']) . "'>
                                            <button class='edit-btn'>Edit</button>
                                        </a>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No medications found</td></tr>";
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
                                    <td>
                                        <form action='delete_prescription.php' method='POST' style='display: inline;' onsubmit='return confirmDelete();'>
                                            <input type='hidden' name='dispense_id' value='{$row['dispense_id']}'>
                                            <button type='submit' class='delete-button'>Delete</button>
                                        </form>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No prescriptions found</td></tr>";
                    }
                    ?>
            </table>
        </div>
    </div>
</body>
</html>