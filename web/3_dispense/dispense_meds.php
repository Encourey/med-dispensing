<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    echo "Doctor ID not set in session. Redirecting to login...";
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$patient_id = $_GET['patient_id'] ?? $_POST['patient_id'] ?? die("Error: Patient ID not provided.");

// Get patient_data for timing reference
$sql = "SELECT * FROM patient_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

$medication_map = [
  1 => ['name' => 'Paracetamol', 'dosage' => $patient['dosage_1'], 'timing' => explode(',', $patient['timing_1'])],
  2 => ['name' => 'Carbocisteine', 'dosage' => $patient['dosage_2'], 'timing' => explode(',', $patient['timing_2'])],
  3 => ['name' => 'Amoxicillin', 'dosage' => $patient['dosage_3'], 'timing' => explode(',', $patient['timing_3'])],
];

// Time references
$time_map = [];
$res = $conn->query("SELECT time_text, time FROM medications_time");
while ($r = $res->fetch_assoc()) {
    $time_map[$r['time_text']] = $r['time'];
}

// Build combined list
$medications = [];
foreach ($medication_map as $id => $info) {
    if ($patient["medication_id_{$id}"]) {
        foreach ($info['timing'] as $t) {
            if (isset($time_map[$t])) {
                $medications[] = [
                    'medication_id' => $id,
                    'medication_name' => $info['name'],
                    'dosage_unit' => $info['dosage'],
                    'time_label' => ucfirst($t),
                    'time' => $time_map[$t],
                    'mgpdosage' => 500 // You can fetch this dynamically if needed
                ];
            }
        }
    }
}

// Sort by time ascending
usort($medications, fn($a, $b) => strcmp($a['time'], $b['time']));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dispense Medications</title>
  <style>
    body { font-family: Arial; background: #f4f4f9; padding: 40px; }
    .container { background: #fff; padding: 20px; max-width: 800px; margin: auto; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
    th { background: #eee; }
    button { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; margin-top: 15px; cursor: pointer; }
    button:hover { background: #388e3c; }
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
  <script>
    function validateForm() {
      const checked = document.querySelectorAll('input[name="medication_ids[]"]:checked');
      if (checked.length === 0) {
        alert("Please select at least one medication to dispense.");
        return false;
      }
      return true;
    }
  </script>
</head>
<body>
<div class="container">
  <h2>Dispense Medications for Patient ID: <?= htmlspecialchars($patient_id) ?></h2>
  <form action="process_dispense.php" method="POST" onsubmit="return validateForm();">
    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
    <table>
      <tr>
        <th>Select</th>
        <th>Medication</th>
        <th>Dosage</th>
        <th>Time</th>
      </tr>
      <?php foreach ($medications as $m): ?>
        <tr>
          <td><input type="checkbox" name="medication_ids[]" value="<?= $m['medication_id'] ?>_<?= $m['time'] ?>"></td>
          <td><?= $m['medication_name'] ?></td>
          <td><?= $m['dosage_unit'] ?> capsule(s)</td>
          <td><?= date('H:i', strtotime($m['time'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <button type="submit">Dispense Selected Medications</button>
  </form>
  <a href="docmain.php">Back to Dashboard</a>
</div>
</body>
</html>
