<?php
    require_once "dbconfig.php";

    $mySQLLogEnabled = false;

    /**
     * Same as fLog in Utils.php but it has a [mySQL] part.
     */
    function mySQLLog($str, $type = null)
    {
        if($GLOBALS["mySQLLogEnabled"]) fLog("[mySQL] $str", $type);
    }

    /**
     * Connects to the database with the creditinals (host, username, password, dbname)
     * Not necessary because other functions will connect if not connected
     */
    function mySQLConn()
    {
        if(isset($GLOBALS["db"])) return;
        $GLOBALS["db"] = new mysqli(host, username, password, dbname);
        if($GLOBALS["db"] -> connect_error) ; # mySQLLog("Could not connect to the database " . dbname . ": {$GLOBALS["db"] -> connect_error}", "err");
        else; # mySQLLog("Connected to " . dbname . " at " .host . " successfully", "info");
    }

    /**
     * Executes a mySQL command.
     * Multiple commands are allowed seperating with a semicolon: "DROP TABLE a; DROP TABLE b;"
     * To comment out a statement use "#"
     * 
     * @param string sql - command(s) or query(es) what this function will execute
     * @param ...string params - none or multiple parameters. These will be escaped and replaced into sql marked by %s
     * 
     * If you only write 1 command:
     * @return bool success - it returns when the you did not get, only post data
     * @return mixed data - it returns on getting data
     * 
     * If you only write more than 1 command:
     * @return mixed(bool or/and Object) success or/and data - it returns an array of bools and Objects
     */
    function mySQLExec($in, ...$params)
    {
        mySQLConn();
        $db = $GLOBALS["db"];
        $countSql = substr_count($in, "%s");
        $countParams = count($params);
        if($countSql != $countParams)
        {
            foreach($params as $val) $pars .= "$val, ";
            $pars = "{" . substr($pars, 0, -2) . "}";
            $error = "\n\tSQL: $in\n\tParameters: $pars";
            if($countSql > $countParams) mySQLLog("Too few parameters. $error", "err");
            else mySQLLog("Too many parameters. $error", "err");
            return NULL;
        }
        else
        {
            for($i = 0; $i < count($params); $i++) $params[$i] = $db -> real_escape_string($params[$i]);
            $in = vsprintf($in, $params);
        }
        
        $responses = array();
        $sqls = explode(";", $in);
        foreach($sqls as $sql)
        {
            if(strlen($sql) == 0 || contains($sql, "#")) continue;
            
            $resp = $db -> query($sql);
            if($resp === false)
            {
                mySQLLog("Failed to execute SQL command.\n\tSQL: $sql", "err");
                array_push($responses, $resp);
                continue;
            }
            if(contains($sql, false, "INSERT", "UPDATE", "REPLACE", "DELETE"))
            {
                $countRows = $db -> affected_rows;
                mySQLLog("Successfully executed mySQL command, rows affected: $countRows\n\tSQL: $sql", "info");
                array_push($responses, $countRows);
                continue;
            }
            if(contains($sql, false, "SELECT"))
            {
                if($resp -> num_rows == 0)
                {
                    mySQLLog("Successfully executed mySQL query, but got nothing.\n\tSQL: $sql", "info");
                    array_push($responses, array());
                    continue;
                }
                $query = array();
                while($row = $resp -> fetch_assoc())
                {
                    array_push($query, $row);
                }
                mySQLLog("Successfully executed mySQL query.\n\tSQL: $sql\n\tResult: " . json_encode($query), "info");
                array_push($responses, $query);
                continue;
            }
            mySQLLog("Successfully executed mySQL command.\n\tSQL: $sql", "info");
            array_push($responses, true);
        }
        if(count($responses) == 1) return $responses[0];
        else return $responses;
    }
?>