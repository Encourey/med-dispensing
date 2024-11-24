// Function to show the Doctor Login form
function switchToDoctor() {
    document.getElementById('login-title').innerText = 'Doctor Login';
    document.getElementById('doctor-login-form').style.display = 'block';
    document.getElementById('patient-login-form').style.display = 'none';
  }
  
  // Function to show the Patient Login form
  function switchToPatient() {
    document.getElementById('login-title').innerText = 'Patient Login';
    document.getElementById('doctor-login-form').style.display = 'none';
    document.getElementById('patient-login-form').style.display = 'block';
  }
  