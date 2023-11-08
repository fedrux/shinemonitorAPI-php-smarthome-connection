//set arduino ide ready to nodemcu v1.0

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>
#include <DHT.h>

//https://electrocircuit.net/tuya-iot-with-nodemcu/

const char* ssid = "wifi ssid";
const char* password = "pw wifi";
String url = "energia.php?passwordPagina=xxxxxx&device=nodemcu";  // Ad esempio, "http://example.com/data.json"

const size_t capacity = JSON_ARRAY_SIZE(50);
DynamicJsonDocument doc(capacity);

const int whiteLED = D0;   // Pin del LED bianco
const int blueLED = D1;    // Pin del LED blu
const int redLED = D2;     // Pin del LED rosso
const int yellowLED = D3;  // Pin del LED giallo
const int greenLED = D4;   // Pin del LED verde

#define DHTPIN D5
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

unsigned long previousMillisLEDRed = 0;
unsigned long previousMillisLEDGreen = 0;
unsigned long previousMillisLEDYellow = 0;
unsigned long previousMillisLEDBlue = 0;
unsigned long previousMillisLEDWhite = 0;

String statoUltimo = "";


short int led_green_val = 0;
short int led_yellow_val = 0;
short int led_red_val = 0;
short int led_blue_val = 0;
//percenntuale lampeggio
short int led_green_perc = 0;
short int led_yellow_perc = 0;
short int led_red_perc = 0;
short int led_blue_perc = 0;




/*
unsigned long int blinkLED(int pin, int percentage, int ledState, unsigned long& previousMillis) {
  if (ledState == 2) {
    unsigned long currentMillis = millis();
    long interval = map(percentage, 0, 100, 10, 1000);  // Mappa la percentuale in un intervallo tra 100ms e 1000ms
    if (currentMillis - previousMillis >= interval) {
      previousMillis = currentMillis;
      if (ledState == LOW) {
        ledState = HIGH;
      } else {
        ledState = LOW;
      }
    }
    digitalWrite(pin, ledState);
  }else{
    digitalWrite(pin, ledState);
  }
  return previousMillis;
}
*/
// Function to blink an LED at a specified interval
void blinkLED(int pin, int percentage, int ledState, unsigned long& previousMillis) {
  percentage = 100 - percentage;
  if (ledState == 2) {
    int interval = map(percentage, 0, 100, 10, 1000);
    unsigned long currentMillis = millis();
    if (currentMillis - previousMillis >= interval) {
      previousMillis = currentMillis;
      digitalWrite(pin, !digitalRead(pin));
    }
  } else {
    digitalWrite(pin, ledState);
  }
}



void setup() {
  pinMode(redLED, OUTPUT);
  pinMode(yellowLED, OUTPUT);
  pinMode(greenLED, OUTPUT);
  pinMode(blueLED, OUTPUT);
  pinMode(whiteLED, OUTPUT);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  Serial.begin(115200);

  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connessione WiFi in corso...");
  }

  Serial.println("Connessione WiFi riuscita!");
  digitalWrite(whiteLED, 1);
  digitalWrite(redLED, 1);
  digitalWrite(yellowLED, 1);
  digitalWrite(greenLED, 1);
  digitalWrite(blueLED, 1);
  delay(2000);
}


unsigned long previousMillisToLoop = 30000;  //parte subito almeno
void loop() {

  unsigned long currentMillisToLoop = millis();
  if ((currentMillisToLoop - previousMillisToLoop) >= 30000) {
    Serial.print(currentMillisToLoop - previousMillisToLoop);
    blinkLED(greenLED, 0, 0, previousMillisLEDGreen);
    blinkLED(yellowLED, 0, 0, previousMillisLEDYellow);
    blinkLED(redLED, 0, 0, previousMillisLEDRed);
    blinkLED(blueLED, 0, 0, previousMillisLEDBlue);
    blinkLED(whiteLED, 0, 1, previousMillisLEDWhite);

    if (WiFi.status() == WL_CONNECTED) {
      Serial.print(".");
      std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
      client->setInsecure();
      HTTPClient https;

      String temperature = (String)dht.readTemperature();
      String humidity = (String)dht.readHumidity();
      Serial.print("hum: ");
      Serial.println(dht.readHumidity());

      url = "energia.php?passwordPagina=xxxxxx&device=nodemcu&statoUltimo=" + statoUltimo + "&temp=" + temperature + "&hum=" + humidity;  // Ad esempio, "http://example.com/data.json"
      Serial.println(url);

      if (https.begin(*client, url)) {  // HTTPS
        Serial.println("[HTTPS] GET...");

        int httpCode = https.GET();

        // httpCode will be negative on error
        if (httpCode > 0) {
          // HTTP header has been send and Server response header has been handled
          Serial.printf("[HTTPS] GET... code: %d\n", httpCode);
          // file found at server?
          if (httpCode == HTTP_CODE_OK) {
            String payload = https.getString();


            // Search for the specific div content in the HTML response.
            String startTag = "<p id=\"arduino\">";
            String endTag = "</p>";
            int start = payload.indexOf(startTag);

            if (start != -1) {
              int end = payload.indexOf(endTag, start);
              if (end != -1) {
                String divContent = payload.substring(start + startTag.length(), end);
                Serial.println("Div content:");
                Serial.println(divContent);

                DeserializationError error = deserializeJson(doc, divContent);
                if (error) {
                  Serial.print("Errore di parsing JSON: ");
                  Serial.println(error.c_str());
                } else {
                  // Leggi i parametri direttamente dal JSON
                  String timestamp = doc["timestamp"];
                  //stato del led
                  led_green_val = doc["led_green"][0];
                  led_yellow_val = doc["led_yellow"][0];
                  led_red_val = doc["led_red"][0];
                  led_blue_val = doc["led_blue"][0];
                  //percenntuale lampeggio
                  led_green_perc = doc["led_green"][1];
                  led_yellow_perc = doc["led_yellow"][1];
                  led_red_perc = doc["led_red"][1];
                  led_blue_perc = doc["led_blue"][1];
                  String statoAttuale = doc["statoAttuale"];

                  if (statoAttuale != statoUltimo) {
                    if (statoAttuale == "eco" || statoAttuale == "sole") {
                      Serial.println("Lo stato è cambiato: " + statoUltimo + " è diventato -> " + statoAttuale);
                      statoUltimo = statoAttuale;
                    } else {
                      Serial.println("parametro statoAttuale non valido->" + statoAttuale + "<-");
                    }
                  } else {
                    Serial.println("Lo stato non è cambiato: siamo ancora in modo: " + statoAttuale);
                  }
                  //url = "energia.php?passwordPagina=xxxxxx&device=nodemcu&statoUltimo=" + statoUltimo + "&temp=" + temperature + "&hum=" + humidity;  // Ad esempio, "http://example.com/data.json"
                  //Serial.println(url);
                  // Ora puoi utilizzare i valori dei parametri come desideri
                  Serial.print("hum2: ");
                  Serial.println(dht.readHumidity());
                }
              } else {
                Serial.println("End tag not found.");
              }
            } else {
              Serial.println("Start tag not found.");
            }
          }
        } else {
          Serial.printf("[HTTPS] GET... failed, error: %s\n\r", https.errorToString(httpCode).c_str());
        }
        https.end();
      } else {
        Serial.printf("[HTTPS] Unable to connect\n\r");
      }
    } else {
      Serial.println("Wi-Fi not connected. Reconnecting...");
      WiFi.begin(ssid, password);
      delay(5000);  // Wait for reconnection
    }


    // Reset the timer
    previousMillisToLoop = currentMillisToLoop;
  }

  blinkLED(greenLED, led_green_perc, led_green_val, previousMillisLEDGreen);
  blinkLED(yellowLED, led_yellow_perc, led_yellow_val, previousMillisLEDYellow);
  blinkLED(redLED, led_red_perc, led_red_val, previousMillisLEDRed);
  blinkLED(blueLED, led_blue_perc, led_blue_val, previousMillisLEDBlue);

  if (statoUltimo == "eco") {
    blinkLED(whiteLED, 40, 2, previousMillisLEDWhite);
  } else if (statoUltimo == "sole") {
    blinkLED(whiteLED, 90, 2, previousMillisLEDWhite);
  } else {
    blinkLED(whiteLED, 0, 1, previousMillisLEDWhite);
  }
}
