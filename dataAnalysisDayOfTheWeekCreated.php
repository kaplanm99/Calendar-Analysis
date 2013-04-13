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
                processEventForDayOfTheWeekEventCreated($dayOfTheWeekEventCreated, $date['dtstart'], $date['dtend'], $event["google_created"]);
            }
        }

            
    }
    
    
    $users = array();
    
    $dayOfTheWeekEventCreated = array();
    
    $user_ids = getUserIds();
    
    $usersWithEventsCount = 0;
    
    print('{ "cols": [ 
        {"id":"","label":"Day of the Week","pattern":"","type":"string"},');
    
    
    foreach($user_ids as $user_id) {    
    
        $nonrecurringEvents = getEventsByUser(false,$user_id);
        $recurringEvents = getEventsByUser(true,$user_id);
            
        $dayOfTheWeekEventCreated[$user_id] = array();    
            
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekEventCreated[$user_id][$i] = 0;
        }
        
        getDayOfTheWeekEventCreated($dayOfTheWeekEventCreated[$user_id], $nonrecurringEvents, false);
        getDayOfTheWeekEventCreated($dayOfTheWeekEventCreated[$user_id], $recurringEvents, true);
        
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
?>