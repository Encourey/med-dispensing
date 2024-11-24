<?php
session_start();
if (!isset($_SESSION['patient_name'])) {
    header("Location: index.php");
    exit();  
}
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient's Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
            color: #333;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px 0;
            text-align: center;
        }

        header h2 {
            margin: 0;
            font-size: 1.8em;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .container h3 {
            color: #4CAF50;
            font-size: 1.4em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
            color: #333;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        table td {
            border-bottom: 1px solid #ddd;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.9em;
            border-radius: 4px;
        }

        button:hover {
            background-color: #45a049;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #4CAF50;
            color: white;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            header h2 {
                font-size: 1.5em;
            }

            table th, table td {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <header>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['patient_name']); ?></h2>
    </header>

    <div class="container">
        <h3>Patient Information</h3>
        <table>
            <tr>
                <th>Patient ID</th>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Medication</th>
                <th>Dosage</th>
            </tr>

            <?php
            $sql = "SELECT * FROM patient_data"; 
            $result = $conn->query($sql);  

            if ($result === false) {
                echo "Error executing query: " . $conn->error;
            } else {
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . $row["id"] . "</td>
                                <td>" . $row["patient_name"] . "</td>
                                <td>" . $row["age"] . "</td>
                                <td>" . $row["gender"] . "</td>
                                <td>" . ($row["medication"] ?: 'No medication assigned') . "</td>
                                <td>" . ($row["dosage"] ?: 'N/A') . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No patient records found</td></tr>";
                }
            }

            $conn->close();
            ?>
        </table>
    </div>

    <footer>
        <a href="logout.php">Logout</a>
    </footer>

</body>
</html>
