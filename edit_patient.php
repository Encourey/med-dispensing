<?php
session_start();
include 'db_connect.php';

// Check if the doctor is logged in
if (!isset($_SESSION['doctor_name'])) {
    header("Location: index.php");
    exit();
}

// Check if an ID is passed via GET request
if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];

    // Fetch current patient details from the database
    $sql = "SELECT * FROM patient_data WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    } else {
        echo "Patient not found.";
        exit();
    }
} else {
    echo "Invalid patient ID.";
    exit();
}

// Handle form submission to update patient details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = $_POST['patient_name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $medication_name = $_POST['medication'];
    $dosage = $_POST['dosage'];

    // Update the patient details in the database
    $sql = "UPDATE patient_data SET patient_name = ?, age = ?, gender = ?, medication_name = ?, dosage = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $patient_name, $age, $gender, $medication_name, $dosage, $patient_id);
    if ($stmt->execute()) {
        header("Location: maindoc.php");  // Redirect back to the dashboard after successful update
        exit();
    } else {
        echo "Error updating patient information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        h2 {
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin: 10px 0 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="number"] {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        button:hover {
            background-color: #45a049;
        }

        a {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #4CAF50;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Patient Information</h2>
        <form method="POST">
            <label for="patient_name">Patient Name:</label>
            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($patient['patient_name']); ?>" required>

            <label for="age">Age:</label>
            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($patient['age']); ?>" required>

            <label for="gender">Gender:</label>
            <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($patient['gender']); ?>" required>
            
            <button type="submit">Update Patient Info</button>
        </form>
        <a href="docmain.php">Back to Dashboard</a>
    </div>
</body>
</html>
