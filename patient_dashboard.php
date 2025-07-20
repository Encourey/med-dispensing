<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: patmain.php");
    exit();
}
?>

