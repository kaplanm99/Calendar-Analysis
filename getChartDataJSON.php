<?php

    $data = array();

    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);
    if ($stmt = $mysqli->prepare("SELECT `user_id`, `array_serialized` FROM `data_analysis` WHERE `data_analysis_type` = ? AND `nonrecurring_included` = ? AND `recurring_included` = ? ;")){
        
        $data_analysis_type = "day_of_the_week_created";
        
        $nonrecurring_included = 1;
        $recurring_included = 0;
        
        $stmt->bind_param('sii', $data_analysis_type, $nonrecurring_included, $recurring_included);
        $stmt->execute();
        $stmt->bind_result($user_id, $array_serialized);
        
        while($stmt->fetch()) {
            $data[$user_id] = unserialize($array_serialized);
        }
        
        $stmt->close();

    }
       
    $mysqli->close();
    
    
    
    
    print('{ "cols": [ {"id":"","label":"day_of_the_week_created","pattern":"","type":"string"},');
    
    foreach($data as $user_id => $array_unserialized) {        
        print('{"id":"","label":"'.$user_id.'","pattern":"","type":"number"},');
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
            print('{"v":'.$array_unserialized[$i].'},');
        }
        
        print(']},');
        
    }
    
    print(']}');
    
        
?>