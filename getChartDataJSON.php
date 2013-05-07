<?php

    if(isset($_GET["data_analysis_type"]) && isset($_GET["nonrecurring_included"]) && isset($_GET["recurring_included"]) && isset($_GET["count_or_length"]) ) {    
        
        $data_analysis_type = strip_tags($_GET["data_analysis_type"]);
        $data_analysis_type = trim($data_analysis_type);
        $data_analysis_type = filter_var($data_analysis_type, FILTER_SANITIZE_STRING);
        
        $nonrecurring_included = strip_tags($_GET["nonrecurring_included"]);
        $nonrecurring_included = trim($nonrecurring_included);
        $nonrecurring_included = intval($nonrecurring_included);

        $recurring_included = strip_tags($_GET["recurring_included"]);
        $recurring_included = trim($recurring_included);
        $recurring_included = intval($recurring_included);
        
        $count_or_length = strip_tags($_GET["count_or_length"]);
        $count_or_length = trim($count_or_length);
        $count_or_length = intval($count_or_length);
        
        $data = array();

        require('db/config.php');
        $mysqli = new mysqli($host, $username, $password, $db);
        if ($stmt = $mysqli->prepare("SELECT `user_id`, `array_serialized` FROM `data_analysis` WHERE `data_analysis_type` = ? AND `nonrecurring_included` = ? AND `recurring_included` = ? AND `count_or_length` = ?;")){
            
            $stmt->bind_param('siii', $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length);
            $stmt->execute();
            $stmt->bind_result($user_id, $array_serialized);
            
            while($stmt->fetch()) {
                $data[$user_id] = unserialize($array_serialized);
            }
            
            $stmt->close();

        }
           
        $mysqli->close();
        
        
        
        if($data_analysis_type == "day_of_the_week_created" || $data_analysis_type == "day_of_the_week_started") { 
            print('{ "cols": [ {"id":"","label":"'.$data_analysis_type.'","pattern":"","type":"string"},');
        
            foreach($data as $user_id => $array_unserialized) {        
                print('{"id":"","label":"User '.$user_id.'","pattern":"","type":"number"},');
            }
            
            print(' ], "rows": [ ');
            
        
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
        
        if($data_analysis_type == "month_of_the_year_created" || $data_analysis_type ==  "month_of_the_year_started") { 
            print('{ "cols": [ {"id":"","label":"'.$data_analysis_type.'","pattern":"","type":"string"},');
        
            foreach($data as $user_id => $array_unserialized) {        
                print('{"id":"","label":"User '.$user_id.'","pattern":"","type":"number"},');
            }
            
            print(' ], "rows": [ ');
            
            
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
        
        if($data_analysis_type == "relative_percentage_sums") { 
            print('{ "cols": [ {"id":"'.$data_analysis_type.'","label":"'.$data_analysis_type.'","pattern":"","type":"number"},');
        
            foreach($data as $user_id => $array_unserialized) {        
                print('{"id":"User '.$user_id.'","label":"User '.$user_id.'","pattern":"","type":"number"},');
            }
            
            print(' ], "rows": [ ');
            
                
            $maxTimeDifferenceInDays = 0;
            
            foreach($data as $user_id => $array_unserialized) {            
                if( count($array_unserialized) > $maxTimeDifferenceInDays) {
                    $maxTimeDifferenceInDays = count($array_unserialized);
                }
            }
            
            for($i = 0;$i <= $maxTimeDifferenceInDays;$i++) {
                
                print('{"c":[{"v":"'.$i.'"},');
                
                foreach($data as $user_id => $array_unserialized) { 
                    print('{"v":'.floatval($array_unserialized[$i]).'},');
                }
                
                print(']},');
                
            }
        }
        
        
        
        print(']}');
    }
        
?>