<?php
    
function processEventForEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $google_start, $google_end, $google_created) {
    $startUnixTimestamp = strtotime($google_start);
    $endUnixTimestamp = strtotime($google_end);
    $createdUnixTimestamp = strtotime($google_created);    
    
    $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
    $eventLengthInHours = $eventLengthInSeconds/(60*60);
    
    $dtstartInDays = intval($startUnixTimestamp/(60*60*24));
    //print($dtstartInDays . "<br/>");    
    
    // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
    if($startUnixTimestamp < strtotime("now") && $startUnixTimestamp > 0 && $createdUnixTimestamp > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
        $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
        $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
        //$timeDifferenceInDays = $timeDifferenceInDays . "";
        
        if($timeDifferenceInDays >= 0) {
            if(! array_key_exists($dtstartInDays, $eventsPerDay) ) {
                $eventsPerDay[$dtstartInDays] = array();
            }
            
            $eventsPerDay[$dtstartInDays][] = $timeDifferenceInDays;
            
            if($timeDifferenceInDays > $maxTimeDifferenceInDays) {
                $maxTimeDifferenceInDays = $timeDifferenceInDays;
            }
        }
    }
    /*
    if($startUnixTimestamp >= strtotime("now") ) {
        print("Future event<br/>");
    }
    */
}
    
function getEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $events, $recurring) {

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
                
                processEventForEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $date['dtstart'], $date['dtend'], $event["google_created"]);
                
                $z++;
                
                if($z > 160) break;              
            }
        
        }
        
    } else {
        foreach($events as $event) {
            processEventForEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $event["google_start"], $event["google_end"], $event["google_created"]);
        }
    }

        
}

function calculateRelativePercentageSums(&$relativePercentageSums, &$numOfDaysWithEvents, $eventsPerDay, $maxTimeDifferenceInDays) {
    for($i = 0;$i < $maxTimeDifferenceInDays;$i++) {
        $relativePercentageSums[$i] = 0;
    }
    
    foreach($eventsPerDay as $dtstartInDays => $eventsThatDay) {
        //sort($eventsThatDay);
        //print_r($eventsThatDaySorted);
        
        for($i = 0;$i < count($eventsThatDay);$i++) {
            for($j = 0;$j <= $eventsThatDay[$i];$j++) {
                $relativePercentageSums[$j] += (1/count($eventsThatDay));
            }
        }
        
        $numOfDaysWithEvents++;        
    }
}

?>