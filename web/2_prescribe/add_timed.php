<?php
session_start();
include 'db_connect.php';

// Check if the doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

// Handle form submission to add medication info
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Medication details
    $patient_id = $_POST['patient_id'];
    $medications = $_POST['medication_id'];  // Array of medication IDs
    $multipliers = $_POST['custom_multiplier'];  // Array of multipliers
    $set_time = $_POST['set_time'];

    // Check if at least one medication is selected
    if (empty($medications)) {
        echo "Please select at least one medication.";
        exit();
    }

    // Loop through each medication to add to the database
    foreach ($medications as $index => $medication_id) {
        $custom_multiplier = $multipliers[$index];

        // Fetch the actual dosage from the medications table based on selected medication
        $sql_medication = "SELECT mgpdosage FROM medications WHERE medication_id = ?";
        $stmt_medication = $conn->prepare($sql_medication);
        $stmt_medication->bind_param("i", $medication_id);
        $stmt_medication->execute();
        $result_medication = $stmt_medication->get_result();

        if ($result_medication->num_rows > 0) {
            // Get the actual dosage from the database
            $row = $result_medication->fetch_assoc();
            $actual_dosage = $row['dosage'];

            // Calculate the custom dosage as the multiplier * actual dosage
            $custom_dosage = $actual_dosage * $custom_multiplier;

            // Insert medication details into the patient_medications table
            $sql_insert = "INSERT INTO patient_medications (patient_id, medication_id, custom_dosage, set_time) 
                           VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiis", $patient_id, $medication_id, $custom_dosage, $set_time);

            if (!$stmt_insert->execute()) {
                echo "Error adding medication information.";
                exit();
            }
        } else {
            echo "Medication not found.";
            exit();
        }
    }

    header("Location: docmain.php");  // Redirect back to the dashboard after successful addition
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Medications to Patient</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
        }

        select, input[type="number"], input[type="time"] {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .medication-field {
            margin-bottom: 20px;
        }

        .medication-field select,
        .medication-field input {
            width: 100%;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        button {
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .add-button {
            background-color: #00bcd4;
        }

        .add-button:hover {
            background-color: #0097a7;
        }

        a {
            text-decoration: none;
            color: #4CAF50;
            text-align: center;
            display: block;
            margin-top: 20px;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        .medications-container {
            margin-bottom: 30px;
        }
    </style>
    <script>
        // JavaScript to add new medication fields dynamically and prevent duplicates
        var selectedMedications = [];

        function updateSelectedMedications(selectElement) {
            var medicationId = selectElement.value;
            if (medicationId && !selectedMedications.includes(medicationId)) {
                selectedMedications.push(medicationId);
            } else {
                selectElement.value = ""; // Reset selection if it's already selected
                alert("This medication has already been selected.");
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Assign Medications to Patient</h2>
        <form method="POST">
            <!-- Patient Selection Dropdown -->
            <label for="patient_id">Select Patient:</label>
            <select id="patient_id" name="patient_id" required>
                <option value="">Select Patient</option>
                <?php
                // Fetch existing patients from the patient_data table
                $sql_patients = "SELECT id, patient_name FROM patient_data";
                $result_patients = $conn->query($sql_patients);
                while ($row = $result_patients->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['patient_name'] . "</option>";
                }
                ?>
            </select>

            <!-- Medications Container -->
            <label for="medication_id[]">Select Medication:</label>
            <select name="medication_id[]" required onchange="updateSelectedMedications(this)">
                <option value="">Select Medication</option>
                <?php
                // Fetch available medications from the medications table
                $sql_medications = "SELECT medication_id, medication_name FROM medications";
                $result_medications = $conn->query($sql_medications);
                while ($row = $result_medications->fetch_assoc()) {
                    echo "<option value='" . $row['medication_id'] . "'>" . $row['medication_name'] . "</option>";
                }
                ?>
            </select>

            <label for="custom_multiplier[]">Custom Dosage Multiplier:</label>
            <input type="number" step="0.01" name="custom_multiplier[]" required>

            <label for="set_time">Set Time for Dispensing:</label>
            <input type="time" id="set_time" name="set_time" required>

            <!-- Buttons -->
            <div class="button-container">
                <button type="submit">Assign Medications</button>
            </div>

        </form>
        <a href="docmain.php">Back to Dashboard</a>
    </div>
</body>
</html>
