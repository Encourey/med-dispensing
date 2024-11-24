<?php
session_start();
include 'db_connect.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM patients WHERE patient_name = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password); // Bind parameters to prevent SQL injection
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['patient_name'] = $row['patient_name'];  

        // Redirect to the dashboard page
        header("Location: patient_dashboard.php");
        exit();  // Ensure no further code runs after the redirect
    } else {
        echo "Patient username or password is incorrect.";
    }
}
?>
