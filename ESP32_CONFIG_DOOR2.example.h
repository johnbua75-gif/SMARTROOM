#pragma once

// Room 16 Door 2 - copy into this board's sketch folder as ESP32_CONFIG.h.
// Keep the Wi-Fi password and device credential private.
#define SS_PIN 4
#define RST_PIN 5
#define BUZZER_PIN 23
#define RELAY_PIN 26
#define CLASSROOM_ID 41

const char* WIFI_SSID = "SET_GROUPMATE_WIFI_SSID";
const char* WIFI_PASSWORD = "SET_WIFI_PASSWORD";

// Replace with the laptop's Wi-Fi IPv4 address at the house.
const char* API_BASE = "http://SET_LAPTOP_WIFI_IPV4:8000/api/v1";
const char* API_ROOT_CA = "";
const bool ALLOW_INSECURE_HTTP_FOR_LOCAL_TESTING = true;
const char* DEVICE_CREDENTIAL = "PASTE_ROOM_16_DOOR_CREDENTIAL_HERE";
