<?php
include 'db_connect.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql = "SELECT patient_data.*, GROUP_CONCAT(medications.medication_name SEPARATOR ', ') AS medications
        FROM patient_data
        LEFT JOIN patient_medications ON patient_data.id = patient_medications.patient_id
        LEFT JOIN medications ON patient_medications.medication_id = medications.medication_id
        WHERE patient_data.patient_name LIKE '%$search%'
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
