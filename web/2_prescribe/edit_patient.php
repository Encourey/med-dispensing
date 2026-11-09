<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db_connect.php';

if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Invalid patient ID.";
    exit();
}

$patient_id = $_GET['id'];

$sql = "SELECT * FROM patient_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}
$patient = $result->fetch_assoc();

// Extract med info
function extract_meal($timings) {
    foreach ($timings as $t) {
        if ($t === 'before_meal' || $t === 'after_meal') return $t;
    }
    return '';
}
function extract_times($timings) {
    return array_filter($timings, fn($t) => !in_array($t, ['before_meal','after_meal']));
}

$meds = [1 => [], 2 => [], 3 => []];
foreach ($meds as $i => $_) {
    $meds[$i]['checked'] = $patient["medication_id_$i"];
    $meds[$i]['dosage'] = $patient["dosage_$i"];
    $timing = explode(',', $patient["timing_$i"] ?? '');
    $meds[$i]['meal'] = extract_meal($timing);
    $meds[$i]['timing'] = extract_times($timing);
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['patient_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];

    for ($i = 1; $i <= 3; $i++) {
        $meds[$i]['checked'] = isset($_POST["medication_id_$i"]) ? 1 : 0;
        $meds[$i]['dosage'] = $_POST["dosage_$i"] ?? '';
        $meal = $_POST["meal_$i"] ?? '';
        $timing = $_POST["timing_$i"] ?? [];
        $combined = $meal ? [$meal] : [];
        $combined = array_merge($combined, $timing);
        $meds[$i]['timing'] = implode(',', $combined);
    }

    $sql = "UPDATE patient_data SET patient_name=?, age=?, gender=?,
            medication_id_1=?, dosage_1=?, timing_1=?,
            medication_id_2=?, dosage_2=?, timing_2=?,
            medication_id_3=?, dosage_3=?, timing_3=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisssissssi",
        $name, $age, $gender,
        $meds[1]['checked'], $meds[1]['dosage'], $meds[1]['timing'],
        $meds[2]['checked'], $meds[2]['dosage'], $meds[2]['timing'],
        $meds[3]['checked'], $meds[3]['dosage'], $meds[3]['timing'],
        $patient_id
    );
    if ($stmt->execute()) {
        header("Location: docmain.php");
        exit();
    } else {
        echo "Error updating patient.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Edit Patient</title>
  <style>
    body { font-family: Arial; background: #f4f7fa; }
    .container {
      max-width: 700px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px #ccc;
    }
    h2 { text-align: center; }
    label { font-weight: bold; display: block; margin-top: 10px; }
    input[type="text"], input[type="number"] {
      width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;
    }
    .medication-section {
      border: 1px solid #ddd; margin-top: 20px; padding: 15px; border-radius: 8px; background: #fafafa;
    }
    .checkbox-group, .radio-group {
      display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;
    }
    button {
      margin-top: 20px; padding: 12px;
      background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 1em;
    }
    
    a {
        display: block;
        margin-top: 15px;
        text-align: center;
        color: #4CAF50;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
<div class="container">
  <h2>Edit Patient</h2>
  <form method="POST">
    <label>Patient Name:</label>
    <input type="text" name="patient_name" value="<?= htmlspecialchars($patient['patient_name']) ?>" required>

    <label>Age:</label>
    <input type="number" name="age" value="<?= $patient['age'] ?>" required>

    <label>Gender:</label>
    <div class="radio-group">
      <label><input type="radio" name="gender" value="MALE" <?= $patient['gender'] === 'MALE' ? 'checked' : '' ?>> Male</label>
      <label><input type="radio" name="gender" value="FEMALE" <?= $patient['gender'] === 'FEMALE' ? 'checked' : '' ?>> Female</label>
    </div>

    <?php
    $med_labels = [1 => 'Paracetamol', 2 => 'Ibuprofen', 3 => 'Amoxicillin'];
    foreach ($meds as $i => $m) {
    ?>
    <div class="medication-section">
      <label><input type="checkbox" name="medication_id_<?= $i ?>" value="1" <?= $m['checked'] ? 'checked' : '' ?>> <?= $med_labels[$i] ?></label>

      <label>Dosage (capsules):</label>
      <input type="number" name="dosage_<?= $i ?>" value="<?= htmlspecialchars($m['dosage']) ?>">

      <label>Meal Timing:</label>
      <div class="radio-group">
        <label><input type="radio" name="meal_<?= $i ?>" value="before_meal" <?= $m['meal'] === 'before_meal' ? 'checked' : '' ?>> Before Meal</label>
        <label><input type="radio" name="meal_<?= $i ?>" value="after_meal" <?= $m['meal'] === 'after_meal' ? 'checked' : '' ?>> After Meal</label>
      </div>

      <label>Times:</label>
      <div class="checkbox-group">
        <?php
        foreach (['morning','noon','evening','bedtime'] as $t) {
          $checked = in_array($t, $m['timing']) ? 'checked' : '';
          echo "<label><input type='checkbox' name='timing_{$i}[]' value='{$t}' {$checked}> ".ucfirst($t)."</label>";
        }
        ?>
      </div>
    </div>
    <?php } ?>

    <button type="submit">Update Patient Info</button>
    <a href="docmain.php">Back to Dashboard</a>
  </form>

</div>

</body>
</html>
