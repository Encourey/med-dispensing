<?php
session_start();
if (!isset($_SESSION['patient_user'])) {
    header("Location: patmain.php");
    exit();
}

header("Location: patmain.php");
?>

