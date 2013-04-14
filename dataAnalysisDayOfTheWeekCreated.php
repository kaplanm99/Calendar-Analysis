<?php
    
    require_once('recurrence.php');            
    require('getEvents.php');
    require('getEventsPerDay.php');
    
    function processEventForDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreated, $google_start, $google_end, $google_created) {
        $startUnixTimestamp = strtotime($google_start);
        $endUnixTimestamp = strtotime($google_end);
        $createdUnixTimestamp = strtotime($google_created);    
        
        $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
        $eventLengthInHours = $eventLengthInSeconds/(60*60);
        
        $dtstartInDays = intval($startUnixTimestamp/(60*60*24));
        //print($dtstartInDays . "<br/>");    
        
        // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
        if($startUnixTimestamp > 0 && $createdUnixTimestamp > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
            $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
            $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
            //$timeDifferenceInDays = $timeDifferenceInDays . "";
            
            if($timeDifferenceInDays >= 0) {
                $dayFormat = "N";
                    
                $google_createdDT = new DateTime($google_created);
                $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                
                $dayOfTheWeekEventCreated[$google_created_DayOfTheWeek]++;
            }
        }
    }
    
    function getDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreated, $events, $recurring) {

        if($recurring) {
            
            foreach($events as $event) {  
            
                $google_recurrence_unserialized = unserialize($event["google_recurrence"]);
                    
                //print_r($google_recurrence_unserialized);
                
                $format = "Ymd\THis";

                $startDT = new DateTime($event["google_start"]);
                $dtStart = $startDT->format($format);
                
                $endDT = new DateTime($event["google_end"]);
                $dtEnd = $endDT->format($format);
                
                //print($dtStart . " , " . $dtEnd . "<br/>");  
                
                $rRule = substr($google_recurrence_unserialized[0],6);
                
                //print($rRule . "<br/>");
                
                // replace tzid with the event's Timezone need to access and store that value (add to IRB)
                
                $options = array(
                  'dtstart' => $dtStart,
                  'dtend'   => $dtEnd,
                  'tzid'    => 'America/New_York',
                  'rrule'   => $rRule
                );

                $recurrence = new recurrence($options);
                $recurrence->format = 'Y-m-d\TH:i:sP';

                $z = 0;
                
                while($date = $recurrence->next()) {
                    
                    processEventForDayOfTheWeekEventCreated($dayOfTheWeekEventCreated, $date['dtstart'], $date['dtend'], $event["google_created"]);
                    
                    $z++;
                    
                    if($z > 160) break;              
                }
            
            }
            
        } else {
            foreach($events as $event) {
                processEventForDayOfTheWeekEventCreated($dayOfTheWeekEventCreated, $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }

            
    }
    
    
    $users = array();
    
    $user_ids = getUserIds();
    
    foreach($user_ids as $user_id) {    
    
        $nonrecurringEvents = getEventsByUser(false,$user_id);
        $recurringEvents = getEventsByUser(true,$user_id);
            
        $dayOfTheWeekNonreccurringEventCreated = array();
        $dayOfTheWeekReccurringEventCreated = array();        
        $dayOfTheWeekNonreccurringAndReccurringEventCreated = array();
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringEventCreated[$i] = 0;
            $dayOfTheWeekReccurringEventCreated[$i] = 0;            
        }
        
        getDayOfTheWeekEventCreated($dayOfTheWeekNonreccurringEventCreated, $nonrecurringEvents, false);
        getDayOfTheWeekEventCreated($dayOfTheWeekReccurringEventCreated, $recurringEvents, true);
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringAndReccurringEventCreated[$i] = $dayOfTheWeekNonreccurringEventCreated[$i] +  $dayOfTheWeekReccurringEventCreated[$i];            
        }
        
        
        $dayOfTheWeekNonreccurringEventCreatedSum = 0;
        $dayOfTheWeekReccurringEventCreatedSum = 0;
        $dayOfTheWeekNonreccurringAndReccurringEventCreatedSum = 0;
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringEventCreatedSum += $dayOfTheWeekNonreccurringEventCreated[$i];
            $dayOfTheWeekReccurringEventCreatedSum += $dayOfTheWeekReccurringEventCreated[$i];
            $dayOfTheWeekNonreccurringAndReccurringEventCreatedSum += $dayOfTheWeekNonreccurringAndReccurringEventCreated[$i];         
        }
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringEventCreated[$i] /= $dayOfTheWeekNonreccurringEventCreatedSum;
            $dayOfTheWeekReccurringEventCreated[$i] /= $dayOfTheWeekReccurringEventCreatedSum;
            $dayOfTheWeekNonreccurringAndReccurringEventCreated[$i] /=$dayOfTheWeekNonreccurringAndReccurringEventCreatedSum;         
        }
        
        // serialize 1 per user and with and without recurrence and both
        $dayOfTheWeekNonreccurringEventCreatedSerialized = serialize($dayOfTheWeekNonreccurringEventCreated);
        
        $dayOfTheWeekReccurringEventCreatedSerialized = serialize($dayOfTheWeekReccurringEventCreated);
        
        $dayOfTheWeekNonreccurringAndReccurringEventCreatedSerialized = serialize($dayOfTheWeekNonreccurringAndReccurringEventCreated);
        
        
        //print($dayOfTheWeekNonreccurringEventCreatedSerialized . "<br>");
        //print($dayOfTheWeekReccurringEventCreatedSerialized . "<br>");
        
        
        require('db/config.php');
        $mysqli = new mysqli($host, $username, $password, $db);
        if ($stmt = $mysqli->prepare("INSERT INTO `data_analysis` (`user_id`, `data_analysis_type`, `nonrecurring_included`, `recurring_included`, `array_serialized`) VALUES (?,?,?,?,?);")){
            
            $user_id = intval($user_id);
            $data_analysis_type = "day_of_the_week_created";
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            
            $stmt->bind_param('isiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $dayOfTheWeekNonreccurringEventCreatedSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 0;
            $recurring_included = 1;
            
            $stmt->bind_param('isiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $dayOfTheWeekReccurringEventCreatedSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 1;
            $recurring_included = 1;
            
            $stmt->bind_param('isiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $dayOfTheWeekNonreccurringAndReccurringEventCreatedSerialized);
            $stmt->execute();
            
            $stmt->close();

        }
           
        $mysqli->close();
        
        //$dayOfTheWeekEventCreatedUnserialized = unserialize($dayOfTheWeekEventCreatedSerialized);
        
        //print_r($dayOfTheWeekEventCreatedUnserialized);
    }
    
    /*
    print('{ "cols": [ {"id":"","label":"Day of the Week","pattern":"","type":"string"},');
    
    foreach($user_ids as $user_id) {        
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
        
        foreach($user_ids as $user_id) {
            print('{"v":'.$dayOfTheWeekEventCreated[$user_id][$i].'},');
        }
        
        print(']},');
        
    }
    
    print(']}');
    */
?>