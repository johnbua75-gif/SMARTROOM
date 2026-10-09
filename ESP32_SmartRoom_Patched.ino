#include "ESP32_CONFIG.h"

#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Preferences.h>

// ─── Config ───────────────────────────────────────────────
MFRC522 rfid(SS_PIN, RST_PIN);
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClientSecure httpsClient;

unsigned long rfidLastResetMs = 0;
int rfidFailCount = 0;
unsigned long lastHeartbeatMs = 0;
unsigned long lastWiFiRetryMs = 0;
unsigned long lastQueuedUploadMs = 0;
bool ntpConfigured = false;
Preferences offlineLogPreferences;
const uint8_t OFFLINE_LOG_CAPACITY = 30;

struct ScheduleCheckResult {
  bool allowed;
  bool systemError;
  String reason;
};

bool beginApiRequest(HTTPClient &http, const String &url) {
  if (url.startsWith("https://")) {
    if (strlen(API_ROOT_CA) == 0) return false;
    httpsClient.setCACert(API_ROOT_CA);
    return http.begin(httpsClient, url);
  }

  return url.startsWith("http://")
      && ALLOW_INSECURE_HTTP_FOR_LOCAL_TESTING
      && http.begin(url);
}

// ─── HELPERS ────────────────────────────────────────────────────────────────

void buzzOnce()   { digitalWrite(BUZZER_PIN, LOW); delay(200);  digitalWrite(BUZZER_PIN, HIGH); }
void buzzDenied() { digitalWrite(BUZZER_PIN, LOW); delay(800);  digitalWrite(BUZZER_PIN, HIGH);
                    delay(200); digitalWrite(BUZZER_PIN, LOW); delay(400); digitalWrite(BUZZER_PIN, HIGH); }

void lcdMsg(const char* line1, const char* line2 = "") {
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print(line1);
  if (line2[0]) { lcd.setCursor(0, 1); lcd.print(line2); }
}

void unlockDoor() {
  digitalWrite(RELAY_PIN, HIGH);
  delay(5000);
  digitalWrite(RELAY_PIN, LOW);
}

void ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    if (!ntpConfigured) {
      configTime(0, 0, "pool.ntp.org");
      ntpConfigured = true;
    }
    return;
  }

  if (lastWiFiRetryMs != 0 && millis() - lastWiFiRetryMs < 10000) return;
  lastWiFiRetryMs = millis();
  Serial.println("WiFi lost, requesting reconnect...");
  WiFi.disconnect(false);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
}

String getTimestamp() {
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo, 100)) return "";
  char buf[30];
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%SZ", &timeinfo);
  return String(buf);
}

String offlineLogKey(uint8_t index) {
  char key[5];
  snprintf(key, sizeof(key), "e%02u", index);
  return String(key);
}

void queueDeniedAccess(String method, int userId, int cardId, String reason, String rfidUid) {
  if (!offlineLogPreferences.isKey("count")) {
    offlineLogPreferences.putUChar("head", 0);
    offlineLogPreferences.putUChar("count", 0);
  }

  uint8_t head = offlineLogPreferences.getUChar("head", 0);
  uint8_t count = offlineLogPreferences.getUChar("count", 0);
  uint8_t slot;
  if (count >= OFFLINE_LOG_CAPACITY) {
    slot = head;
    head = (head + 1) % OFFLINE_LOG_CAPACITY;
    offlineLogPreferences.putUChar("head", head);
    Serial.println("Offline access-log queue full; dropped oldest entry");
  } else {
    slot = (head + count) % OFFLINE_LOG_CAPACITY;
    count++;
    offlineLogPreferences.putUChar("count", count);
  }

  DynamicJsonDocument entry(512);
  String timestamp = getTimestamp();
  if (timestamp.length() > 0) entry["accessed_at"] = timestamp;
  entry["method"] = method;
  entry["result"] = "denied";
  entry["direction"] = "entry";
  entry["rfid_uid"] = rfidUid;
  entry["user_id"] = userId > 0 ? userId : 0;
  entry["access_card_id"] = cardId > 0 ? cardId : 0;
  entry["reason"] = reason;
  entry["timestamp_missing"] = timestamp.length() == 0;

  String serialized;
  serializeJson(entry, serialized);
  offlineLogPreferences.putString(offlineLogKey(slot).c_str(), serialized);
  Serial.printf("Queued denied access log in flash (%u/%u)\n", count, OFFLINE_LOG_CAPACITY);
}

bool uploadNextQueuedDeniedAccess() {
  if (WiFi.status() != WL_CONNECTED || millis() - lastQueuedUploadMs < 5000) return false;
  lastQueuedUploadMs = millis();

  uint8_t head = offlineLogPreferences.getUChar("head", 0);
  uint8_t count = offlineLogPreferences.getUChar("count", 0);
  if (count == 0) return false;

  String key = offlineLogKey(head);
  String storedEntry = offlineLogPreferences.getString(key.c_str(), "");
  DynamicJsonDocument entry(768);
  if (storedEntry.length() == 0 || deserializeJson(entry, storedEntry)) {
    Serial.println("Offline access-log queue entry is invalid; retrying later");
    return false;
  }

  DynamicJsonDocument payload(768);
  payload["result"] = "denied";
  payload["direction"] = "entry";
  payload["metadata"]["method"] = entry["method"] | "RFID";
  payload["metadata"]["rfid_uid"] = entry["rfid_uid"] | "";
  int userId = entry["user_id"] | 0;
  int cardId = entry["access_card_id"] | 0;
  if (userId > 0) payload["user_id"] = userId;
  if (cardId > 0) payload["access_card_id"] = cardId;

  String reason = entry["reason"] | "Access denied";
  String timestamp = entry["accessed_at"] | "";
  if (timestamp.length() == 0) {
    timestamp = getTimestamp();
    if (timestamp.length() == 0) return false;
    reason += " (timestamp set on upload; original time unavailable)";
  }
  payload["accessed_at"] = timestamp;
  payload["reason"] = reason;

  String body;
  serializeJson(payload, body);
  HTTPClient http;
  if (!beginApiRequest(http, String(API_BASE) + "/device/access-logs")) return false;
  http.setTimeout(2500);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + DEVICE_CREDENTIAL);
  http.addHeader("Accept", "application/json");
  int code = http.POST(body);
  Serial.printf("Queued denied log upload response: %d\n", code);
  if (code > 0) Serial.println(http.getString());
  http.end();

  if (code != 201) return false;

  offlineLogPreferences.remove(key.c_str());
  head = (head + 1) % OFFLINE_LOG_CAPACITY;
  count--;
  offlineLogPreferences.putUChar("head", head);
  offlineLogPreferences.putUChar("count", count);
  Serial.printf("Uploaded queued denied log; %u remain\n", count);
  return true;
}

String urlEncode(String value) {
  String encoded;
  const char* characters = value.c_str();

  while (*characters) {
    char character = *characters++;
    if (isalnum(static_cast<unsigned char>(character)) || character == '-' || character == '_' || character == '.' || character == '~') {
      encoded += character;
    } else {
      char escape[4];
      snprintf(escape, sizeof(escape), "%%%02X", static_cast<unsigned char>(character));
      encoded += escape;
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

// ─── RFID WATCHDOG ───────────────────────────────────────────────────────────

void resetRFIDReader() {
  Serial.println("Resetting RFID reader...");
  rfid.PCD_Reset(); delay(100);
  rfid.PCD_Init();  delay(100);
  byte v = rfid.PCD_ReadRegister(MFRC522::VersionReg);
  if (v == 0x00 || v == 0xFF) Serial.println("RFID reset FAILED - check wiring");
  else Serial.printf("RFID reset OK (version: 0x%02X)\n", v);
  rfidFailCount    = 0;
  rfidLastResetMs  = millis();
}

// ─── API: LOG ACCESS ─────────────────────────────────────────────────────────

bool logAccess(String method, String result, int userId = 0, int cardId = 0, String reason = "", String rfidUid = "") {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) { Serial.println("No WiFi - access log failed"); return false; }
  String timestamp = getTimestamp();
  if (timestamp.length() == 0) { Serial.println("Clock not synchronized - access log failed"); return false; }

  DynamicJsonDocument doc(512);
  doc["result"]             = result;
  doc["direction"]          = "entry";
  doc["accessed_at"]        = timestamp;
  doc["metadata"]["method"] = method;
  if (reason.length() > 0) doc["reason"] = reason;
  if (rfidUid.length() > 0) doc["metadata"]["rfid_uid"] = rfidUid;
  if (userId > 0) doc["user_id"]        = userId;
  if (cardId > 0) doc["access_card_id"] = cardId;
  String body; serializeJson(doc, body);
  Serial.println("Log body: " + body);
  int maxAttempts = result == "granted" ? 3 : 1;
  for (int attempt = 1; attempt <= maxAttempts; attempt++) {
    HTTPClient http;
    if (!beginApiRequest(http, String(API_BASE) + "/device/access-logs")) return false;
    http.setTimeout(2500);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", String("Bearer ") + DEVICE_CREDENTIAL);
    http.addHeader("Accept", "application/json");
    int code = http.POST(body);
    Serial.printf("Log response (attempt %d): %d\n", attempt, code);
    if (code >= 200 && code < 300) {
      if (result != "granted") { http.end(); return true; }

      DynamicJsonDocument responseDoc(1024);
      DeserializationError responseError = deserializeJson(responseDoc, http.getString());
      bool grantRecorded = !responseError && responseDoc["data"]["result"].as<String>() == "granted";
      http.end();
      return grantRecorded;
    }
    if (code > 0) Serial.println(http.getString());
    if (code >= 400 && code < 500) { http.end(); return false; }
    http.end();
    delay(300 * attempt);
  }

  return false;
}

// ─── Check RFID against API ───────────────────────────────
bool checkRFIDApi(String uid, int &userId, int &cardId, String &reason) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) {
    reason = "Could not verify card: Wi-Fi unavailable";
    Serial.println(reason);
    return false;
  }

  HTTPClient http;
  String url = String(API_BASE) + "/device/access-cards?rfid_uid=" + urlEncode(uid);
  Serial.println("Checking card: " + url);
  if (!beginApiRequest(http, url)) {
    reason = "Could not verify card: request unavailable";
    Serial.println(reason);
    return false;
  }
  http.setTimeout(3500);
  http.addHeader("Authorization", String("Bearer ") + DEVICE_CREDENTIAL);
  http.addHeader("Accept", "application/json");

  int code = http.GET();
  Serial.printf("RFID API response: %d\n", code);
  if (code != 200) {
    String responseBody = code > 0 ? http.getString() : "";
    reason = "Could not verify card: HTTP "+String(code);
    Serial.println(reason);
    if (responseBody.length() > 0) Serial.println(responseBody);
    http.end();
    return false;
  }

  String payload = http.getString();
  Serial.println(payload);
  http.end();

  DynamicJsonDocument doc(8192);
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    reason = "Could not parse card access response";
    Serial.printf("%s: %s\n", reason.c_str(), err.c_str());
    return false;
  }

  JsonArray data = doc["data"].as<JsonArray>();
  if (!data.size()) {
    reason = "Access card not recognized";
    Serial.println(reason);
    return false;
  }

  JsonObject card = data[0].as<JsonObject>();
  userId = card["user_id"].as<int>();
  cardId = card["id"].as<int>();
  if (card["status"].as<String>() != "active") {
    reason = "Access card inactive or expired";
    Serial.println(reason);
    return false;
  }
  reason = "";
  return true;
}

// ─── Check Schedule against API ───────────────────────────
ScheduleCheckResult checkSchedule(int userId, String rfidUid) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) {
    String reason = "Could not verify access: Wi-Fi unavailable";
    Serial.println(reason);
    return {false, true, reason};
  }

  HTTPClient http;
  String url = String(API_BASE) + "/device/reservations/check"
               + "?user_id=" + String(userId);
  url += "&rfid_uid=" + urlEncode(rfidUid);
  Serial.println("Checking schedule: " + url);
  if (!beginApiRequest(http, url)) {
    String reason = "Could not verify access: request unavailable";
    Serial.println(reason);
    return {false, true, reason};
  }
  http.setTimeout(3500);
  http.addHeader("Authorization", String("Bearer ") + DEVICE_CREDENTIAL);
  http.addHeader("Accept", "application/json");

  int code = http.GET();
  Serial.printf("Schedule API response: %d\n", code);

  if (code != 200) {
    String responseBody = code > 0 ? http.getString() : "";
    String reason = "Schedule API HTTP error "+String(code);
    Serial.println(reason);
    if (responseBody.length() > 0) Serial.println(responseBody);
    http.end();
    return {false, true, reason};
  }

  String payload = http.getString();
  Serial.println(payload);
  http.end();

  DynamicJsonDocument doc(4096);
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    String reason = "Schedule API response could not be parsed";
    Serial.printf("%s: %s\n", reason.c_str(), err.c_str());
    return {false, true, reason};
  }

  if (!doc["allowed"].is<bool>()) {
    String reason = "Schedule API response has no allowed value";
    Serial.println(reason);
    return {false, true, reason};
  }

  bool allowed = doc["allowed"].as<bool>();
  String reason = doc["reason"] | doc["message"] | (allowed ? "Access granted" : "Access denied");
  Serial.printf("Schedule allowed: %s\n", allowed ? "YES" : "NO");
  Serial.printf("Schedule API reason: %s\n", reason.c_str());
  return {allowed, false, reason};
}

// ─── Access results ───────────────────────────────────────
String shortAccessReason(String reason) {
  String normalized = reason;
  normalized.toLowerCase();

  if (normalized.indexOf("inactive") >= 0 || normalized.indexOf("expired card") >= 0) return "Card Inactive";
  if (normalized.indexOf("not recognized") >= 0 || normalized.indexOf("unknown card") >= 0 || normalized.indexOf("wrong card") >= 0) return "Unknown Card";
  if (normalized.indexOf("pending") >= 0 || normalized.indexOf("not approved") >= 0) return "Not Approved";
  if (normalized.indexOf("reservation") >= 0 && normalized.indexOf("expired") >= 0) return "Reservation Over";
  if (normalized.indexOf("not started") >= 0 || normalized.indexOf("reserved for") >= 0 || normalized.indexOf("too early") >= 0) return "Too Early";
  if (normalized.indexOf("no schedule") >= 0 || normalized.indexOf("no reservation") >= 0 || normalized.indexOf("no active") >= 0) return "No Reservation";
  if (normalized.indexOf("system") >= 0 || normalized.indexOf("http") >= 0 || normalized.indexOf("timeout") >= 0 || normalized.indexOf("wifi") >= 0 || normalized.indexOf("api") >= 0 || normalized.indexOf("could not verify") >= 0 || normalized.indexOf("could not parse") >= 0) return "System Error";

  return "Access Denied";
}

void grantAccess(const char* method, int userId = 0, int cardId = 0) {
  Serial.printf("ACCESS GRANTED via %s\n", method);
  if (!logAccess(String(method), "granted", userId, cardId)) {
    lcdMsg("Access Blocked", "Log unavailable");
    buzzDenied();
    delay(2000);
    lcdMsg("Scan RFID card", "to begin");
    return;
  }
  lcdMsg("Access Granted!", ":) Welcome");
  buzzOnce();
  unlockDoor();
  lcdMsg("Scan RFID card", "to begin");
}

void denyAccess(String reason, const char* method = "unknown", int userId = 0, int cardId = 0, String rfidUid = "") {
  Serial.printf("ACCESS DENIED - %s\n", reason.c_str());
  String displayReason = shortAccessReason(reason);
  lcdMsg("Access Denied", displayReason.c_str());
  buzzDenied();
  if (!logAccess(String(method), "denied", userId, cardId, reason, rfidUid)) {
    queueDeniedAccess(String(method), userId, cardId, reason, rfidUid);
  }
  delay(2000);
  lcdMsg("Scan RFID card", "to begin");
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

  // Halt BEFORE API calls so reader doesn't re-trigger
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  int userId = 0, cardId = 0;

  // Step 1: Is this card registered and active?
  String cardFailureReason;
  if (!checkRFIDApi(uid, userId, cardId, cardFailureReason)) {
    denyAccess(cardFailureReason, "RFID", userId, cardId, uid);
    return;
  }

  // Step 2: Does this user have a schedule RIGHT NOW?
  lcdMsg("Checking", "Schedule...");
  ScheduleCheckResult decision = checkSchedule(userId, uid);
  if (!decision.allowed) {
    denyAccess(decision.reason, "RFID", userId, cardId, uid);
    return;
  }

  // Step 3: All good — unlock
  grantAccess("RFID", userId, cardId);
}

void sendDeviceHeartbeat() {
  if (lastHeartbeatMs != 0 && millis() - lastHeartbeatMs < 60000) return;
  if (WiFi.status() != WL_CONNECTED) return;
  lastHeartbeatMs = millis();

  HTTPClient http;
  if (!beginApiRequest(http, String(API_BASE) + "/device/heartbeat")) return;
  http.setTimeout(5000);
  http.addHeader("Authorization", String("Bearer ") + DEVICE_CREDENTIAL);
  http.addHeader("Accept", "application/json");
  int code = http.POST("");
  Serial.printf("Device heartbeat: %d\n", code);
  http.end();
}

// ─── Setup ────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  offlineLogPreferences.begin("door_logs", false);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, HIGH);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, LOW);

  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  lcdMsg("Connecting...");

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {
    delay(500);
    Serial.print(".");
    tries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connected: " + WiFi.localIP().toString());
    lcdMsg("WiFi OK!", WiFi.localIP().toString().c_str());
    // Use UTC; server expects +00:00 timestamps
    configTime(0, 0, "pool.ntp.org");
    ntpConfigured = true;
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

  lcdMsg("Scan RFID card", "to begin");
  Serial.println("System ready.");
}

// ─── Loop ─────────────────────────────────────────────────
void loop() {
  ensureWiFi();
  sendDeviceHeartbeat();
  checkRFID();
  uploadNextQueuedDeniedAccess();
  delay(50);
}
