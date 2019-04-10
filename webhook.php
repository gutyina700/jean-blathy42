 <?php
    require_once "utils.php"; // random functions and informations
    require_once "webexHelper.php"; // webexGet, webexPost and response functions
    require_once "mySQLHelper.php"; //  mySQLConn and mySQLExec functions
    
    // path where to write stuff like logs
    const logFile = "log/php.log";
    
    // write errors to file
    ini_set("log_errors", 1);
    ini_set("error_log", logFile);
    $mySQLLogEnabled = true;

    // get informations about the webex message
    $input = file_get_contents("php://input"); // body
    function test()
    {
        # fLog("start of test function");
        
        
        
        # fLog("end of test function");
    }
    if($input == null) test();
    $inputJson = json_decode($input, true);
    if(!isset($inputJson["targetUrl"]))
    {
        require_once "arduinoHello.php";
        exit;
    }
    $message = json_decode(wGet("messages/" . $inputJson["data"]["id"]), true); // webex message array
    if($message["personEmail"] == email) exit; // make sure this is not the bot's message
    $text = $message["text"];
    if(strlen($text) > 0) fLog("Message received: $text");
    $roomId = $message["roomId"];

    const keywords = array("add", "remove", "list");
    // respond messages
    const syntax = array(
        "base" => "Commands: add, remove, list, <feature_name> ...",
        "baseAdd" => "add <device|feature> ...",
        "baseAddDevice" => "add device <name> <id|serial>",
        "baseAddDeviceName" => "add device %s <id|serial>",
        "baseAddDeviceNameId" => "add device %s %s",
        "baseAddFeature" => "add feature <name> <device_name> <pin> <digital|analog> <read|write>",
        "baseAddFeatureName" => "add feature %s <device_name> <pin> <digital|analog> <read|write>",
        "baseAddFeatureNameDevicename" => "add feature %s %s <pin> <digital|analog> <read|write>",
        "baseAddFeatureNameDevicenamePin" => "add feature %s %s %s <digital|analog> <read|write>",
        "baseAddFeatureNameDevicenamePinType" => "add feature %s %s %s %s <read|write>",
        "baseAddFeatureNameDevicenamePinTypeReadwrite" => "add feature %s %s %s %s %s",
        "baseRemove" => "remove <device|feature> <name>",
        "baseRemoveDevice" => "remove device <name>",
        "baseRemoveDeviceName" => "remove device %s",
        "baseRemoveFeature" => "remove feature <name>",
        "baseRemoveFeatureName" => "remove feature %s",
        "baseList" => "list <devices|features> [all]",
        "baseListDevices" => "list devices [all]",
        "baseListDevicesAll" => "list devices all",
        "baseListFeatures" => "list features",
        "baseFeatureName" => "%s <get|set> ...",
        "baseFeatureNameGet" => "%s get",
        "baseFeatureNameSet" => "%s set <value>",
        "baseFeatureNameSetValue" => "%s set %s",
        "succAdded" => "Successfully added %s %s.",
        "succRemoved" => "Successfully removed %s %s.",
        "succListDevices" => "List of devices:\n%s",
        "succListDevicesAll" => "List of all the devices:\n%s",
        "succListFeatures" => "List of features:\n%s",
        "succListFeaturesAll" => "List of all the features :\n%s",
        "succPinGet" => "The value of %s is %s.",
        "succPinSet" => "Successfully set %s to value %s.",
        "errInvalidBase" => "There is no command or feature called %s.",
        "errSpecialChar" => "Special characters are not allowed.",
        "errAlreadyExist" => "%s %s is already exists.",
        "errAlreadyOccupied" => "%s %s is already occupied.",
        "errAlreadyExistInCurrentRoom" => "The %s %s is already exists in this room.",
        "errFeatureReadOnly" => "Feature %s is read only.",
        "errFeatureDigital" => "The value of a digital feature can only be true or false.",
        "errFeatureAnalog" => "The value of an analog feature can only 0 to 1023.",
        "errListDevicesEmpty" => "You don't have any devices.",
        "errListFeaturesEmpty" => "You don't have any features.",
        "errNotExist" => "%s %s is not exists.",
        "errTooLong" => "%s is too long (max %s characters)",
        "errTooShort" => "%s is too short (min %s characters)",
        "errBetween" => "%s must be between %s and %s.",
        "errInt" => "%s must be an integer.",
        "errFloat" => "%s must be an decimal number.",
        "errKeywordInName" => "%s name cannot be a keyword.",
        "errMySQL" => "A problem occured with mySQL.",
        "errUnknown" => "An unknown error has occured.",
        "err" => "An error has occured.",
        "errMention" => "Please start by mentioning me.",
        "errMoreMentions" => "Mentioning more people is not allowed."
    );

    // respond a message then exit
    function show($key, ...$params)
    {
        if($params == NULL) $syntax = syntax[$key];
        else $syntax = vsprintf(syntax[$key], $params);
        respond($syntax);
        prepareExit();
    }

    // respond a message for syntax then exit
    function showSyntax($key, ...$params)
    {
        if($params == NULL) $syn = syntax[$key];
        else $syn = vsprintf(syntax[$key], $params);
        respond("Invalid syntax. Try $syn");
        prepareExit();
    }

    // exits with an error message if the string is too long or short
    function checkLen($tool, $subject, $max = 100, $min = 2)
    {
        $len = strlen($subject);
        if($len > $max) show("errTooLong", $tool, $max);
        if($len < $min) show("errTooShort", $tool, $min);
    }

    // exists with an error message if the input is not a number
    function checkInt($tool, $subject)
    {
        if(!isInt($subject)) show("errInt", $tool);
    }

    // exists with an error message if the input is not a number
    function checkFloat($tool, $subject)
    {
        if(!isFloat($subject)) show("errFloat", $tool);
    }
    
    // exists if with an error message if the number is more or less than the input values
    function checkBetween($tool, $subject, $min, $max, $isInt = true)
    {
        if($isInt) checkInt($tool, $subject);
        else checkFloat($tool, $subject);
        if($subject < $min || $subject > $max) show("errBetween", $tool, $min, $max);
    }

    // logs a well visible database error string string
    function dbError($txt)
    {
        fLog("DATABASE ERROR\n\t!!!\n\t\n\t$txt\n\t\n\t!!!", "err");
        show("err");
    }

    // mySQL injection fix
    const specialChars = array("\$", "\0", "'", "\"", "\n", "\r", "\t", "\\", "%", "--");
    if(containsArray($text, specialChars)) show("errSpecialChar");

    // decide the message came from group or direct
    $params = explode(" ", trim($text));
    switch($message["roomType"])
    {
        case "group":
            if($params[0] != "Jean") show("errMention");
            if(count($message["mentionedPeople"]) > 1) show("errMoreMentions");
            break;
        case "direct":
            array_unshift($params, "NULL");
            break;
    }

    $count = count($params);
    const nameLenLimit = 15;

    // the huge uncommented code below handles the user input through webex teams
    if($count == 1) show("base");
    if($count > 1)
    {
        switch($params[1])
        {
            case "add":
                if($count == 2) showSyntax("baseAdd");
                switch($params[2])
                {
                    case "device":
                        if($count == 3) showSyntax("baseAddDevice");
                        $deviceName = $params[3];
                        checkLen("Device name", $deviceName, nameLenLimit);
                        $foundDevices = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE room_id = '%s' AND device_name = '%s'", $roomId, $deviceName)[0]["count"];
                        if($foundDevices == 1) show("errAlreadyExist", "Device", $deviceName);
                        if($foundDevices > 0) dbError("DUPLICATE ROWS FOUND IN DEVICE TABLE: device_name: $deviceName");
                        if($count == 4) showSyntax("baseAddDeviceName", $deviceName);
                        $idSerial = $params[4];
                        if(strlen($idSerial) < 4)
                        {
                            checkInt("Device id", $idSerial);
                            $sqlIdSerial = $strIdSerial = "id";
                        }
                        else
                        {
                            $sqlIdSerial = "device_serial";
                            $strIdSerial = "serial";
                        }
                        $foundIdSerials = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE $sqlIdSerial = '%s'", $idSerial)[0]["count"];
                        if($foundIdSerials == 0) show("errNotExist", "Device $strIdSerial", $idSerial);
                        if($foundIdSerials != 1) dbError("DUPLICATE ROWS FOUND IN DEVICE TABLE: $strIdSerial: $idSerial");
                        $freeIdSerials = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE $sqlIdSerial = '%s' AND room_id IS NULL", $idSerial)[0]["count"];
                        if($freeIdSerials == 0) show("errAlreadyOccupied", "Device $strIdSerial", $idSerial);
                        if($count != 5) showSyntax("baseAddDeviceNameId", $deviceName, $idSerial);
                        mySQLExec("UPDATE device SET room_id = '%s', device_name = '%s' WHERE $sqlIdSerial = '%s'", $roomId, $deviceName, $idSerial);
                        show("succAdded", "device", $deviceName);
                        break;
                    case "feature":
                        if($count == 3) showSyntax("baseAddFeature");
                        $featureName = $params[3];
                        checkLen("Feature name", $featureName, nameLenLimit);
                        if(arrayContains(keywords, $featureName)) show("errKeywordInName", "Feature");
                        $SQLdevicesId = mySQLExec("SELECT id FROM device WHERE room_id = '%s'", $roomId);
                        $devicesId = array();
                        foreach($SQLdevicesId as $val) array_push($devicesId, $val["id"]);
                        $devicesIdStr = implode(", ", $devicesId);
                        $SQLfeatureNames = mySQLExec("SELECT feature_name FROM feature WHERE device_id IN (%s)", $devicesIdStr);
                        $featureNames = array();
                        foreach($SQLfeatureNames as $val) array_push($featureNames, $val["feature_name"]);
                        if(arrayContains($featureNames, $featureName)) show("errAlreadyExistInCurrentRoom", "feature", $featureName);
                        if($count == 4) showSyntax("baseAddFeatureName", $featureName);
                        $deviceName = $params[4];
                        checkLen("Device name", $deviceName, nameLenLimit);
                        $foundDevices = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE room_id = '%s' AND device_name = '%s'", $roomId, $deviceName)[0]["count"];
                        if($foundDevices == 0) show("errNotExist", "Device name", $deviceName);
                        if($foundDevices != 1) dbError("DUPLICATE ROWS FOUND IN DEVICE TABLE: device name: $deviceName, room_id: $roomId");
                        $deviceId = mySQLExec("SELECT id FROM device WHERE room_id = '%s' AND device_name = '%s'", $roomId, $deviceName)[0]["id"];
                        $featuresFound = mySQLExec("SELECT COUNT(id) AS count FROM feature WHERE device_id = '%s' AND feature_name = '%s'", $deviceId, $featureName)[0]["count"];
                        if($featuresFound == 1) show("errAlreadyExist", "Feature", $featureName);
                        if($featuresFound != 0) dbError("DUPLICATE ROWS FOUND IN FEATURE TABLE: device_id: $deviceId, feature_name: $featureName");
                        if($count == 5) showSyntax("baseAddFeatureNameDevicename", $featureName, $deviceName);
                        $pin = $params[5];
                        checkInt("Pin", $pin);
                        $pinMin = 0;
                        $pinMax = 50;
                        checkBetween("Pin number", $pin, $pinMin, $pinMax);
                        if($count == 6) showSyntax("baseAddFeatureNameDevicenamePin", $featureName, $deviceName, $pin);
                        $pinType = $params[6];
                        if(!arrayContains(array("digital", "analog"), $pinType)) showSyntax("baseAddFeatureNameDevicenamePin", $featureName, $deviceName, $pin);
                        $pinsFound = mySQLExec("SELECT COUNT(id) AS count FROM feature WHERE device_id = '%s' AND pin_type = '%s' AND pin_number = '%s'", $deviceId, $pinType, $pin)[0]["count"];
                        if($pinsFound == 1) show("errAlreadyOccupied", "Pin", $pinType . " " . $pin);
                        if($pinsFound > 1) dbError("DUPLICATE ROWS FOUND IN FEATURE TABLE: device_id: $deviceId, pin_type: $pinType, pin_number: $pinNumber");
                        if($count == 7) showSyntax("baseAddFeatureNameDevicenamePinType", $featureName, $deviceName, $pin, $pinType);
                        $pinReadwrite = $params[7];
                        if(arrayContains(array("read", "0", "false"), $pinReadwrite)) $pinWritable = "false";
                        elseif(arrayContains(array("write", "1", "true"), $pinReadwrite)) $pinWritable = "true";
                        else showSyntax("baseAddFeatureNameDevicenamePinType", $featureName, $deviceName, $pin, $pinType);
                        if($count > 8) showSyntax("baseAddFeatureNameDevicenamePinTypeReadwrite", $featureName, $deviceName, $pin, $pinType, $pinReadwrite);
                        mySQLExec("INSERT INTO feature(device_id, feature_name, pin_value, pin_type, pin_number, is_write) VALUES('%s', '%s', '0', '%s', '%s', %s)", $deviceId, $featureName, $pinType, $pin, $pinWritable);
                        show("succAdded", "feature", $featureName);
                        break;
                    default:
                        showSyntax("baseAdd");
                }
                break;
            case "remove":
                if($count == 2) showSyntax("baseRemove");
                switch($params[2])
                {
                    case "device":
                        if($count == 3) showSyntax("baseRemoveDevice");
                        $deviceName = $params[3];
                        checkLen("Device name", $deviceName);   
                        $foundDevices = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE room_id = '%s' AND device_name = '%s'", $roomId, $deviceName)[0]["count"];
                        if($foundDevices == 0) show("errNotExist", "Device", $deviceName);
                        if($foundDevices > 1) dbError("DUPLICATE ROWS FOUND IN DEVICE TABLE: name: $deviceName");
                        if($count != 4) showSyntax("baseRemoveDeviceName", $deviceName);
                        $deviceId = mySQLExec("SELECT id FROM device WHERE room_id = '%s' AND device_name = '%s'", $roomId, $deviceName)[0]["id"];
                        mySQLExec("DELETE FROM feature WHERE device_id = '%s'", $deviceId);
                        mySQLExec("UPDATE device SET room_id = NULL, device_name = NULL WHERE device_name = '%s'", $deviceName);
                        show("succRemoved", "device", $deviceName);
                        break;
                    case "feature":
                        if($count == 3) showSyntax("baseRemoveFeature");
                        $featureName = $params[3];
                        checkLen("Feature name", $featureName);
                        $SQLdevicesId = mySQLExec("SELECT id FROM device WHERE room_id = '%s'", $roomId);
                        $devicesId = array();
                        foreach($SQLdevicesId as $val) array_push($devicesId, $val["id"]);
                        $devicesIdStr = implode(", ", $devicesId);
                        $foundFeatures = mySQLExec("SELECT COUNT(id) AS count FROM feature WHERE feature_name = '%s' AND device_id IN (%s)", $featureName, $devicesIdStr)[0]["count"];
                        if($foundFeatures == 0) show("errNotExist", "Feature", $featureName);
                        if($foundFeatures > 1) dbError("DUPLICATE ROWS FOUND IN FEATURE TABLE: feature_name: $featureName, device_ids: $devicesIdStr");
                        if($count != 4) showSyntax("baseRemoveFeatureName", $featureName);
                        mySQLExec("DELETE FROM feature WHERE feature_name = '%s' AND device_id IN (%s)", $featureName, $devicesIdStr);
                        show("succRemoved", "feature", $featureName);
                        break;
                    default:
                        showSyntax("baseRemove");
                }
                break;
            case "list":
                if($count == 2) showSyntax("baseList");
                switch($params[2])
                {
                    case "devices":
                        if($count > 4 || ($count == 4 && $params[3] != "all")) showSyntax("baseListDevicesAll");
                        $foundDevices = mySQLExec("SELECT COUNT(id) AS count FROM device WHERE room_id = '%s'", $roomId)[0]["count"];
                        if($foundDevices == 0) show("errListDevicesEmpty");
                        $boolAll = $count == 4;
                        if($boolAll) $query = mySQLExec("SELECT id, device_serial, device_name, last_sync FROM device WHERE room_id = '%s' OR room_id IS NULL", $roomId);
                        else $query = mySQLExec("SELECT id, device_serial, device_name, last_sync FROM device WHERE room_id = '%s'", $roomId);
                        $listDevices = "";
                        foreach($query as $val)
                        {
                            if(!isset($val["device_name"])) $val["device_name"] = "Unnamed device";
                            $listDevices .= sprintf("\t%s: id: %s, serial: %s\n", $val["device_name"], $val["id"], $val["device_serial"]);
                        }
                        if($boolAll) show("succListDevicesAll", "\t" . trim($listDevices));
                        else show("succListDevices", "\t" . trim($listDevices));
                        break;
                    case "features":
                        if($count > 3) showSyntax("baseListFeatures");
                        $SQLdevices = mySQLExec("SELECT id, device_name FROM device WHERE room_id = '%s'", $roomId);
                        $devices = array();
                        foreach($SQLdevices as $val) array_push($devices, $val["id"]);
                        $devicesStr = implode(", ", $devices);
                        $foundFeatures = mySQLExec("SELECT COUNT(id) AS count FROM feature WHERE device_id IN (%s)", $devicesStr)[0]["count"];
                        if($foundFeatures == 0) show("errListFeaturesEmpty");
                        $SQLfeatures = mySQLExec("SELECT device_id, feature_name, pin_value, pin_type, pin_number, is_write, last_sync FROM feature WHERE device_id IN (%s)", $devicesStr);
                        $result = array();
                        foreach($SQLfeatures as $val)
                        {
                            foreach($SQLdevices as $value)
                                if($value["id"] == $val["device_id"])
                                {
                                    $deviceName = $value["device_name"];
                                    break;
                                }
                            array_push($result, array("device_name" => $deviceName,
                                "feature_name" => $val["feature_name"],
                                "pin_value" => $val["pin_value"],
                                "pin_type" => $val["pin_type"],
                                "pin_number" => $val["pin_number"],
                                "is_write" => $val["is_write"],
                                "last_sync" => $val["last_sync"]));
                        }
                        foreach($result as $val)
                        {
                            $pinValue = $val["pin_value"];
                            $val["is_write"] = $val["is_write"] ? "write" : "read";
                            switch($val["pin_type"])
                            {
                                case "digital":
                                    $pinValue = $pinValue == 1 ? "on" : "off";
                                    break;
                                case "analog":
                                    $pinValue = round($pinValue);
                                    break;
                            }
                            $listFeatures .= sprintf("\t%s: device: %s, pin: %s %s %s -> %s\n", $val["feature_name"], $val["device_name"], $val["pin_type"], $val["pin_number"], $val["is_write"], $pinValue);
                        }
                        show("succListFeatures", "\t" . trim($listFeatures));
                        break;
                    default:
                        showSyntax("baseList");
                }
                break;
            default:
                $featureName = $params[1];
                $SQLdevicesId = mySQLExec("SELECT id FROM device WHERE room_id = '%s'", $roomId);
                $devicesId = array();
                foreach($SQLdevicesId as $val) array_push($devicesId, $val["id"]);
                $devicesIdStr = implode(", ", $devicesId);
                $featuresFound = mySQLExec("SELECT COUNT(id) AS count FROM feature WHERE feature_name = '%s' AND device_id IN ($devicesIdStr)", $featureName)[0]["count"];
                if($featuresFound == 0) show("errNotExist", "Feature", $featureName);
                if($featuresFound > 1) dbError("DUPLICATE ROWS FOUND IN FEATURE TABLE: feature_name: $featureName, device_ids: $devicesIdStr");
                if($count == 2) showSyntax("baseFeatureName", $featureName);
                $pinType = mySQLExec("SELECT pin_type FROM feature WHERE feature_name = '%s' AND device_id IN ($devicesIdStr)", $featureName)[0]["pin_type"];
                switch($params[2])
                {
                    case "get":
                        if($count != 3) showSyntax("baseFeatureNameGet", $featureName);
                        $pinValue = mySQLExec("SELECT pin_value FROM feature WHERE feature_name = '%s' AND device_id IN ($devicesIdStr)", $featureName)[0]["pin_value"];
                        switch($pinType)
                        {
                            case "digital":
                                $pinValue = $pinValue == 1 ? "true" : "false";
                                break;
                            case "analog":
                                $pinValue = round($pinValue);
                                break;
                        }
                        show("succPinGet", $featureName, $pinValue);
                        break;
                    case "set":
                        $writable = mySQLExec("SELECT is_write FROM feature WHERE feature_name = '%s' AND device_id IN (%s)", $featureName, $devicesIdStr)[0]["is_write"];
                        if(!$writable) show("errFeatureReadOnly", $featureName);
                        if($count == 3) showSyntax("baseFeatureNameSet", $featureName);
                        $pinValue = $params[3];
                        respond("0");
                        switch($pinType)
                        {
                            case "digital":
                                if(arrayContains(array("true", "on", "1", "enabled"), $pinValue)) $pinValue = 1;
                                elseif(arrayContains(array("false", "off", "0", "disabled"), $pinValue)) $pinValue = 0;
                                else show("errFeatureDigital");
                                break;
                            case "analog":
                                checkInt("Analog pin value", $pinValue);
                                if($pinValue < 0 || $pinValue > 1023) show("errFeatureAnalog");
                                break;
                        }
                        if($count != 4) showSyntax("baseFeatureNameSetValue", $featureName, $pinValue);
                        mySQLExec("UPDATE feature SET pin_value = '%s' WHERE feature_name = '%s' AND device_id IN ($devicesIdStr)", $pinValue, $featureName);
                        show("succPinSet", $featureName, $pinValue);
                        break;
                    default:
                        showSyntax("baseFeatureName", $featureName);
                }
        }
    }

    prepareExit();
    // before exiting this code runs
    function prepareExit()
    {
        sendResponses();
        fLog("--------------------------------------------------");
        exit;
    }
?>