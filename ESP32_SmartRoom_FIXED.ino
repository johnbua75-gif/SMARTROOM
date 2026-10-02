#include "ESP32_CONFIG.h"

#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_Fingerprint.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ─── Config ───────────────────────────────────────────────
MFRC522 rfid(SS_PIN, RST_PIN);
HardwareSerial fpSerial(2);
Adafruit_Fingerprint finger(&fpSerial);
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ─── State ────────────────────────────────────────────────
bool fpSensorReady = false;  // Track if fingerprint sensor is available

// ─── Buzzer ───────────────────────────────────────────────
void buzzOnce() {
  digitalWrite(BUZZER_PIN, LOW);
  delay(200);
  digitalWrite(BUZZER_PIN, HIGH);
}

void buzzDenied() {
  digitalWrite(BUZZER_PIN, LOW);
  delay(1000);
  digitalWrite(BUZZER_PIN, HIGH);
}

// ─── LCD ──────────────────────────────────────────────────
void lcdMsg(const char* line1, const char* line2 = "") {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1);
  if (line2[0]) {
    lcd.setCursor(0, 1);
    lcd.print(line2);
  }
}

// ─── Solenoid ─────────────────────────────────────────────
void unlockDoor() {
  digitalWrite(RELAY_PIN, HIGH);
  delay(5000);
  digitalWrite(RELAY_PIN, LOW);
}

// ─── WiFi Reconnect ───────────────────────────────────────
void ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;
  Serial.println("WiFi lost, reconnecting...");
  lcdMsg("Reconnecting...");
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {  // ✅ INCREASED: 20 → 40 retries
    delay(500);
    Serial.print(".");
    tries++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi reconnected: " + WiFi.localIP().toString());
  } else {
    Serial.println("\nWiFi still failed.");
  }
}

// ─── Timestamp (UTC) ──────────────────────────────────────
String getTimestamp() {
  time_t now = time(NULL);
  struct tm timeinfo;

  // Prefer gmtime_r when available, otherwise use gmtime() safely
  #if defined(__GNUC__)
    if (gmtime_r(&now, &timeinfo) == NULL) {
      return "";
    }
  #else
    struct tm *tmp = gmtime(&now);
    if (!tmp) return "";
    timeinfo = *tmp;
  #endif

  char buf[30];
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%S+00:00", &timeinfo);
  return String(buf);
}

// ─── URL encode helper ────────────────────────────────────
String urlEncode(const String &str) {
  String encoded = "";
  char c;
  char buf[4];
  const char* s = str.c_str();
  while ((c = *s++)) {
    if (isalnum((unsigned char)c) || c == '-' || c == '_' || c == '.' || c == '~') {
      encoded += c;
    } else {
      sprintf(buf, "%%%02X", (unsigned char)c);
      encoded += buf;
    }
  }
  return encoded;
}

String normalizeRfidUid(String value) {
  value.toUpperCase();
  value.replace("RFID-", "");
  value.replace("RFID_", "");
  value.replace(":", "");
  value.replace("-", "");
  value.replace(" ", "");
  return value;
}

// ─── Log to API (ArduinoJson version) ──────────────────────
bool logAccess(String method, String result, int userId = 0, int cardId = 0, String reason = "", String rfidUid = "") {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) { Serial.println("No WiFi - access log failed"); return false; }
  String timestamp = getTimestamp();
  if (timestamp.length() == 0) { Serial.println("Clock not synchronized - access log failed"); return false; }

  // ✅ FIXED: Use ArduinoJson for safer JSON construction
  DynamicJsonDocument doc(1024);
  doc["classroom_id"] = CLASSROOM_ID;
  doc["result"] = result;
  doc["direction"] = "entry";
  doc["accessed_at"] = timestamp;
  doc["metadata"]["method"] = method;
  if (reason.length() > 0) doc["reason"] = reason;
  if (rfidUid.length() > 0) doc["metadata"]["rfid_uid"] = rfidUid;
  if (userId > 0) doc["user_id"] = userId;
  if (cardId > 0) doc["access_card_id"] = cardId;

  String body;
  serializeJson(doc, body);

  Serial.println("Log body: " + body);
  for (int attempt = 1; attempt <= 3; attempt++) {
    HTTPClient http;
    http.begin(String(API_BASE) + "/access-logs");
    http.setTimeout(10000);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
    http.addHeader("Accept", "application/json");
    int code = http.POST(body);
    Serial.printf("Log API response (attempt %d): %d\n", attempt, code);
    if (code > 0) Serial.println(http.getString());
    if (code >= 200 && code < 300) { http.end(); return true; }
    if (code >= 400 && code < 500) { http.end(); return false; }
    http.end();
    delay(300 * attempt);
  }

  return false;
}

// ─── Check RFID against API ───────────────────────────────
bool checkRFIDApi(String uid, int &userId, int &cardId) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) return false;

  HTTPClient http;
  String url = String(API_BASE) + "/access-cards?rfid_uid=" + urlEncode(uid);
  Serial.println("Checking card: " + url);
  http.begin(url);
  http.setTimeout(10000);
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.addHeader("Accept", "application/json");

  int code = http.GET();
  Serial.printf("RFID API response: %d\n", code);
  if (code != 200) { 
    if (code > 0) Serial.println(http.getString());
    http.end(); 
    return false; 
  }

  String payload = http.getString();
  Serial.println(payload);
  http.end();

  DynamicJsonDocument doc(8192);
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.print("JSON parse error in checkRFIDApi: ");
    Serial.println(err.c_str());
    return false;
  }

  JsonArray data = doc["data"].as<JsonArray>();
  if (!data.size()) return false;

  // ✅ FIXED: Loop through results and verify rfid_uid matches exactly
  for (JsonObject card : data) {
    String cardUid = normalizeRfidUid(card["rfid_uid"].as<String>());
    String checkUid = normalizeRfidUid(uid);
    if (cardUid != checkUid) continue;

    String status = card["status"].as<String>();
    userId = card["user_id"].as<int>();
    cardId = card["id"].as<int>();
    if (status != "active") {
      Serial.println("Card found but not active, skipping");
      continue;
    }

    return true;
  }
  return false;
}

// ─── Check Schedule against API ───────────────────────────
bool checkSchedule(int userId, String rfidUid) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("No WiFi - cannot verify schedule");
    return false;
  }

  HTTPClient http;
  String url = String(API_BASE) + "/reservations/check"
               + "?user_id=" + String(userId)
               + "&classroom_id=" + String(CLASSROOM_ID);
  url += "&rfid_uid=" + urlEncode(rfidUid);
  Serial.println("Checking schedule: " + url);
  http.begin(url);
  http.setTimeout(10000);
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.addHeader("Accept", "application/json");

  int code = http.GET();
  Serial.printf("Schedule API response: %d\n", code);

  if (code != 200) {
    if (code > 0) Serial.println(http.getString());
    Serial.println("Schedule check failed - denying access");
    http.end();
    return false;
  }

  String payload = http.getString();
  Serial.println(payload);
  http.end();

  DynamicJsonDocument doc(4096);
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.print("JSON parse error in checkSchedule: ");
    Serial.println(err.c_str());
    return false;
  }

  bool allowed = false;
  if (doc.containsKey("allowed")) allowed = doc["allowed"].as<bool>();
  Serial.printf("Schedule allowed: %s\n", allowed ? "YES" : "NO");
  return allowed;
}

// ─── Access results ───────────────────────────────────────
void grantAccess(const char* method, int userId = 0, int cardId = 0) {
  Serial.printf("ACCESS GRANTED via %s\n", method);
  if (!logAccess(String(method), "granted", userId, cardId)) {
    lcdMsg("Access Blocked", "Log unavailable");
    buzzDenied();
    delay(2000);
    lcdMsg("Scan Card or", "Finger...");
    return;
  }
  lcdMsg("Access Granted!", ":) Welcome");
  buzzOnce();
  unlockDoor();
  lcdMsg("Scan Card or", "Finger...");
}

void denyAccess(const char* reason, const char* method = "unknown", int userId = 0, int cardId = 0, String rfidUid = "") {
  Serial.printf("ACCESS DENIED - %s\n", reason);
  lcdMsg("Access Denied!", reason);
  buzzDenied();
  logAccess(String(method), "denied", userId, cardId, String(reason), rfidUid);
  delay(2000);
  lcdMsg("Scan Card or", "Finger...");
}

// ─── RFID ─────────────────────────────────────────────────
String lastUID = "";
unsigned long lastScanMs = 0;

void checkRFID() {
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
    if (i < rfid.uid.size - 1) uid += ":";
  }
  uid.toUpperCase();

  // Debounce: ignore same card within 7 seconds
  if (uid == lastUID && (millis() - lastScanMs) < 7000) {
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    return;
  }
  lastUID = uid;
  lastScanMs = millis();

  Serial.println("Card UID: " + uid);
  lcdMsg("Checking...", uid.c_str());

  // Halt BEFORE API call so reader doesn't re-trigger during HTTP wait
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  int userId = 0, cardId = 0;

  // Step 1: Is this card registered and active?
  if (!checkRFIDApi(uid, userId, cardId)) {
    denyAccess("Wrong Card", "RFID", userId, cardId, uid);
    return;
  }

  // Step 2: Does this user have a schedule RIGHT NOW?
  lcdMsg("Checking", "Schedule...");
  if (!checkSchedule(userId, uid)) {
    denyAccess("No Schedule", "RFID", userId, cardId, uid);
    return;
  }

  // Step 3: All good — unlock
  grantAccess("RFID", userId, cardId);
}

// ─── Fingerprint ──────────────────────────────────────────
void checkFingerprint() {
  // ✅ FIXED: Skip if sensor not ready
  if (!fpSensorReady) return;

  uint8_t p = finger.getImage();
  if (p == FINGERPRINT_NOFINGER) return;
  if (p != FINGERPRINT_OK) return;

  Serial.println("Finger detected!");
  lcdMsg("Scanning...", "Hold still...");

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    lcdMsg("Read Error", "Try again");
    delay(1500);
    lcdMsg("Scan Card or", "Finger...");
    return;
  }

  p = finger.fingerSearch();
  if (p == FINGERPRINT_OK) {
    Serial.printf("FP match! Template ID #%d has no server identity mapping\n", finger.fingerID);
    denyAccess("FP Not Linked", "Fingerprint");

  } else if (p == FINGERPRINT_NOTFOUND) {
    denyAccess("Unknown Finger", "Fingerprint");
  } else {
    lcdMsg("Sensor Error", "Try again");
    delay(1500);
    lcdMsg("Scan Card or", "Finger...");
  }
  delay(500);
}

// ─── Setup ────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, HIGH);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  lcdMsg("Connecting...");

  // ✅ IMPROVED: Increased WiFi retries from 20 to 40 (20 seconds instead of 10)
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {  // ← 40 retries
    delay(500);
    Serial.print(".");
    tries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected: " + WiFi.localIP().toString());
    lcdMsg("WiFi OK!", WiFi.localIP().toString().c_str());
    // Use UTC; server expects +00:00 timestamps
    configTime(0, 0, "pool.ntp.org");
    Serial.println("Syncing NTP time...");
    struct tm timeinfo;
    if (getLocalTime(&timeinfo)) Serial.println("Time synced!");
    else Serial.println("NTP sync failed");
  } else {
    Serial.println("\nWiFi FAILED");
    lcdMsg("WiFi Failed!", "Check settings");
  }
  delay(1500);

  SPI.begin(15, 35, 2, 4);
  rfid.PCD_Init();
  Serial.println("RFID ready.");

  fpSerial.begin(57600, SERIAL_8N1, FP_RX_PIN, FP_TX_PIN);
  finger.begin(57600);

  // ✅ FIXED: Set flag to skip fingerprint checks if sensor not ready
  if (finger.verifyPassword()) {
    Serial.println("AS608 found!");
    finger.getTemplateCount();
    Serial.printf("Templates stored: %d\n", finger.templateCount);
    fpSensorReady = true;
  } else {
    Serial.println("AS608 NOT found!");
    lcdMsg("FP Sensor", "NOT FOUND!");
    fpSensorReady = false;  // Prevent loop from trying to use it
    delay(3000);
  }

  lcdMsg("Scan Card or", "Finger...");
  Serial.println("System ready.");
}

// ─── Loop ─────────────────────────────────────────────────
void loop() {
  checkRFID();
  checkFingerprint();
  delay(50);
}
