<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $input_password = $_POST['password'];

    // ดึงข้อมูลผู้ป่วยจากฐานข้อมูลด้วย username
    $sql = "SELECT * FROM `patient_data` 
            LEFT JOIN `patients` ON `patient_data`.`id` = `patients`.`patient_id` 
            WHERE patient_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // ตรวจสอบว่าพบผู้ใช้หรือไม่
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่านด้วย password_verify
        if (password_verify($input_password, $row['password'])) {
            $_SESSION['patient_user'] = $row['patient_user'];
            $_SESSION['patient_id'] = $row['patient_id'];
            $_SESSION['patient_name'] = $row['patient_name'];

            header("Location: patient_dashboard.php");
            exit();
        } else {
            echo "❌ Incorrect password.";
        }
    } else {
        echo "❌ Username not found.";
    }
}
?>
