#include <WiFi.h>
#include <ESPAsyncWebServer.h>
#include <ESP32Servo.h>

const char* ssid = "Galaxy A33 5G Runch";
const char* password = "thefifth";

Servo servo1;
Servo servo2;
const int servoPin1 = 25; // กำหนดขา PWM สำหรับ Servo
const int servoPin2 = 26; 

AsyncWebServer server(80);

void setup() {
  Serial.begin(115200);

  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Wi-Fi connected! IP: " + WiFi.localIP().toString());

  servo1.attach(servoPin1);
  servo2.attach(servoPin2);
  servo1.write(0); // ตั้งมุมเริ่มต้นที่ 0 องศา
  servo2.write(0);

  server.on("/servo", HTTP_GET, [](AsyncWebServerRequest *request) {
    if (request->hasParam("angle")) {
      String angleParam = request->getParam("angle")->value();
      int angle = angleParam.toInt();
      if (angle >= 0 && angle <= 180) {
        servo1.write(angle);
        servo2.write(angle);
        delay(500);
        request->send(200, "text/plain", "Servo moved to " + angleParam + " degrees");
        Serial.println("Servo moved to " + String(angle) + " degrees");
      } else {
        request->send(400, "text/plain", "Invalid angle");
        Serial.println("Invalid angle: " + String(angle));
      }
    } else {
      request->send(400, "text/plain", "Missing angle parameter");
      Serial.println("Missing angle parameter");
    }
  });

  server.begin();
  Serial.println("Server started");
}

void loop() {
  // ไม่มีอะไรใน loop เพราะใช้ AsyncWebServer
}
