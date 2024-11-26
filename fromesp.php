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

            $insertPatMed = $conn->prepare("INSERT INTO patient_medications (patient_id, medication_id) VALUES (?, ?)");
            $insertPatMed->bind_param("ii", $patient_id, $medication_id);
            $insertPatMed->execute();
            $insertPatMed->close();

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