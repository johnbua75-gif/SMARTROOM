#pragma once

// Fill in this file before uploading ESP32_SmartRoom_Patched.ino.
// Keep this file private because it contains Wi-Fi and API credentials.

#define SS_PIN 4
#define RST_PIN 5
#define BUZZER_PIN 23
#define RELAY_PIN 26
#define FP_RX_PIN 16
#define FP_TX_PIN 17

const char* WIFI_SSID = "FILL_WIFI_SSID";
const char* WIFI_PASSWORD = "FILL_WIFI_PASSWORD";

// Use the computer IPv4 address on the same Wi-Fi network.
const char* API_BASE = "http://192.168.1.8:8000/api/v1";
const char* API_TOKEN = "FILL_ESP32_API_TOKEN";

// Room 15 is classroom ID 2 in the current database.
const int CLASSROOM_ID = 2;
