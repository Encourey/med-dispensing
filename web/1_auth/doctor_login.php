<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the SQL statement and check for errors
    $stmt = $conn->prepare("SELECT doctor_id, doctor_name FROM doctors WHERE doctor_name = ? AND password = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($doctor_id, $doctor_name);
        $stmt->fetch();

        // Set session variables
        $_SESSION['doctor_id'] = $doctor_id;
        $_SESSION['doctor_name'] = $doctor_name;

        // Regenerate session ID
        session_regenerate_id(true);

        // Redirect to doctor dashboard
        header("Location: docmain.php");
        exit();
    } else {
        echo "Invalid login credentials.";
        
        header("Location: index.php");
    }
    $stmt->close();
}
?>
