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

function showSection(sectionId) {
  // Hide all sections
  document.querySelectorAll('.section').forEach(section => {
    section.style.display = 'none';
  });

  // Show the selected section
  document.getElementById(sectionId).style.display = 'block';
}

//function for dispensing and deleting entries
function confirmDispense() {
  return confirm("Are you sure you want to Dispense.");
}

function confirmDelete() {
  return confirm("Are you sure you want to delete this entry? This action cannot be undone.");
}
//for real time patient searching
function searchPatient() {
    const searchTerm = document.getElementById("search").value;
    const xhr = new XMLHttpRequest();

    xhr.open("GET", "search_patient.php?search=" + encodeURIComponent(searchTerm), true);

    xhr.onerror = function () {
        console.error("Request failed");
    };

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                // Replace only the <tbody> content
                document.getElementById("patientTable").innerHTML = xhr.responseText;
            } else {
                console.error("Error: " + xhr.status);
            }
        }
    };

    xhr.send();
}


function searchPrescription() {
  const patientName = document.getElementById("patient-name").value;

  console.log("Searching prescriptions for:", patientName); // Debugging line to verify input

  const xhr = new XMLHttpRequest();
  xhr.open("GET", "search_prescript.php?search=" + encodeURIComponent(patientName), true); // Send only patient_name

  xhr.onerror = function () {
    console.error("Request failed");
  };

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        console.log("Prescription search results:", xhr.responseText); // Log response for debugging
        document.getElementById("prescriptionTable").innerHTML = xhr.responseText;
      } else {
        console.error("Error: " + xhr.status); // Log status if an error occurs
      }
    }
  };

  xhr.send();
}

let lastKnownStatus = "";
async function checkStatus() {
  const statusElement = document.getElementById('machine-status');
  const dispenseButtons = document.querySelectorAll('.dispense-btn');

  try {
    const response = await fetch('check_status.php');
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

    const data = await response.json();
    const currentStatus = data.status;

    // Only update DOM if status actually changed
    if (currentStatus !== lastKnownStatus) {
      lastKnownStatus = currentStatus;

      if (currentStatus === "enabled") {
        statusElement.textContent = "Machine is Enabled";
        statusElement.style.color = "green";
        dispenseButtons.forEach(btn => {
          btn.disabled = false;
          btn.style.opacity = "1";
          btn.style.cursor = "pointer";
        });

      } else if (currentStatus === "disabled") {
        statusElement.textContent = "Machine is Disabled";
        statusElement.style.color = "red";
        dispenseButtons.forEach(btn => {
          btn.disabled = true;
          btn.style.opacity = "0.5";
          btn.style.cursor = "not-allowed";
        });
      } else {
        statusElement.textContent = "Unknown Status";
        statusElement.style.color = "orange";
        dispenseButtons.forEach(btn => {
          btn.disabled = true;
          btn.style.opacity = "0.5";
          btn.style.cursor = "not-allowed";
        });
      }
    }

  } catch (error) {
    if (lastKnownStatus !== "error") {
      lastKnownStatus = "error";
      statusElement.textContent = "Error Connecting";
      statusElement.style.color = "gray";
      dispenseButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = "0.5";
        btn.style.cursor = "not-allowed";
      });
    }
  }
}
setInterval(checkStatus, 2000);
checkStatus();

var interval = setInterval(timestamphome, 1000);
function timestamphome() {
  var date;
  date = new Date();
  var time = document.getElementById('timediv');
  time.innerHTML = date.toLocaleTimeString();
}

setInterval(() => {
      const now = new Date();
      const yyyy = now.getFullYear();
      const mm = String(now.getMonth() + 1).padStart(2, '0');
      const dd = String(now.getDate()).padStart(2, '0');
      const hh = String(now.getHours()).padStart(2, '0');
      const mi = String(now.getMinutes()).padStart(2, '0');
      const ss = String(now.getSeconds()).padStart(2, '0');
      const dateStr = `${dd}/${mm}/${yyyy}`;
      const timeStr = `${hh}:${mi}:${ss}`;
      document.getElementById('clock').innerText = `${dateStr} ${timeStr}`;
    }, 1000);