<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['patient_user'])) {
    exit("Unauthorized");
}

$patient_id = $_SESSION['patient_id'];
$today = date("Y-m-d");

// Medication names
$med_names = [];
$res = $conn->query("SELECT medication_id, medication_name FROM medications");
while ($r = $res->fetch_assoc()) {
    $med_names[$r['medication_id']] = $r['medication_name'];
}

// Time map
$time_map = [];
$res = $conn->query("SELECT time_text, time FROM medications_time");
while ($r = $res->fetch_assoc()) {
    $time_map[$r['time_text']] = $r['time'];
}

// Fetch patient data
$pat_stmt = $conn->prepare("SELECT * FROM patient_data WHERE id = ?");
$pat_stmt->bind_param("i", $patient_id);
$pat_stmt->execute();
$pat_result = $pat_stmt->get_result();

if ($pat_result->num_rows > 0) {
    $patient = $pat_result->fetch_assoc();
} else {
    exit("Patient not found");
}

// Load today's dispense records
$dispensed_today = [];
$disp_sql = "SELECT dispense_time_label, dispense_time, response_message 
             FROM dispensations 
             WHERE patient_id = ? AND DATE(dispense_date) = ?";
$disp_stmt = $conn->prepare($disp_sql);
$disp_stmt->bind_param("is", $patient_id, $today);
$disp_stmt->execute();
$disp_result = $disp_stmt->get_result();

while ($row = $disp_result->fetch_assoc()) {
    if ($row['response_message'] === 'Dispense SUCCESS') {
        $key = $row['dispense_time_label'] . '_' . $row['dispense_time'];
        $dispensed_today[$key] = true;
    }
}

$rows = [];
for ($i = 1; $i <= 3; $i++) {
    if (!empty($patient["medication_id_$i"])) {
        $dosage = $patient["dosage_$i"];
        $timings = explode(',', $patient["timing_$i"]);
        $med_name = $med_names[$patient["medication_id_$i"]] ?? "Medication $i";

        $meal = '';
        foreach ($timings as $t) {
            if ($t === 'before_meal') $meal = 'Before Meal';
            elseif ($t === 'after_meal') $meal = 'After Meal';
        }

        foreach ($timings as $t) {
            if (!isset($time_map[$t])) continue;

            $disp_time = $time_map[$t]; // HH:MM:SS
            $key = $t . '_' . $disp_time;
            $status = isset($dispensed_today[$key]) ? 'Taken' : 'Pending';

            $rows[] = [
                'med' => $med_name,
                'dosage' => $dosage,
                'meal' => $meal,
                'time' => $disp_time,
                'status' => $status
            ];
        }
    }
}

usort($rows, fn($a, $b) => strcmp($a['time'], $b['time']));

foreach ($rows as $r) {
    echo "<tr>
        <td>{$r['med']}</td>
        <td>{$r['dosage']} capsule(s)</td>
        <td>{$r['meal']}</td>
        <td>" . date('H:i', strtotime($r['time'])) . "</td>
        <td><strong style='color:" . ($r['status'] === 'Taken' ? 'green' : 'red') . ";'>{$r['status']}</strong></td>
    </tr>";
}