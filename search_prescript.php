<?php
include 'db_connect.php'; // Ensure database connection

// Retrieve the search term from the AJAX request
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare SQL query based on the search term
if ($searchTerm) {
    $sql = "SELECT dispensations.*, patient_data.patient_name, doctors.doctor_name 
            FROM dispensations
            JOIN patient_data ON dispensations.patient_id = patient_data.id
            JOIN doctors ON dispensations.doctor_id = doctors.doctor_id
            WHERE patient_data.patient_name LIKE ?
            ORDER BY dispensations.dispense_date DESC";
    
    $stmt = $conn->prepare($sql);
    $likeTerm = "%" . $searchTerm . "%";
    $stmt->bind_param("s", $likeTerm);
} else {
    // If no search term, fetch all dispensations
    $sql = "SELECT dispensations.*, patient_data.patient_name, doctors.doctor_name 
            FROM dispensations
            JOIN patient_data ON dispensations.patient_id = patient_data.id
            JOIN doctors ON dispensations.doctor_id = doctors.doctor_id
            ORDER BY dispensations.dispense_date DESC";
    
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<table class="prescription-info-table" id="prescriptTable">
    <tr>
        <th>Patient Name</th>
        <th>Medication</th>
        <th>Dosage</th>
        <th>Doctor</th>
        <th>Dispense Date</th>
        <th>Action</th>
    </tr>
<?php
// Generate HTML for prescription table rows
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

$conn->close();
?>
