<?php
$message = json_decode($input, true);
$chipId = $message["chipId"];
$boot = $message["boot"];
$arduinoData = $message["arduinoData"];
//fLog($chipId);
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
		mySQLExec("UPDATE device SET last_sync= NOW() where device_serial = $chipId;");
		$sqlValue = mySQLExec("SELECT pin_value FROM `feature` INNER JOIN device ON device.id = feature.device_id WHERE device.device_serial =".$chipId.";");
		$jsonObj->pin = "4";
		$jsonObj->value = $sqlValue;
		$myJSON = json_encode($jsonObj);
		echo $myJSON;
	}
	//fLog(var_export($myQuerry));
}
else{
		echo("A böngésző nem támogatott platform!");
}
?>