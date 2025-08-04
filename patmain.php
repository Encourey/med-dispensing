<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['patient_user'])) {
    header("Location: index.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$today = date("Y-m-d");

// Fetch patient data
$sql = "SELECT * FROM patient_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Dashboard</title>
  <style>
    body { font-family: Arial; background: #f4f7fa; margin: 0; padding: 0; }
    header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 20px; background: #2e7d32; color: white;
    }
    .container { max-width: 1000px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 10px; }
    h3 { text-align: center; font-size: 1.8em; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; font-size: 1.2em; }
    th, td { border: 1px solid #ccc; padding: 12px; text-align: center; }
    th { background-color: #ffd54f; }
    .logout-btn { background: #f44336; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
    .clock { font-size: 1.5em; font-weight: bold; }
  </style>
  <script>
    setInterval(() => {
      const now = new Date();
      const yyyy = now.getFullYear();
      const mm = String(now.getMonth() + 1).padStart(2, '0');
      const dd = String(now.getDate()).padStart(2, '0');
      const hh = String(now.getHours()).padStart(2, '0');
      const mi = String(now.getMinutes()).padStart(2, '0');
      const ss = String(now.getSeconds()).padStart(2, '0');
      const dateStr = `${dd}/${mm}/${yyyy}`;
      const timeStr = `${hh}:${mi}:${ss}`;
      document.getElementById('clock').innerText = `${dateStr} ${timeStr}`;
    }, 1000);

    function refreshTable() {
        fetch('load_table.php')
            .then(response => response.text())
            .then(html => {
            document.getElementById('med-table-body').innerHTML = html;
            })
            .catch(err => console.error('Failed to fetch table rows:', err));
    }
    // Initial load
    refreshTable();
    // Refresh every 10 seconds
    setInterval(refreshTable, 10000);

  </script>
</head>
<body>
<header>
  <div>Welcome, <?= htmlspecialchars($_SESSION['patient_name']) ?> (ID: <?= $patient_id ?>)</div>
  <div style="display:flex; align-items:center; gap:15px">
    <div id="clock" class="clock"></div>
    <form action="logout.php" method="post"><button class="logout-btn">Logout</button></form>
  </div>
</header>
<div class="container">
  <h3>💊 Today's Medication Schedule</h3>
  <div id="med-table">
    <table>
        <thead>
            <tr>
            <th>Medication</th>
            <th>Dosage</th>
            <th>Meal Time</th>
            <th>Time</th>
            <th>Status</th>
            </tr>
        </thead>
        <tbody id="med-table-body">
            <!-- Rows will be injected here -->
        </tbody>
    </table>
  </div>
</div>
</body>
</html>
