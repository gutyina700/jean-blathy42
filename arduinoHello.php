<?php
$message = json_decode($input, true);
$chipId = $message["chipId"];
$boot = $message["boot"];
$arduinoData = $message["arduinoData"];
//fLog(var_export($arduinoData));
echo($arduinoData[8]);
if($chipId != null){
	//echo("eche: ".$chipId);
	if($boot){
		mySQLExec("insert into communication_log(device_id, restart, send_error, created_at) values((SELECT id from device where device_serial=".$chipId."),1, 0, now());");
	}
	else{
		mySQLExec("insert into communication_log(device_id, restart, send_error, created_at) values((SELECT id from device where device_serial=".$chipId."),0, 0, now());");
	}
	$myQuerry = mySQLExec("select * from device where device_serial=".$chipId.";");
	if($myQuerry == null){
		mySQLExec("INSERT INTO device(device_serial, last_sync) values(".$chipId.",now())");
	}
	else{
		
		//set
		$set = mySQLExec("SELECT feature.id, feature.pin_type, feature.pin_number, feature.pin_value FROM device INNER JOIN feature ON device.id=feature.device_id where device.device_name = 'TestUno' AND feature.is_write = 0");
		for($arduinoDataIndex = 0; $arduinoDataIndex < count($arduinoData); $arduinoDataIndex++)
		{
			$arduinoPinType = $arduinoDataIndex < 10 ? "digital" : "analog";
			$setPinIndex = -1;
			for($j = 0; $j < count($set); $j++)
			{
				if($set[$j]["pin_number"] == $arduinoDataIndex) $setPinIndex = $j;
			}
			if($setPinIndex == -1) continue;
			mySQLExec("UPDATE feature SET pin_value = " . $arduinoData[$arduinoDataIndex] . " WHERE id = " . $set[$setPinIndex]["id"]);
		}
		
		
		//get
		mySQLExec("UPDATE device SET last_sync= NOW() where device_serial = $chipId;");
		$sqlValue = mySQLExec("SELECT feature.pin_type, feature.pin_number, feature.pin_value FROM device INNER JOIN feature ON device.id=feature.device_id where device.device_name = 'TestUno' AND feature.is_write = 1");
		/*$jsonObj->pin = "4";
		$jsonObj->value = $sqlValue;
		$myJSON = json_encode($jsonObj);
		//echo $myJSON;*/
	}
	//fLog(var_export($myQuerry));
}
else{
		echo("A böngésző nem támogatott platform!");
}
?>