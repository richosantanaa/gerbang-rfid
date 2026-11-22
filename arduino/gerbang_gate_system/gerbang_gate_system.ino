#include <SPI.h>
#include <MFRC522.h>
#include <Servo.h>

// PIN RFID RC522
#define SS_PIN 10
#define RST_PIN 9

// PIN KOMPONEN
#define SERVO_PIN 6
#define LED_HIJAU 4
#define LED_MERAH 5
#define BUZZER 7

// Waktu tunggu jawaban dari server (ms)
#define TIMEOUT_SERVER 4000

MFRC522 rfid(SS_PIN, RST_PIN);
Servo gateServo;

String bacaUID() {
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
    if (i < rfid.uid.size - 1) uid += " ";
  }
  uid.toUpperCase();
  return uid;
}

void aksesDiterima() {
  digitalWrite(LED_HIJAU, HIGH);
  digitalWrite(LED_MERAH, LOW);
  tone(BUZZER, 2000, 150);

  gateServo.write(90);
  delay(3000);
  gateServo.write(0);

  digitalWrite(LED_HIJAU, LOW);
}

void aksesDitolak() {
  gateServo.write(0);
  for (int i = 0; i < 3; i++) {
    digitalWrite(LED_MERAH, HIGH);
    tone(BUZZER, 1000);
    delay(200);
    noTone(BUZZER);
    digitalWrite(LED_MERAH, LOW);
    delay(200);
  }
}

void setup() {
  Serial.begin(9600);
  Serial.setTimeout(TIMEOUT_SERVER);

  SPI.begin();
  rfid.PCD_Init();

  gateServo.attach(SERVO_PIN);
  gateServo.write(0);

  pinMode(LED_HIJAU, OUTPUT);
  pinMode(LED_MERAH, OUTPUT);
  pinMode(BUZZER, OUTPUT);

  digitalWrite(LED_HIJAU, LOW);
  digitalWrite(LED_MERAH, LOW);
  digitalWrite(BUZZER, LOW);

  Serial.println("READY");
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String uid = bacaUID();

  // Kirim UID ke PC (diteruskan ke Laravel oleh bridge.py)
  Serial.print("UID:");
  Serial.println(uid);

  // Tunggu jawaban: "GRANTED|Nama|Jabatan" atau "DENIED"
  String jawaban = Serial.readStringUntil('\n');
  jawaban.trim();

  if (jawaban.startsWith("GRANTED")) {
    aksesDiterima();
  } else {
    // DENIED, atau server tidak menjawab (timeout) -> tolak demi keamanan
    aksesDitolak();
  }

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
  delay(500);
}
