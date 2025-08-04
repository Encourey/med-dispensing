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
    $medication_id = $_GET['id'];

    // Fetch current medication details from the database
    $sql = "SELECT * FROM medications WHERE medication_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $medication_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $medication = $result->fetch_assoc();
    } else {
        echo "Medication not found.";
        exit();
    }
} else {
    echo "Invalid medication ID.";
    exit();
}

// Handle form submission to update medication details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medication_name = $_POST['medication_name'];
    $mgpdosage = $_POST['mgpdosage'];

    $sql = "UPDATE medication SET medication_name = ?, mgpdosage = ? WHERE medication_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $medication_name, $mgpdosage, $medication_id);

    if ($stmt->execute()) {
        header("Location: patmain.php"); // Redirect to dashboard
        exit();
    } else {
        echo "Error updating medication information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medication</title>
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
        <h2>Edit Medication</h2>
        <form method="POST">
            <label for="medication_name">Medication Name:</label>
            <input type="text" id="medication_name" name="medication_name" value="<?php echo htmlspecialchars($medication['medication_name']); ?>" required>

            <label for="mgpdosage">Dosage (mg):</label>
            <input type="number" id="mgpdosage" name="mgpdosage" value="<?php echo htmlspecialchars($medication['mgpdosage']); ?>" required>

            <button type="submit">Update Medication</button>
        </form>
        <a href="docmain.php">Back to Dashboard</a>
    </div>
</body>
</html>
