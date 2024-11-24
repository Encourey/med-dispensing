<?php
session_start();
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link rel="stylesheet" href="style.css">
  <style>
  body {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
  }
  
  .login-container {
    width: 300px;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    text-align: center;
  }
  
  .login-container h1 {
    margin-bottom: 20px;
  }
  
  .login-form {
    display: flex;
    flex-direction: column;
  }
  
  .login-form label {
    margin-bottom: 5px;
    text-align: left;
  }
  
  .login-form input {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  
  .login-form button {
    padding: 10px;
    background-color: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .login-form button:hover {
    background-color: #45a049;
  }
  
  .switch-link {
    margin-top: 10px;
  }
  
  .switch-link a {
    color: #4CAF50;
    text-decoration: none;
    cursor: pointer;
  }
  
  .switch-link a:hover {
    text-decoration: underline;
  }
  </style>
</head>
<body>
  <div class="login-container">
    <h1 id="login-title">Doctor Login</h1>

    <!-- Doctor Login Form -->
    <form id="doctor-login-form" method="POST" action="doctor_login.php" class="login-form">
      <label for="doctor-username">Username:</label>
      <input type="text" id="doctor-username" name="username" required><br>

      <label for="doctor-password">Password:</label>
      <input type="password" id="doctor-password" name="password" required><br>

      <button type="submit">Login</button>
    </form>

    <!-- Patient Login Form -->
    <form id="patient-login-form" method="POST" action="patient_login.php" class="login-form" style="display: none;">
      <label for="patient-username">Username:</label>
      <input type="text" id="patient-username" name="username" required><br>

      <label for="patient-password">Password:</label>
      <input type="password" id="patient-password" name="password" required><br>

      <button type="submit">Login</button>
    </form>

    <!-- Links to switch between forms -->
    <p class="switch-link">
      <a href="#" onclick="switchToDoctor()">Doctor Login</a> |
      <a href="#" onclick="switchToPatient()">Patient Login</a>
    </p>
  </div>

  <!-- JavaScript to toggle between login forms -->
  <script>
    function switchToDoctor() {
      document.getElementById('doctor-login-form').style.display = 'block';
      document.getElementById('patient-login-form').style.display = 'none';
      document.getElementById('login-title').textContent = 'Doctor Login';
    }

    function switchToPatient() {
      document.getElementById('doctor-login-form').style.display = 'none';
      document.getElementById('patient-login-form').style.display = 'block';
      document.getElementById('login-title').textContent = 'Patient Login';
    }
  </script>
</body>
</html>
