/************ Includes and Initializations *********************/
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Keypad.h>
#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h> 
#include <ArduinoJson.h>

// LCD
LiquidCrystal_I2C lcd(0x27, 12, 6);

// Keypad
const byte ROWS = 4, COLS = 4;
char keys[ROWS][COLS] = {
  {'1', '2', '3', 'A'},
  {'4', '5', '6', 'B'},
  {'7', '8', '9', 'C'},
  {'*', '0', '#', 'D'}
};
byte rowPins[ROWS] = {12, 14, 27, 26};
byte colPins[COLS] = {25, 33, 32, 35};
Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);

// RFID
#define SS_PIN 5
#define RST_PIN 34
MFRC522 mfrc522(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// Wi-Fi
const char* ssid = "RILLRITA_2.4G";
const char* password = "thefifth";

// Server and LED
WebServer server(80);
const char* serverUrl = "http://192.168.1.46/logins/process_dispense_esp.php";
const int ledPin = 2;

// States
enum State { WAITING, INTERACTING, SUBMENU };
State currentState = WAITING;
String currentCardID = "";

// UART
#define TXD1 17
#define RXD1 16
HardwareSerial mySerial(1);
int servo1Angle = 0, servo2Angle = 0;

/************ Setup Function *********************/
void setup() {
  Serial.begin(115200);
  pinMode(ledPin, OUTPUT);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) delay(500);
  Serial.println("Wi-Fi connected! IP: " + WiFi.localIP().toString());

  server.on("/dispense", HTTP_POST, handleDispense);
  server.on("/status", []() { server.send(200, "text/plain", "enabled"); });
  server.begin();

  mySerial.begin(9600, SERIAL_8N1, RXD1, TXD1);
  lcd.init();
  lcd.backlight();
  lcd.print("Initializing...");
  SPI.begin();
  mfrc522.PCD_Init();
  for (byte i = 0; i < 6; i++) key.keyByte[i] = 0xFF;

  performHttpPost();
}

/************ Main Loop *********************/
void loop() {
  server.handleClient();
  switch (currentState) {
    case WAITING: handleWaitingState(); break;
    case INTERACTING: handleInteractingState(); break;
    case SUBMENU: handleSubmenuState(); break;
  }
}

/************ State Handlers *********************/
void handleWaitingState() {
  // Check if a card is present and read its serial number
  if (mfrc522.PICC_IsNewCardPresent()) {
    // Read the card's serial number
    if (mfrc522.PICC_ReadCardSerial()) {
      String cardID = getCardID(); // Get the card ID

      // If the card ID is different, process the card
      if (cardID != currentCardID) {
        currentCardID = cardID; // Store the new card ID

        // Perform the action for the card
        showMessage("Card Detected", "ID: " + cardID);
        delay(5000);
        // You can add your custom behavior here, like state transitions
        // For example:
        currentState = INTERACTING; // Move to interacting state
        showMessage("Hello!", "1: Info 2: Pills");
      }
    }
  } else {
    showMessage("Scan your card", "Waiting...");
  }

  delay(500); // Add a small delay to prevent spamming the LCD
}

void handleInteractingState() {
  char key = keypad.getKey();
  if (!key) return;

  switch (key) {
    case '1':
      displayCardInfo();  // แสดงข้อมูลบัตร
      break;
    case '2':
      displayPillsMenu();  // ไปที่เมนูจ่ายยา
      break;
    case '3':
      resetToWaiting();  // กลับไปที่ WAITING
      break;
  }
}

void handleSubmenuState() {
  char key = keypad.getKey();
  if (key == '#') {
    currentState = INTERACTING;
    showMessage("Welcome Back", "1: Info 2: Pills");
  }
}

/************ Helper Functions *********************/
void showMessage(String line1, String line2) {
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print(line1);
  lcd.setCursor(0, 1); lcd.print(line2);
}

String getCardID() {
  String cardID = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    cardID += String(mfrc522.uid.uidByte[i], HEX);
  }
  cardID.toUpperCase(); // Modify in place
  return cardID;        // Return the modified string
}

bool authenticateCard() {
  byte buffer[18], len = sizeof(buffer);
  if (mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A, 4, &key, &(mfrc522.uid)) != MFRC522::STATUS_OK) return false;
  return mfrc522.MIFARE_Read(4, buffer, &len) == MFRC522::STATUS_OK;
}

void displayCardInfo() {
  showMessage("Card Info", currentCardID);
  currentState = SUBMENU;
  resetToWaiting();
}

void displayPillsMenu() {
  showMessage("1: Headache", "2: Else");
  char subKey = '\0';  // ตัวแปรเก็บค่าปุ่มที่กด

  while (true) {
    subKey = keypad.getKey();  // รอให้ผู้ใช้กดปุ่ม
    if (subKey == '1') {
      dispensePills(1);  // กด 1 เพื่อจ่ายยา
      break;  // ออกจาก loop หลังจากทำงานเสร็จ
    } else if (subKey == '2') {
      dispensePills(2);  // กด 2 เพื่อจ่ายยา
      break;  // ออกจาก loop หลังจากทำงานเสร็จ
    }
  }
}

void dispensePills(int servoID) {
  lcd.clear();
  showMessage("Dispensing", "Please wait...");
  delay(2000);  // แสดงข้อความให้ผู้ใช้เห็น

  // ทำงานแสดงผลหรือสั่งจ่ายยา (คุณสามารถเพิ่มโค้ดควบคุมเซอร์โวที่นี่)
  blinkLED(1);  // กระพริบ LED เพื่อแสดงสถานะ
  delay(1000);  // รอเพิ่มเติมเพื่อความชัดเจน

  server.send(400, "text/plain", "Dispense SUCCESS");
  delay(500);

  performHttpPost();
  delay(500);

  // หลังจากจ่ายยาเสร็จ
  showMessage("Dispense Done", "Returning...");
  delay(2000);  // แสดงข้อความให้ผู้ใช้เห็น

  resetToWaiting();  // กลับไปที่ WAITING state
}

void resetToWaiting() {
  currentCardID = "";
  currentState = WAITING;
  showMessage("Goodbye!", "Scan your card");
}

void sendServoCommand(int servoID, int angle) {
  if (servoID == 1) servo1Angle = angle;
  else if (servoID == 2) servo2Angle = angle;
  String message = String(servo1Angle) + "," + String(servo2Angle);
  mySerial.println(message);
}

void handleDispense() {
  // Check if required arguments exist
  if (!server.hasArg("medication_id") || !server.hasArg("patient_id")) {
    server.send(400, "text/plain", "Missing arguments");
    return;
  }

  String medicationIDs = server.arg("medication_id"); // e.g., "1,2,3"
  int patientID = server.arg("patient_id").toInt();

  if (patientID <= 0) {
    server.send(400, "text/plain", "Invalid patient_id");
    return;
  }

  // Parse the medication IDs into an array
  int medicationArray[8]; // Adjust size as needed
  int count = parseMedicationIDs(medicationIDs, medicationArray, 8);

  if (count == 0) {
    server.send(400, "text/plain", "Invalid medication_id format");
    return;
  }

  // Process each medication ID
  for (int i = 0; i < count; i++) {
    int medicationID = medicationArray[i];
    // Implement dispensing logic for each medication ID
    Serial.println("Dispensing medication ID: " + String(medicationID));
    blinkLED(1); // Optional: Visual feedback for each ID
  }

  // Send success response
  server.send(200, "text/plain", "Dispense SUCCESS for multiple IDs");
  performHttpPost();
}

void blinkLED(int count) {
  for (int i = 0; i < count; i++) {
    digitalWrite(ledPin, HIGH);
    delay(300);
    digitalWrite(ledPin, LOW);
    delay(300);
  }
}

int parseMedicationIDs(String medicationIDs, int outputArray[], int maxSize) {
  int count = 0;
  int start = 0;
  int commaIndex;

  while ((commaIndex = medicationIDs.indexOf(',', start)) != -1) {
    if (count >= maxSize) break; // Prevent overflow
    outputArray[count++] = medicationIDs.substring(start, commaIndex).toInt();
    start = commaIndex + 1;
  }

  // Add the last (or only) element
  if (count < maxSize && start < medicationIDs.length()) {
    outputArray[count++] = medicationIDs.substring(start).toInt();
  }

  return count; // Return the number of elements parsed
}

void performHttpPost() {
    HTTPClient http;

    // Start HTTP POST request
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json"); // Set Content-Type to JSON

    // Prepare JSON payload
    DynamicJsonDocument doc(1024);

    // Add JSON fields
    doc["patient_id"] = 1;
    JsonArray medications = doc.createNestedArray("medications");

    JsonObject med1 = medications.createNestedObject();
    med1["medication_id"] = 2;
    med1["dosage"] = 10;

    JsonObject med2 = medications.createNestedObject();
    med2["medication_id"] = 3;
    med2["dosage"] = 20;

    // Serialize JSON to String
    String postData;
    serializeJson(doc, postData);

    // Debug: Print JSON payload
    Serial.println("POST Data:");
    Serial.println(postData);

    // Send POST request
    int httpResponseCode = http.POST(postData);

    // Check HTTP response code
    if (httpResponseCode > 0) {
      String response = http.getString(); // Get the response to the request
      Serial.println("HTTP Response Code: " + String(httpResponseCode));
      Serial.println("Response: " + response);
    } else {
      Serial.println("Error on HTTP request");
      Serial.println("HTTP Error Code: " + String(httpResponseCode));
    }

    // Close HTTP connection
    http.end();
  }
