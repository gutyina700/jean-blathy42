<?php
    /**
     * Logs a string to a file and to echo.
     * 
     * @param string text - text to log
     * @param string type - optional param, it can be "err"(error) and "info"
     */
    function fLog($str, $type = null)
    {
        $txt = "[" . date("Y.m.d-H:i:s:") . "] ";
        switch($type)
        {
            case "err":
                $txt .= "[ERROR] $str";
                $color = '<span style="color:#BC0000;">%s</span>';
                break;
            case "info":
                $txt .= "[INFO] $str";
                $color = '<span style="color:#55BA57;">%s</span>';
                break;
            default:
                $txt .= $str;
                $color = '<span style="color:#666666;">%s</span>';
        };
        file_put_contents(logFile, "$txt\n", FILE_APPEND);
        echo sprintf($color, str_replace(array("\n", "\t"), array("<br/>", "&emsp;"), $txt)) . "<br/>";
    }

    /**
     * Return the the string value of the input boolean
     * If the input is not a boolean, it return NULL
     * 
     * @param bool theBoolean
     * @return string booleanInString
     */
    function boolToString($bool)
    {
        if(gettype($bool) != "boolean") return NULL;
        if($bool) return "true";
        else return "false";
    }

    /**
     * Determines whether a string contains specific string or one of the specific strings.
     * 
     * @param string text - this is where the function will search
     * @param bool caseSensitivite - true for case sensitivite, false for insensitive
     * @param ...string search - this is what the function will search for
     * @return bool found - return if at least one of the search found
     */
    function contains($txt, ...$params)
    {
        $caseSensitive = true;
        if(gettype($params[0]) == "boolean")
        {
            $caseSensitive = $params[0];
            $search = array_slice($params, 1);
        }
        else $search = $params;
        if(count($search) == 1) $search = $search[0];
        switch(gettype($search))
        {
            case "string":
                if(!$caseSensitive)
                {  
                    $txt = strtolower($txt);
                    $search = strtolower($search);
                }
                return strpos(" $txt", $search) != false;
            case "array":
                foreach($search as $s) if(contains($txt, $caseSensitive, $s)) return true;
                return false;
        }
    }

    /**
     * Determines whether an element of an array is equalt to a specific string.
     * 
     * @param array theArray - this is where the function will search
     * @param string search - this is what the function will search for
     * @return bool found - return if at least one of the array's element is equal to the search
     */
    function arrayContains($arr, $search)
    {
        foreach($arr as $v) if($search == $v) return true;
        return false;
    }

    /**
     * Determines whether a string contains one of the array's elements.
     * 
     * @param string subject - this is what the function will search in
     * @param array theArray - the function will search with this array
     * @return bool found - return if at least one of the array's element found in the subject
     */
    function containsArray($subject, $arr)
    {
        foreach($arr as $v) if(contains($subject, $v)) return true;
        return false;
    }

    /**
     * Swaps the key and the value in an array.
     * 
     * @param array theArray
     * @param array swappedArray
     */
    function arraySwap($arr)
    {
        if(gettype($arr) != "array") return null;
        $resultArr = array();
        foreach($arr as $k => $v) $resultArr[$v] = $k;
        return $resultArr;
    }

    /**
     * Determines if the entered number or string has a float value.
     * 
     * @param (string|float) subject
     * @return bool isFloat
     */
    function isFloat($subject)
    {
        return is_numeric($subject) && $subject <= PHP_FLOAT_MAX;
    }

    /**
     * Determines if the entered number or string has a float value.
     * 
     * @param (string|float) subject
     * @return bool isFloat
     */
    function isInt($subject)
    {
        return ctype_digit($subject) && $subject <= PHP_FLOAT_MAX;
    }
?>