#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiManager.h> 
#include <ArduinoJson.h>

int id;
bool boot;
int pins[10];
int pinId[] ={D0,D1,D2,D3,D4,D5,D6,D7,D8,A0};
int pinData[10];
bool pinInput[10];
const char* Data[20];
void setup() {
  // put your setup code here, to run once:
  
  
  id = ESP.getChipId();
  WiFiManager wifiManager;
  if(!wifiManager.autoConnect("Jean client")) {
    delay(5000);
  }
  boot = true;
  Serial.begin(9600);
  Serial.println("start");
  //digital reg
 
}

void loop() {
  for(int pmode = 0; pmode<9; pmode++)
  {
    pinMode(pinId[pmode], INPUT);
    pinData[pmode]=digitalRead(pinId[pmode]);
  }
  pinData[9]=analogRead(A0);
DynamicJsonBuffer JSONbuffer;
JsonObject& JSONencoder = JSONbuffer.createObject();
JsonArray& data = JSONencoder.createNestedArray("arduinoData");
  JSONencoder["chipId"] = id;
  JSONencoder["boot"] =  boot;
   for(int digit = 0; digit < 10; digit++)
  {
    data.add(pinData[digit]);
    Serial.println("Hozzadtam");
  }
  char JSONmessageBuffer[300];
    JSONencoder.prettyPrintTo(JSONmessageBuffer, sizeof(JSONmessageBuffer));
    Serial.println(JSONmessageBuffer);
  // put your main code here, to run repeatedly:
  Serial.println("hhtp start)");
  HTTPClient http;
   http.begin("http://jean.nsupdate.info/webhook.php");  //Specify request destination
    http.addHeader("Content-Type", "application/json");  //Specify content-type header
   Serial.println("hhtp 1)");
   int httpCode = http.POST(JSONmessageBuffer);   //Send the request
   String payload = http.getString();
    Serial.println(httpCode);   //Print HTTP return code
    Serial.println(payload);    //Print request response payload
     if (httpCode > 0) {
        // Parsing
        String data = http.getString(); 
      const size_t bufferSize = JSON_ARRAY_SIZE(8) + 20;
      DynamicJsonBuffer jsonBuffer(bufferSize);
      JsonObject& root = jsonBuffer.parseObject(data);
      JsonArray& arrayPin = root["database"];
      for(int i=0;i<8;i++){
        Data[i] = arrayPin[i];
        Serial.println("Printing whole Data");
        Serial.println(Data[i]);
      }
     }

    http.end();   //Close connection
    boot = false;
    delay(5000); 
}
