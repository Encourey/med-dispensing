<?php
session_start();
include 'db_connect.php';

// Check if the doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

// Check if dispense_id is set
if (isset($_POST['dispense_id'])) {
    $dispense_id = $_POST['dispense_id'];

    // Delete the specific entry from the prescription log
    $sql = "DELETE FROM dispensations WHERE dispense_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dispense_id);

    if ($stmt->execute()) {
        header("Location: doctor_dashboard.php");  // Redirect back to the dashboard after deletion
        exit();
    } else {
        echo "Error deleting prescription entry.";
    }
} else {
    echo "Invalid request.";
}
?>
