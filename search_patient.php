<?php
    include 'db_connect.php';

    // Retrieve the search term from the AJAX request
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    // Prepare SQL query based on the search term
    if ($searchTerm) {
        $sql = "SELECT * FROM patient_data WHERE patient_name LIKE ? ";
        $stmt = $conn->prepare($sql);
        $likeTerm = "%" . $searchTerm . "%";
        $stmt->bind_param("s", $likeTerm);
    } else {
        // If no search term, fetch all patients
        $sql = "SELECT * FROM patient_data";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
?>
    <table class="patient-info-table" id="patientTable">
        <tr>
            <th>Patient ID</th>
            <th>Patient Name</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Action</th>
        </tr>
    <?php
    // Generate HTML for patient table rows
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['patient_name']}</td>
                    <td>{$row['age']}</td>
                    <td>{$row['gender']}</td>
                    <td class='button-group'>
                        <form action='dispense_meds.php' method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to Dispense?\");'>
                            <input type='hidden' name='patient_id' value='{$row['id']}'>
                            <button type='submit' class='dispense-btn'>Dispense Medication</button>
                        </form>
                        <a href='edit_patient.php?id={$row['id']}'>
                           <button class='edit-btn'>Edit</button>
                        </a>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No records found</td></tr>";
    }

    $conn->close();
    ?>
