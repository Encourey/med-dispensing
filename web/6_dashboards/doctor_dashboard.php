<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: docmain.php");
    exit();
}

?>


