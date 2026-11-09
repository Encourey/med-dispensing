#include <Wire.h>
#include <LiquidCrystal_I2C.h>   // เปิดใช้งาน LCD
#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <WebServer.h>

// Struct for known cards
struct MedicationSlot {
  String cardID;
  int slot;
  int medID;
  int patientID;      
  String medName;
};

// Replace with actual card IDs and medications
MedicationSlot knownCards[] = { 
  {"35A82303", 1, 1, 1, "Paracetamol"},
  {"0EF93900", 2, 2, 2, "Carbocisteine"},
  {"634EEF34", 3, 3, 3, "Amoxicillin"}
};

// LCD
LiquidCrystal_I2C lcd(0x27, 16, 2);  // ใช้ address 0x27, ขนาด 16x2

// RFID
#define SS_PIN 5
#define RST_PIN 34
MFRC522 mfrc522(SS_PIN, RST_PIN);

// WiFi
const char* ssid = "MedNetwork";
const char* password = "1234567890";

// Endpoints
const char* ESPB_URL = "http://192.168.4.186/servo";
const char* logUrl   = "http://192.168.4.167/logins/process_dispense_esp.php";

// Server for status
WebServer server(80);

String currentCardID = "";

// ---- Setup ----
void setup() {
  Serial.begin(115200);
  Wire.begin(21, 22);

  // LCD Init
  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0,0); lcd.print("Initializing...");

  // Connect to Wi-Fi with timeout
  WiFi.begin(ssid, password);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    delay(500);
    lcd.setCursor(0,1); lcd.print("WiFi...");
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
    lcd.clear();
    lcd.setCursor(0,0); lcd.print("WiFi Failed");
    Serial.println("WiFi connection failed.");
    while (true); // Halt
  }

  Serial.println("WiFi connected: " + WiFi.localIP().toString());
  lcd.clear();
  lcd.setCursor(0,0); lcd.print("WiFi OK");
  lcd.setCursor(0,1); lcd.print(WiFi.localIP().toString());
  delay(2000);

  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("สแกนบัตร RFID/Tag...");

  server.on("/status", []() {
    server.send(200, "text/plain", "enabled");
  });
  server.begin();

  resetToWaiting();  // show "Ready to scan"
}

// ---- Main Loop ----
void loop() {
  server.handleClient();
  handleCardScan();
}

// ---- Handle Card Scan ----
void handleCardScan() {
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String cardID = getUIDString(&mfrc522.uid);
    if (cardID != currentCardID) {
      currentCardID = cardID;
      Serial.println("Card detected: " + currentCardID);

      mfrc522.PICC_HaltA();
      mfrc522.PCD_StopCrypto1();

      processCard(cardID);
    }
  }
}

// ---- Convert RFID to String ----
String getUIDString(MFRC522::Uid *uid) {
  String uidStr = "";
  for (byte i = 0; i < uid->size; i++) {
    if (uid->uidByte[i] < 0x10) uidStr += "0";
    uidStr += String(uid->uidByte[i], HEX);
  }
  uidStr.toUpperCase();
  return uidStr;
}

// ---- Card Processing ----
void processCard(String cardID) {
  for (const auto& card : knownCards) {
    if (card.cardID == cardID) {
      showMessage("Card ID:", cardID);
      delay(2000);

      showMessage("Dispensing", card.medName);
      dispenseAndLog(card.slot, card.medID, 500, card.patientID);
      return;
    }
  }

  showMessage("Unknown Card", "Access Denied");
  delay(2000);
  resetToWaiting();
}

// ---- Send Request to ESPB and Log ----
void dispenseAndLog(int slot, int medID, int dosage, int patientID) {
  HTTPClient http;
  String url = String(ESPB_URL) + "?slot=" + String(slot);
  http.begin(url);

  int code = http.GET();
  if (code > 0) {
    Serial.println("Dispense response: " + http.getString());
    showMessage("Dispensed", "Returning...");
  } else {
    Serial.println("HTTP Error: " + String(code));
    showMessage("Error", "Dispense failed");
  }
  http.end();

  sendLog(patientID, medID, dosage);
  delay(2000);
  resetToWaiting();
}

// ---- Reset to Ready State ----
void resetToWaiting() {
  currentCardID = "";
  showMessage("Ready to scan", "Waiting...");
}

// ---- Show on LCD ----
void showMessage(String line1, String line2) {
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print(line1);
  lcd.setCursor(0, 1); lcd.print(line2);

  // Debug to Serial ด้วย
  Serial.println(line1 + " - " + line2);
}

// ---- Send Log to Server ----
void sendLog(int patient_id, int medication_id, int dosage) {
  HTTPClient http;
  http.begin(logUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> doc;
  doc["patient_id"] = patient_id;
  JsonArray meds = doc.createNestedArray("medications");
  JsonObject med = meds.createNestedObject();
  med["medication_id"] = medication_id;
  med["dosage"] = dosage;

  String jsonStr;
  serializeJson(doc, jsonStr);
  int responseCode = http.POST(jsonStr);

  if (responseCode > 0) {
    String response = http.getString();
    Serial.println("Log response: " + response);
  } else {
    Serial.printf("Log POST error: %d\n", responseCode);
  }

  http.end();
}
