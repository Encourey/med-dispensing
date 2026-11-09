-- =====================================================================
-- Medicine Dispensing System - database schema
-- Derived from the queries actually used in web/ (MySQL / MariaDB, Laragon)
--
-- Import:  mysql -u root < database/schema.sql      (then seed.sql for demo data)
-- Or in phpMyAdmin: Import -> choose this file.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS logins
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE logins;

-- ---------------------------------------------------------------------
-- Stage 1 - Auth
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id    INT AUTO_INCREMENT PRIMARY KEY,
    doctor_name  VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL          -- NOTE: doctor_login.php compares plaintext; migrate to password_hash()
) ENGINE=InnoDB;

-- patient_id mirrors patient_data.id (login joins patient_data.id = patients.patient_id).
-- add_patient.php inserts both rows in the same request, so the AUTO_INCREMENT values line up.
CREATE TABLE IF NOT EXISTS patients (
    patient_id    INT AUTO_INCREMENT PRIMARY KEY,
    patient_user  VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL         -- password_hash() output
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Stage 2 - Prescribe
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medications (
    medication_id    INT AUTO_INCREMENT PRIMARY KEY,   -- also used as the ESP32 slot / RFID mapping (1-3)
    medication_name  VARCHAR(255) NOT NULL,
    mgpdosage        INT NOT NULL                      -- mg per capsule
) ENGINE=InnoDB;

-- Up to 3 fixed prescription slots per patient (medication_id_N is a 0/1 "enabled" flag).
-- timing_N is a comma list, e.g. 'after_meal,morning,evening'
--   meal: before_meal | after_meal      times: morning | noon | evening | bedtime
CREATE TABLE IF NOT EXISTS patient_data (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    patient_name          VARCHAR(100) NOT NULL,
    age                   INT NOT NULL,
    gender                VARCHAR(10) NOT NULL,

    medication_id_1       TINYINT(1) NOT NULL DEFAULT 0,
    dosage_1              VARCHAR(50)  DEFAULT NULL,
    timing_1              VARCHAR(100) DEFAULT NULL,

    medication_id_2       TINYINT(1) NOT NULL DEFAULT 0,
    dosage_2              VARCHAR(50)  DEFAULT NULL,
    timing_2              VARCHAR(100) DEFAULT NULL,

    medication_id_3       TINYINT(1) NOT NULL DEFAULT 0,
    dosage_3              VARCHAR(50)  DEFAULT NULL,
    timing_3              VARCHAR(100) DEFAULT NULL,

    last_dispensed        DATETIME DEFAULT NULL,       -- set by process_dispense.php
    medication_dispensed  INT NOT NULL DEFAULT 0       -- running count of dispenses
) ENGINE=InnoDB;

-- Timed medications (docmain "Timed Medications" tab) - many-to-many with a schedule.
-- fromesp.php inserts (patient_id, medication_id) only, so the last two columns are nullable.
CREATE TABLE IF NOT EXISTS patient_medications (
    record_id      INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT NOT NULL,
    medication_id  INT NOT NULL,
    custom_dosage  INT  DEFAULT NULL,                  -- mg = multiplier x medications.mgpdosage
    set_time       TIME DEFAULT NULL,
    CONSTRAINT fk_pm_patient FOREIGN KEY (patient_id)
        REFERENCES patient_data(id)        ON DELETE CASCADE,
    CONSTRAINT fk_pm_medication FOREIGN KEY (medication_id)
        REFERENCES medications(medication_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lookup: label -> clock time (timing_N values such as 'morning' resolve to a TIME).
CREATE TABLE IF NOT EXISTS medications_time (
    time_id    INT AUTO_INCREMENT PRIMARY KEY,
    time_text  VARCHAR(20) NOT NULL UNIQUE,
    `time`     TIME NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Stages 3-6 - Dispense log (written by the web app and by the ESP32 callback)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dispensations (
    dispense_id          INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id            INT NOT NULL,                 -- 99 = ESP32 device (see seed.sql)
    patient_id           INT NOT NULL,
    medication_name      VARCHAR(255) NOT NULL,
    dosage               VARCHAR(50)  DEFAULT NULL,
    dispense_date        DATETIME NOT NULL,
    dispense_time_label  VARCHAR(20)  DEFAULT NULL,    -- morning / noon / ...
    dispense_time        TIME DEFAULT NULL,
    response_message     TEXT DEFAULT NULL,            -- raw response from the ESP32
    INDEX idx_disp_patient_date (patient_id, dispense_date),
    CONSTRAINT fk_disp_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id),
    CONSTRAINT fk_disp_patient FOREIGN KEY (patient_id)
        REFERENCES patient_data(id) ON DELETE CASCADE
) ENGINE=InnoDB;
