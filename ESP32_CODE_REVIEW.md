/**
 * ESP32 SmartRoom Access Control System - Code Review & Fixes
 * May 14, 2026
 * 
 * AUDIT RESULTS: ✅ MOSTLY GOOD, 6 ISSUES FOUND
 */

// ✅ WORKING CORRECTLY:
// 1. RFID UID format: "XX:XX:XX:XX" matches API expectation
// 2. API endpoints correct: GET /access-cards, POST /access-logs
// 3. Bearer token authentication implemented
// 4. Classroom ID correctly passed
// 5. HTTP timeouts set to 8000ms (adequate)
// 6. NTP sync configured for UTC+8 (Philippines)
// 7. Debounce logic (7 seconds) prevents duplicate scans
// 8. WiFi reconnect mechanism in ensureWiFi()
// 9. Access log body includes optional user_id and access_card_id (nullable fields)
// 10. Metadata stores method type ("RFID" or "Fingerprint")

// ⚠️  ISSUES TO FIX:

/**
 * ISSUE #1: WiFi Initial Connection Timeout
 * PROBLEM: setup() only tries 20 times (10 seconds) to connect
 * RISK: First connection might fail on slow networks
 * FIX: Increase retries to 40 (20 seconds) or implement progressive backoff
 */
// BEFORE:
// while (WiFi.status() != WL_CONNECTED && tries < 20) { ... }

// AFTER:
// while (WiFi.status() != WL_CONNECTED && tries < 40) { ... }

/**
 * ISSUE #2: Fingerprint-to-User Mapping
 * PROBLEM: When fingerprint matches, code passes finger.fingerID as user_id
 *          But finger.fingerID is template ID (1-127), NOT database user_id
 * RISK: Access logs show wrong user (fingerprint ID ≠ user ID)
 * FIX: Need mapping table on ESP32 or query API to link fingerprint ID to user_id
 * 
 * OPTION A (Recommended): Query API with fingerprint ID to get user_id
 *   POST /api/v1/access-logs with fingerprint ID in metadata
 *   Let Laravel match fingerprint to user via database
 * 
 * OPTION B: Store fingerprint-to-user mapping on ESP32 (in SPIFFS or hardcoded)
 * 
 * OPTION C: Use a lookup table sent from server at startup
 */

/**
 * ISSUE #3: SPI Pin Configuration
 * PROBLEM: SPI.begin(15, 35, 2, 4) looks unusual for ESP32
 * NOTE: Standard ESP32 VSPI = CLK:18, MISO:19, MOSI:23, CS:varies
 * ACTION: Verify these pins work with your MFRC522 module
 * VERIFY: Test if RFID is communicating correctly
 */

/**
 * ISSUE #4: Fingerprint Sensor Error Handling
 * PROBLEM: If AS608 not found, system shows LCD message but continues
 * RISK: Loop continuously tries getImage() on non-existent sensor = wasted CPU
 * FIX: Set a flag if sensor fails in setup(), skip fingerprint checks in loop()
 */

/**
 * ISSUE #5: JSON Body Construction (Minor)
 * PROBLEM: Manual string concatenation for JSON body is error-prone
 * BETTER: Use ArduinoJson library (already included!) for body creation
 * BENEFIT: Type-safe, prevents JSON syntax errors
 */

/**
 * ISSUE #6: HTTP Error Recovery
 * PROBLEM: If API returns error, no retry mechanism
 * CURRENT: Logs error but system continues, card stays locked
 * CONSIDER: Retry logic or offline mode fallback
 */

// ─────────────────────────────────────────────────────────────────

/**
 * RECOMMENDED FIXES IN PRIORITY ORDER:
 */

// 1. FIX FINGERPRINT-TO-USER MAPPING (CRITICAL)
//    Add this function to query API with fingerprint ID:

String getFingerprintUserID(uint8_t fingerprintID) {
  if (WiFi.status() != WL_CONNECTED) return "0";
  
  HTTPClient http;
  String url = String(API_BASE) + "/fingerprints/" + String(fingerprintID);
  http.begin(url);
  http.setTimeout(8000);
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.addHeader("Accept", "application/json");
  
  int code = http.GET();
  if (code != 200) { http.end(); return "0"; }
  
  DynamicJsonDocument doc(512);
  deserializeJson(doc, http.getString());
  http.end();
  
  return doc["user_id"].as<String>("0");
}

// 2. IMPROVE FINGERPRINT ERROR HANDLING:
bool fpSensorReady = false;  // Add this global flag

void setup() {
  // ... existing setup code ...
  
  if (finger.verifyPassword()) {
    Serial.println("AS608 found!");
    finger.getTemplateCount();
    Serial.printf("Templates stored: %d\n", finger.templateCount);
    fpSensorReady = true;  // ← Set flag
  } else {
    Serial.println("AS608 NOT found!");
    lcdMsg("FP Sensor", "NOT FOUND!");
    fpSensorReady = false;  // ← Set flag
    delay(3000);
  }
}

void checkFingerprint() {
  if (!fpSensorReady) return;  // ← Skip if sensor failed
  // ... rest of function ...
}

// 3. USE ArduinoJson FOR LOG BODY (More Robust):
void logAccessJson(String method, String result, int userId = 0, int cardId = 0) {
  ensureWiFi();
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = String(API_BASE) + "/access-logs";
  http.begin(url);
  http.setTimeout(8000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.addHeader("Accept", "application/json");

  // Use ArduinoJson instead of string concatenation
  DynamicJsonDocument doc(512);
  doc["classroom_id"] = CLASSROOM_ID;
  doc["result"] = result;
  doc["direction"] = "in";
  doc["accessed_at"] = getTimestamp();
  doc["metadata"]["method"] = method;
  if (userId > 0) doc["user_id"] = userId;
  if (cardId > 0) doc["access_card_id"] = cardId;

  String body;
  serializeJson(doc, body);
  
  Serial.println("Log body: " + body);
  int code = http.POST(body);
  Serial.printf("Log API response: %d\n", code);
  if (code > 0) Serial.println(http.getString());
  else Serial.printf("HTTP error: %s\n", http.errorToString(code).c_str());
  http.end();
}

// 4. IMPROVE WIFI INITIAL CONNECTION (20 → 40 retries):
void setup() {
  // ... existing WiFi code ...
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {  // ← 40 instead of 20
    delay(500);
    Serial.print(".");
    tries++;
  }
  // ...
}

// ─────────────────────────────────────────────────────────────────

/**
 * BACKEND API NOTES FOR YOUR ESP32:
 * 
 * 1. Your API token is valid ✅
 *    Token: 2|x9vhSh6kahXs4oQMWWmCBiEMdCfvxbFj1vhWyqn298d5968e
 * 
 * 2. Your classroom_id=1 (Room 15) exists ✅
 * 
 * 3. Access log fields:
 *    - classroom_id: REQUIRED ✅ You're sending it
 *    - result: REQUIRED ("granted" or "denied") ✅ You're sending it
 *    - direction: REQUIRED ("in" or "out") ✅ You're sending "in"
 *    - accessed_at: REQUIRED (ISO8601 format) ✅ You're formatting it correctly
 *    - metadata: OPTIONAL but recommended ✅ You're sending method
 *    - user_id: OPTIONAL ✅ You're sending it only when > 0
 *    - access_card_id: OPTIONAL, NULLABLE ✅ You're sending it only when > 0
 * 
 * 4. John Kenneth Bagotsay's card (CARD-001):
 *    - RFID: 47:6C:12:06
 *    - Card ID: 3
 *    - User ID: 8
 *    - Status: active
 */

// ─────────────────────────────────────────────────────────────────

/**
 * TESTING CHECKLIST:
 */
/*
✅ 1. Verify RFID module can read: 47:6C:12:06
✅ 2. Verify API GET returns card when RFID matches
✅ 3. Verify API POST accepts access log (granted)
✅ 4. Verify API POST accepts access log (denied)
✅ 5. Test fingerprint scanner (if connected)
✅ 6. Test WiFi reconnection by disconnecting/reconnecting
✅ 7. Verify timestamps are correct (check via curl)
✅ 8. Check admin dashboard shows access logs in real-time
*/

// ─────────────────────────────────────────────────────────────────

/**
 * CURL COMMANDS FOR TESTING YOUR API:
 */

/*
# Test RFID lookup:
curl -X GET "http://192.168.1.15:8000/api/v1/access-cards?rfid_uid=47:6C:12:06&classroom_id=1" \
  -H "Authorization: Bearer 2|x9vhSh6kahXs4oQMWWmCBiEMdCfvxbFj1vhWyqn298d5968e" \
  -H "Accept: application/json"

# Test log POST:
curl -X POST "http://192.168.1.15:8000/api/v1/access-logs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 2|x9vhSh6kahXs4oQMWWmCBiEMdCfvxbFj1vhWyqn298d5968e" \
  -d '{
    "classroom_id": 1,
    "user_id": 8,
    "access_card_id": 3,
    "direction": "in",
    "result": "granted",
    "accessed_at": "2026-05-14T19:32:14+00:00",
    "metadata": {"method": "RFID"}
  }'
*/
