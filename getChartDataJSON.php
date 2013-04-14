<?php

    if(isset($_GET["data_analysis_type"]) && isset($_GET["nonrecurring_included"]) && isset($_GET["recurring_included"]) ) {    
        
        $data_analysis_type = strip_tags($_GET["data_analysis_type"]);
        $data_analysis_type = trim($data_analysis_type);
        $data_analysis_type = filter_var($data_analysis_type, FILTER_SANITIZE_STRING);
        
        $nonrecurring_included = strip_tags($_GET["nonrecurring_included"]);
        $nonrecurring_included = trim($nonrecurring_included);
        $nonrecurring_included = intval($nonrecurring_included);

        $recurring_included = strip_tags($_GET["recurring_included"]);
        $recurring_included = trim($recurring_included);
        $recurring_included = intval($recurring_included);

        $data = array();

        require('db/config.php');
        $mysqli = new mysqli($host, $username, $password, $db);
        if ($stmt = $mysqli->prepare("SELECT `user_id`, `array_serialized` FROM `data_analysis` WHERE `data_analysis_type` = ? AND `nonrecurring_included` = ? AND `recurring_included` = ? ;")){
            
            $stmt->bind_param('sii', $data_analysis_type, $nonrecurring_included, $recurring_included);
            $stmt->execute();
            $stmt->bind_result($user_id, $array_serialized);
            
            while($stmt->fetch()) {
                $data[$user_id] = unserialize($array_serialized);
            }
            
            $stmt->close();

        }
           
        $mysqli->close();
        
        
        
        
        print('{ "cols": [ {"id":"","label":"'.$data_analysis_type.'","pattern":"","type":"string"},');
        
        foreach($data as $user_id => $array_unserialized) {        
            print('{"id":"","label":"User '.$user_id.'","pattern":"","type":"number"},');
        }
        
        print(' ], "rows": [ ');
        
        if($data_analysis_type == "day_of_the_week_created" || $data_analysis_type == "day_of_the_week_started") { 
            $daysOfTheWeek = array();
            $daysOfTheWeek[1] = "Monday";
            $daysOfTheWeek[2] = "Tuesday";
            $daysOfTheWeek[3] = "Wednesday";
            $daysOfTheWeek[4] = "Thursday";
            $daysOfTheWeek[5] = "Friday";
            $daysOfTheWeek[6] = "Saturday";
            $daysOfTheWeek[7] = "Sunday";
            
            for($i = 1;$i <= 7;$i++) {
                
                print('{"c":[{"v":"'.$daysOfTheWeek[$i].'"},');
                
                foreach($data as $user_id => $array_unserialized) {            
                    print('{"v":'.floatval($array_unserialized[$i]).'},');
                }
                
                print(']},');
                
            }
        }
        
        if($data_analysis_type == "month_of_the_year_created") { 
            $monthsOfTheYear = array();
            $monthsOfTheYear[1] = "January";
            $monthsOfTheYear[2] = "Febuary";
            $monthsOfTheYear[3] = "March";
            $monthsOfTheYear[4] = "April";
            $monthsOfTheYear[5] = "May";
            $monthsOfTheYear[6] = "June";
            $monthsOfTheYear[7] = "July";
            $monthsOfTheYear[8] = "August";
            $monthsOfTheYear[9] = "September";
            $monthsOfTheYear[10] = "October";
            $monthsOfTheYear[11] = "November";
            $monthsOfTheYear[12] = "December";
            
            for($i = 1;$i <= 12;$i++) {
                
                print('{"c":[{"v":"'.$monthsOfTheYear[$i].'"},');
                
                foreach($data as $user_id => $array_unserialized) {            
                    print('{"v":'.floatval($array_unserialized[$i]).'},');
                }
                
                print(']},');
                
            }
        }
        
        print(']}');
    }
        
?>