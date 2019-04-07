<?php
    // bot access token
    const token = "ZmJkNzgyYWItZDc2Mi00NThkLTgzNzQtMzUwYzk2ODZjZjYzNjNkZTc0NWQtMjY2_PF84_consumer";
    // bot id
    const email = "jeanbot@webex.bot";
    // webex api uri base
    const baseUrl = "https://api.ciscospark.com/v1/";
    // header for webex requests
    const wHeader = array("Authorization: Bearer " . token, "Content-Type" => "application/json; charset=", "Content-Length" => "102");

    /**
     * Webex GET request.
     * 
     * @param string url
     * @param array params - for the request
     * @return string response
     */
    function wGet($url, $params = null)
    {
        $url = baseUrl . $url;
        if($params != null) $url .= "?" . http_build_query($params);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => wHeader
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
     * Webex POST request.
     * 
     * @param string url
     * @param array body
     * @return string response
     */
    function wPost($url, $body)
    {
        $url = baseUrl . $url;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => true,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => wHeader,
            CURLOPT_POSTFIELDS => http_build_query($body)
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response; 
    }

    $respondMessages = array();

    /**
     * Webex POST request. It posts back a message where the webhook was triggered.
     * $roomId must be specified as a global variable.
     * 
     * @param string text
     * @return string response
     */
    function respond($txt)
    {
        if(!isset($GLOBALS["roomId"])) fLog("Global variable 'roomId' is missing", "err");
        else array_push($GLOBALS["respondMessages"], $txt);
    } 
    function sendResponses()
    {
        $messages = $GLOBALS["respondMessages"];
        if(count($messages) == 0) return;
        wPost("messages", array("roomId" => $GLOBALS["roomId"], "text" => implode("\n", $messages)));
        fLog("Message(s) sent: " . implode(", ", $messages), "info");
    }
?>