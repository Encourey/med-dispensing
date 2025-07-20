<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    echo "Doctor ID not set in session. Redirecting to login...";
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Retrieve `patient_id` from GET or POST and check if it's set
if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
} elseif (isset($_POST['patient_id'])) {
    $patient_id = $_POST['patient_id'];
} else {
    die("Error: Patient ID not provided.");
}

// Fetch all the medications that can be prescribed, including dosage
$medicationsResult = $conn->query("
    SELECT 
        m.medication_id, 
        m.medication_name, 
        m.mgpdosage
    FROM 
        medications m
");

if (!$medicationsResult) {
    die("Error fetching medications: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispense Medication</title>
    <style>
        /* Basic layout styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Container styling */
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 600px;
            text-align: center;
        }

        h2 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
        }

        /* Button styling */
        button {
            background-color: #4CAF50;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #3a8b3c;
        }

        /* Disable button styling */
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        a {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #4CAF50;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        // Function to validate that at least one medication is selected
        function validateForm() {
            const checkboxes = document.querySelectorAll('input[name="medication_ids[]"]:checked');
            if (checkboxes.length === 0) {
                alert("Please select at least one medication to dispense.");
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }

        // Function to update the total dosage dynamically
        function updateTotalDosage(medicationId, mgpdosage) {
            const dosageInput = document.querySelector(`input[name="dosages[${medicationId}]"]`);
            const totalDosageCell = document.getElementById(`total_dosage_${medicationId}`);
            const dosage = dosageInput.value ? parseInt(dosageInput.value) : 1;
            totalDosageCell.textContent = (mgpdosage * dosage) + " mg";
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Dispense Medications for Patient ID: <?php echo htmlspecialchars($patient_id); ?></h2>
        <form action="process_dispense.php" method="POST" onsubmit="return validateForm();">
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">

            <?php if ($medicationsResult->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Select</th>
                        <th>Medication Name</th>
                        <th>Milligrams per Dosage</th>
                        <th>Dosage (Units)</th>
                        <th>Total Dosage (mg)</th>
                    </tr>
                    <?php while ($med = $medicationsResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="medication_ids[]" value="<?php echo htmlspecialchars($med['medication_id']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($med['medication_name']); ?></td>
                            <td><?php echo htmlspecialchars($med['mgpdosage']); ?> mg</td>
                            <td>
                                <input type="number" name="dosages[<?php echo htmlspecialchars($med['medication_id']); ?>]" value="1" min="1" required onchange="updateTotalDosage(<?php echo htmlspecialchars($med['medication_id']); ?>, <?php echo htmlspecialchars($med['mgpdosage']); ?>)">
                            </td>
                            <td id="total_dosage_<?php echo htmlspecialchars($med['medication_id']); ?>"><?php echo htmlspecialchars($med['mgpdosage']); ?> mg</td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit">Dispense Selected Medications</button>
            <?php else: ?>
                <p>No medications available in the database.</p>
                <button type="submit" disabled>Dispense</button>
            <?php endif; ?>
        </form>
        <a href="docmain.php">Back to Dashboard</a>
    </div>
</body>
</html>

<?php 
$medicationsResult->close();
$conn->close();
?>
