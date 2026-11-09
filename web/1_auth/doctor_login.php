<?php
session_start();
include __DIR__ . '/../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the SQL statement and check for errors
    $stmt = $conn->prepare("SELECT doctor_id, doctor_name, password FROM doctors WHERE doctor_name = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    $login_ok = false;
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($doctor_id, $doctor_name, $stored_password);
        $stmt->fetch();

        // id 99 is the ESP32 service account (never a human login); empty passwords never match.
        if ($doctor_id != 99 && $stored_password !== '' && $password !== '') {
            if (password_get_info($stored_password)['algoName'] !== 'unknown') {
                // Already hashed
                $login_ok = password_verify($password, $stored_password);
            } elseif (hash_equals($stored_password, $password)) {
                // Legacy plaintext password: accept once, then store a hash instead
                $login_ok = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $upg = $conn->prepare("UPDATE doctors SET password = ? WHERE doctor_id = ?");
                $upg->bind_param("si", $new_hash, $doctor_id);
                $upg->execute();
                $upg->close();
            }
        }
    }

    if ($login_ok) {

        // Set session variables
        $_SESSION['doctor_id'] = $doctor_id;
        $_SESSION['doctor_name'] = $doctor_name;

        // Regenerate session ID
        session_regenerate_id(true);

        // Redirect to doctor dashboard
        header("Location: ../6_dashboards/docmain.php");
        exit();
    } else {
        echo "Invalid login credentials.";
        
        header("Location: ../index.php");
    }
    $stmt->close();
}
?>
