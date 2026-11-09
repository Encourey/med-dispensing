<?php
session_start();
include 'db_connect.php';

// Check if the doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

// Handle form submission to add new patient details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = $_POST['patient_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $patient_user = $_POST['patient_user'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $medication_id_1 = isset($_POST['medication_id_1']) ? 1 : 0;
    $medication_id_2 = isset($_POST['medication_id_2']) ? 1 : 0;
    $medication_id_3 = isset($_POST['medication_id_3']) ? 1 : 0;

    $dosage_1 = $_POST['dosage_1'] ?? '';
    $dosage_2 = $_POST['dosage_2'] ?? '';
    $dosage_3 = $_POST['dosage_3'] ?? '';

    // Join selected timing checkboxes into a string (e.g. "before_meal,morning,evening")
    $timing_1 = '';
if (isset($_POST['meal_1'])) {
    $timing_1 .= $_POST['meal_1'];
}
if (isset($_POST['timing_1'])) {
    $timing_1 .= ($timing_1 ? ',' : '') . implode(',', $_POST['timing_1']);
}

$timing_2 = '';
if (isset($_POST['meal_2'])) {
    $timing_2 .= $_POST['meal_2'];
}
if (isset($_POST['timing_2'])) {
    $timing_2 .= ($timing_2 ? ',' : '') . implode(',', $_POST['timing_2']);
}

$timing_3 = '';
if (isset($_POST['meal_3'])) {
    $timing_3 .= $_POST['meal_3'];
}
if (isset($_POST['timing_3'])) {
    $timing_3 .= ($timing_3 ? ',' : '') . implode(',', $_POST['timing_3']);
}
    //$timing_1 = isset($_POST['timing_1']) ? implode(",", $_POST['timing_1']) : '';
    //$timing_2 = isset($_POST['timing_2']) ? implode(",", $_POST['timing_2']) : '';
    //$timing_3 = isset($_POST['timing_3']) ? implode(",", $_POST['timing_3']) : '';


    // Add to patient_data table
    $sql = "INSERT INTO patient_data (
            patient_name, age, gender,
            medication_id_1, dosage_1, timing_1,
            medication_id_2, dosage_2, timing_2,
            medication_id_3, dosage_3, timing_3
        )
        VALUES (?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisssissss",
        $patient_name, $age, $gender,
        $medication_id_1, $dosage_1, $timing_1,
        $medication_id_2, $dosage_2, $timing_2,
        $medication_id_3, $dosage_3, $timing_3
    );


    
    if ($stmt->execute()) {
        // Add to patients table
        $sql2 = "INSERT INTO patients (patient_user, password) VALUES (?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ss", $patient_user, $password);
        $stmt2->execute();

        header("Location: docmain.php");
        exit();
    } else {
        echo "Error adding patient information.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Patient Information</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f7fa;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 700px;
      margin: 50px auto;
      padding: 30px;
      background-color: #ffffff;
      border-radius: 10px;
      box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #333;
      text-align: center;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
      display: block;
    }

    input[type="text"],
    input[type="number"],
    input[type="password"] {
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ccc;
      width: 100%;
      box-sizing: border-box;
    }

    .radio-group,
    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .checkbox-group label,
    .radio-group label {
      font-weight: normal;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .medication-section {
      border: 1px solid #e0e0e0;
      padding: 15px;
      border-radius: 8px;
      background-color: #fafafa;
    }

    .medication-title {
      font-size: 1.1em;
      font-weight: bold;
      margin-bottom: 10px;
      color: #4CAF50;
    }

    .medication-details {
      display: none;
      margin-top: 10px;
      padding-left: 10px;
      border-left: 3px solid #d0e8d0;
    }

    button {
      padding: 12px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1em;
    }

    button:hover {
      background-color: #45a049;
    }

    a {
      display: block;
      margin-top: 20px;
      text-align: center;
      text-decoration: none;
      color: #4CAF50;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Add Patient Information</h2>
    <form method="POST">
      <label for="patient_name">Patient Name:</label>
      <input type="text" id="patient_name" name="patient_name" required />

      <label for="age">Age:</label>
      <input type="number" id="age" name="age" required />

      <label>Gender:</label>
      <div class="radio-group">
        <label><input type="radio" name="gender" value="MALE" required /> Male</label>
        <label><input type="radio" name="gender" value="FEMALE" /> Female</label>
      </div>

      <!-- Medication 1 -->
      <div class="medication-section">
        <div class="medication-title">
          <label>
            <input type="checkbox" name="medication_id_1" value="1" onchange="toggleDetails(this, 'details_1')" />
            Paracetamol
          </label>
        </div>
        <div class="medication-details" id="details_1">
          <label for="dosage_1">Dosage (mg):</label>
          <input type="number" name="dosage_1" min="0" />

          <label>Meal Timing:</label>
            <div class="radio-group">
            <label><input type="radio" name="meal_1" value="before_meal"> Before meal</label>
            <label><input type="radio" name="meal_1" value="after_meal"> After meal</label>
          </div>

          <label>Timing:</label>
          <div class="checkbox-group">
            <!--<label><input type="checkbox" name="timing_1[]" value="before_meal" /> Before meal</label>
            <label><input type="checkbox" name="timing_1[]" value="after_meal" /> After meal</label>-->
            <label><input type="checkbox" name="timing_1[]" value="morning" /> Morning</label>
            <label><input type="checkbox" name="timing_1[]" value="noon" /> Noon</label>
            <label><input type="checkbox" name="timing_1[]" value="evening" /> Evening</label>
            <label><input type="checkbox" name="timing_1[]" value="bedtime" /> Bedtime</label>
          </div>
        </div>
      </div>

      <!-- Medication 2 -->
      <div class="medication-section">
        <div class="medication-title">
          <label>
            <input type="checkbox" name="medication_id_2" value="1" onchange="toggleDetails(this, 'details_2')" />
            Ibuprofen
          </label>
        </div>
        <div class="medication-details" id="details_2">
          <label for="dosage_2">Dosage (mg):</label>
          <input type="number" name="dosage_2" min="0" />

          <label>Meal Timing:</label>
            <div class="radio-group">
            <label><input type="radio" name="meal_2" value="before_meal"> Before meal</label>
            <label><input type="radio" name="meal_2" value="after_meal"> After meal</label>
          </div>

          <label>Timing:</label>
          <div class="checkbox-group">
            <label><input type="checkbox" name="timing_2[]" value="morning" /> Morning</label>
            <label><input type="checkbox" name="timing_2[]" value="noon" /> Noon</label>
            <label><input type="checkbox" name="timing_2[]" value="evening" /> Evening</label>
            <label><input type="checkbox" name="timing_2[]" value="bedtime" /> Bedtime</label>
          </div>
        </div>
      </div>

      <!-- Medication 3 -->
      <div class="medication-section">
        <div class="medication-title">
          <label>
            <input type="checkbox" name="medication_id_3" value="1" onchange="toggleDetails(this, 'details_3')" />
            Amoxicillin
          </label>
        </div>
        <div class="medication-details" id="details_3">
          <label for="dosage_3">Dosage (mg):</label>
          <input type="number" name="dosage_3" min="0" />

          <label>Meal Timing:</label>
            <div class="radio-group">
            <label><input type="radio" name="meal_3" value="before_meal"> Before meal</label>
            <label><input type="radio" name="meal_3" value="after_meal"> After meal</label>
          </div>

          <label>Timing:</label>
          <div class="checkbox-group">
            <label><input type="checkbox" name="timing_3[]" value="morning" /> Morning</label>
            <label><input type="checkbox" name="timing_3[]" value="noon" /> Noon</label>
            <label><input type="checkbox" name="timing_3[]" value="evening" /> Evening</label>
            <label><input type="checkbox" name="timing_3[]" value="bedtime" /> Bedtime</label>
          </div>
        </div>
      </div>

      <!-- Patient Login -->
      <label for="patient_user">Patient Username:</label>
      <input type="text" id="patient_user" name="patient_user" required />

      <label for="password">Password:</label>
      <input type="text" id="password" name="password" required />

      <button type="submit">Add Patient Info</button>
    </form>
    <a href="docmain.php">Back to Dashboard</a>
  </div>

  <script>
    function toggleDetails(checkbox, detailsId) {
      const details = document.getElementById(detailsId);
      if (checkbox.checked) {
        details.style.display = "block";
      } else {
        details.style.display = "none";
        const inputs = details.querySelectorAll("input");
        inputs.forEach(input => {
          if (input.type === "checkbox" || input.type === "radio") {
            input.checked = false;
          } else {
            input.value = "";
          }
        });
      }
    }

    // Restore state if page is reloaded with values
    document.addEventListener("DOMContentLoaded", () => {
      const meds = [
        { box: "medication_id_1", details: "details_1" },
        { box: "medication_id_2", details: "details_2" },
        { box: "medication_id_3", details: "details_3" }
      ];
      meds.forEach(m => {
        const cb = document.querySelector(`input[name=${m.box}]`);
        if (cb && cb.checked) toggleDetails(cb, m.details);
      });
    });
  </script>
</body>
</html>

