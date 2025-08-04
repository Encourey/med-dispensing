#include <WiFi.h>
#include <ESPAsyncWebServer.h>
#include <ESP32Servo.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// WiFi credentials
const char* ssid = "MedNetwork";
const char* password = "1234567890";

// Servo pins
const int servoPin1 = 25;
const int servoPin2 = 26;
const int servoPin3 = 27;

// Servo objects
Servo servo1;
Servo servo2;
Servo servo3;

AsyncWebServer server(80);
const char* serverUrl = "http://192.168.4.167/logins/process_dispense_esp.php";

void setup() {
  Serial.begin(115200);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("WiFi connected. IP: " + WiFi.localIP().toString());

  // Attach servos
  servo1.attach(servoPin1);
  servo2.attach(servoPin2);
  servo3.attach(servoPin3);

  // Initialize servos to 0 degree position
  servo1.write(90);
  servo2.write(90);
  servo3.write(90);

  // GET /servo?slot=x handler (existing)
  server.on("/servo", HTTP_GET, [](AsyncWebServerRequest* request) {
    if (!request->hasParam("slot")) {
      request->send(400, "text/plain", "Missing 'slot' parameter");
      return;
    }

    String slotParam = request->getParam("slot")->value();
    int slot = slotParam.toInt();

    if (slot < 1 || slot > 3) {
      request->send(400, "text/plain", "Invalid slot number, must be 1, 2, or 3");
      return;
    }

    Serial.printf("Dispensing slot %d\n", slot);

    bool success = dispenseMedication(slot);

    if (success) {
      request->send(200, "text/plain", "Dispense Success on slot " + String(slot));
    } else {
      request->send(500, "text/plain", "Dispense Failed");
    }
  });

  server.on("/dispense", HTTP_POST, [](AsyncWebServerRequest* request) {
    int patient_id = request->arg("patient_id").toInt();
    int medication_id = request->arg("medication_id").toInt();
    int dosage = request->arg("dosage").toInt();
    
    Serial.printf("Parsed args:\n  patient_id: %d\n  medication_id: %d\n  dosage: %d\n", patient_id, medication_id, dosage);

    // Map medication_id to slot (customize if needed)
    int slot = medication_id;  // 1-to-1 mapping for now

    if (slot < 1 || slot > 3) {
      request->send(400, "text/plain", "Invalid medication_id (slot)");
      return;
    }

    bool success = dispenseMedication(slot);

    if (success) {
      sendDispenseResult(patient_id, medication_id, dosage);
      request->send(200, "text/plain", "success");
    } else {
      request->send(500, "text/plain", "failed to dispense");
    }
  });


  server.begin();
  Serial.println("HTTP server started");
}

void loop() {
  // Async server handles everything
}

bool dispenseMedication(int slot) {
  Servo* servoPtr = nullptr;

  switch (slot) {
    case 1:
      servoPtr = &servo1;
      break;
    case 2:
      servoPtr = &servo2;
      break;
    case 3:
      servoPtr = &servo3;
      break;
    default:
      return false;
  }

  if (!servoPtr) return false;

  // Move servo from 90° to 180° and back
  servoPtr->write(180);
  delay(1000);  // Hold for dispensing
  servoPtr->write(90);
  delay(500);  // Wait before next

  return true;
}


void sendDispenseResult(int patient_id, int medication_id, int dosage) {
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");

  // Prepare JSON object
  DynamicJsonDocument doc(512);
  doc["patient_id"] = patient_id;
  doc["dispense_time"] = "00:00:00";  // or any specific time like "08:30:00"

  JsonArray meds = doc.createNestedArray("medications");
  JsonObject med = meds.createNestedObject();
  med["medication_id"] = medication_id;
  med["dosage"] = dosage;

  String json;
  serializeJson(doc, json);
  Serial.println("Sending JSON:");
  Serial.println(json);

  // Send the POST request
  int httpResponseCode = http.POST(json);

  if (httpResponseCode > 0) {
    Serial.printf("POST Response Code: %d\n", httpResponseCode);
    String response = http.getString();
    Serial.println("Server response: " + response);
  } else {
    Serial.printf("POST failed, error: %s\n", http.errorToString(httpResponseCode).c_str());
  }

  http.end();
}
