<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Invalid patient ID.";
    exit();
}

$patient_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $sql = "UPDATE patients SET password = ? WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_password, $patient_id);

    if ($stmt->execute()) {
        header("Location: docmain.php");
        exit();
    } else {
        echo "Failed to update password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Patient Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
        }

        .container {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        input[type="password"], button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
        }

        button:hover {
            background-color: #45a049;
        }

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
</head>
<body>
<div class="container">
    <h2>Change Patient Password</h2>
    <form method="POST">
        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required>

        <button type="submit">Update Password</button>
    </form>
    <a href="docmain.php">Back to Dashboard</a>
</div>
</body>
</html>
