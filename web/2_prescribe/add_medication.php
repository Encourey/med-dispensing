<!-- UNUSED
<?php
session_start();
// Include database connection
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medication_name = $_POST['medication_name'];
    $mgpdosage = $_POST['mgpdosage'];

    // Prepare the SQL query
    $query = "INSERT INTO medications (medication_name, mgpdosage) VALUES (?, ?)";

    // Use prepared statements to prevent SQL injection
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ss", $medication_name, $mgpdosage);
        if ($stmt->execute()) {
            echo "Medication added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient Information</title>
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
        <form action="add_medication.php" method="POST">
        <label for="medication_name">Medication Name:</label>
        <input type="text" name="medication_name" required><br>

        <label for="dosage">Milligram per Dosage:</label>
        <input type="text" name="mgpdosage" required><br>

        <button type="submit">Add Medication</button>
    </form>
    <a href="doctor_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html> -->
