<?php
include 'db_connect.php';

header('Content-Type: application/json');

// ตรวจสอบว่า Request Method เป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับ JSON Payload
    $jsonData = file_get_contents('php://input');

    // แปลง JSON เป็น Array
    $data = json_decode($jsonData, true);

    // ตรวจสอบว่า JSON ถูกต้องหรือไม่
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
        exit();
    }

    // ตรวจสอบว่ามีฟิลด์ที่ต้องการใน JSON หรือไม่
    if (!isset($data['patient_id']) || !isset($data['medications'])) {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    // รับค่า patient_id
    $patient_id = $data['patient_id'];

    // รับรายการ medications (Array)
    $medications = $data['medications'];

    // ตัวอย่าง: Loop ผ่าน medications เพื่อประมวลผลแต่ละรายการ
    foreach ($medications as $medication) {
        if (isset($medication['medication_id']) && isset($medication['dosage'])) {
            $medication_id = $medication['medication_id'];
            $dosage = $medication['dosage'];

            // $insertPatMed = $conn->prepare('INSERT INTO `dispensations` (`dispense_id`, `doctor_id`, `patient_id`, `medication_name`, `dosage`, `dispense_date`, `response_message`) VALUES (NULL, "1", "1", "aaa", "100", "2024-11-26 13:31:40.000000", NULL)');
            // $insertPatMed->bind_param("ii", $patient_id, $medication_id);
            // $insertPatMed->execute();
            // $insertPatMed->close();

               // เตรียมคำสั่ง SQL (ป้องกัน SQL Injection ด้วยการใช้ ? placeholder)
            $sql_queryName = $conn->prepare("SELECT medication_name FROM medications WHERE medication_id = ?");
            
            // ผูกค่า medication_id
            $sql_queryName->bind_param("s", $medication_id);
            
            // ดำเนินการ SQL Query
            $sql_queryName->execute();
            
            // ดึงผลลัพธ์
            $result = $sql_queryName->get_result();
            $medication_name = null;
            
            // อ่านค่าจากผลลัพธ์
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $medication_name = $row['medication_name'];
                }
            }
            
            // ปิด Statement
            $sql_queryName->close();
            
            // สร้าง DateTime และตั้งค่า Timezone เป็น UTC+7
            $d1 = new DateTime("now", new DateTimeZone("Asia/Bangkok"));

            // แปลงเป็นรูปแบบที่เหมาะสมสำหรับ SQL
            $dispense_date = $d1->format('Y-m-d H:i:s');

            // เตรียมคำสั่ง SQL
            $sql_insertPatMed = $conn->prepare(
                'INSERT INTO dispensations (dispense_id, doctor_id, patient_id, medication_name, dosage, dispense_date, response_message) 
                VALUES (NULL, ?, ?, ?, ?, ?, ?)'
            );

            // ผูกค่ากับ SQL
            $doctor_id = 99; // ตัวอย่างค่า doctor_id
            $response_message = "Dispense SUCCESS from ESP";
            $sql_insertPatMed->bind_param(
                "iissss", 
                $doctor_id,          // doctor_id
                $patient_id,         // patient_id
                $medication_name,    // medication_name
                $dosage,             // dosage
                $dispense_date,      // dispense_date
                $response_message    // response_message
            );

            // ดำเนินการคำสั่ง
            if ($sql_insertPatMed->execute()) {
                echo "Data inserted successfully with UTC+7 timezone.";
            } else {
                echo "Error: " . $sql_insertPatMed->error;
            }

            // ปิด statement
            $sql_insertPatMed->close();


            // สำหรับ Debugging
            error_log("Processed: Patient ID: $patient_id, Medication ID: $medication_id, Dosage: $dosage");
        } else {
            // หากข้อมูล medication_id หรือ dosage ไม่ครบ
            http_response_code(400); // Bad Request
            echo json_encode(["status" => "error", "message" => "Invalid medication data"]);
            exit();
        }
    }

    // ส่ง Response กลับไปยัง ESP32
    http_response_code(200); // OK
    echo json_encode(["status" => "success", "message" => "Dispense process completed"]);
} else {
    // หากไม่ใช่ POST Method
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>