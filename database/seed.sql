-- Demo data. Run after schema.sql.   mysql -u root < database/seed.sql
USE logins;

-- Demo doctor (doctor_login.php currently compares plaintext passwords).
-- Doctor 99 is the ESP32 device: process_dispense_esp.php logs dispenses under this id.
-- Its password is random and not meant for login.
INSERT INTO doctors (doctor_id, doctor_name, password) VALUES
    (1,  'demo_doctor',   'demo123'),
    (99, 'ESP32 Device',  'not-a-login-b7f3c1d9e2a84f60')
ON DUPLICATE KEY UPDATE doctor_name = VALUES(doctor_name), password = VALUES(password);

-- Slots 1-3 match the servo slots and the RFID card table in firmware/esp32_reader.
INSERT INTO medications (medication_id, medication_name, mgpdosage) VALUES
    (1, 'Paracetamol',   500),
    (2, 'Carbocisteine', 500),
    (3, 'Amoxicillin',   500)
ON DUPLICATE KEY UPDATE medication_name = VALUES(medication_name);

-- Clock times for each timing label - adjust to your real schedule.
INSERT INTO medications_time (time_text, `time`) VALUES
    ('morning', '08:00:00'),
    ('noon',    '12:00:00'),
    ('evening', '18:00:00'),
    ('bedtime', '21:00:00')
ON DUPLICATE KEY UPDATE `time` = VALUES(`time`);

-- Demo patient (login: demo_patient / demo123; password stored as a bcrypt hash, as add_patient.php expects).
INSERT INTO patient_data (id, patient_name, age, gender,
                          medication_id_1, dosage_1, timing_1)
VALUES (1, 'Demo Patient', 30, 'FEMALE', 1, '1', 'after_meal,morning,evening')
ON DUPLICATE KEY UPDATE patient_name = VALUES(patient_name);

INSERT INTO patients (patient_id, patient_user, password)
VALUES (1, 'demo_patient', '$2y$10$cOun6TyfPfzcVJyv/3Khb.oU/XRaAXhTmjVp9cj29YycRQfxOcljy')
ON DUPLICATE KEY UPDATE patient_user = VALUES(patient_user), password = VALUES(password);
