<?php

require_once('recurrence.php');            

if( isset($_POST['password']) && $_POST['password'] == "pilot_calendar_study_results!!!!" ) {    

    $eventsPerDay = array();
    
    $maxTimeDifferenceInDays = 0;
    
    ///////////////////////////////////////////////////////////////////////
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    

    if ($stmt = $mysqli->prepare("SELECT `google_created`, `google_start`, `google_end`, `user_id` FROM `event` WHERE `google_recurrence` = '' AND `recurring_event_id` = 0;")) {
        $stmt->execute();
        $stmt->bind_result($google_created, $google_start, $google_end, $user_id);
        
        while($stmt->fetch()) {
                        
            $eventLengthInSeconds = strtotime($google_end) - strtotime($google_start);
            $eventLengthInHours = $eventLengthInSeconds/(60*60);
            
            $dtstartInDays = intval(strtotime($google_start)/(60*60*24));
            
            //print($dtstartInDays . "<br/>");
            
            
            // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
            if(strtotime($google_start) > 0 && strtotime($google_created) > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
                $timeDifferenceInSeconds = strtotime($google_start) - strtotime($google_created);
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
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    ///////////////////////////////////////////////////////////////////////
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    if ($stmt = $mysqli->prepare("SELECT `google_created`, `google_start`, `google_end`, `user_id`, `google_recurrence` FROM `event` WHERE `google_recurrence` <> '' AND `recurring_event_id` = 0;")) {
        $stmt->execute();
        $stmt->bind_result($google_created, $google_start, $google_end, $user_id, $google_recurrence);
        
        while($stmt->fetch()) {
        //if($stmt->fetch()) {
            $google_recurrence_unserialized = unserialize($google_recurrence);
            
            //print_r($google_recurrence_unserialized);
            
            $format = "Ymd\THis";
  
            $startDT = new DateTime($google_start);
            $dtStart = $startDT->format($format);
            
            $endDT = new DateTime($google_end);
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
            
            while($date = $recurrence->next()){
                
                //print($date['dtstart'] . " , " . $date['dtend'] . "<br/>");
                
                $eventLengthInSeconds = strtotime($date['dtend']) - strtotime($date['dtstart']);
                $eventLengthInHours = $eventLengthInSeconds/(60*60);
               
               //print($eventLengthInHours . "<br/>"); 
                
                $dtstartInDays = intval(strtotime($date['dtstart'])/(60*60*24));
                
                //print($dtstartInDays . "<br/>");
                
                if(strtotime($date['dtstart']) > 0 && strtotime($google_created) > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
                    $timeDifferenceInSeconds = strtotime($date['dtstart']) - strtotime($google_created);
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                    
                    //print($timeDifferenceInSeconds . " , " . $timeDifferenceInDays . "<br/>"); 
                
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
                $z++;
                
                if($z > 160) break;              
            }
            
            //print("Length " . $z . " Recurrence Finished<br/>");
            
            $z++;
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    
    // Make a histogram of number of days with that total number of events that day
    $numOfDaysWithThatEventCount = array();
    
    $dayEventCount = 0;
    
    foreach($eventsPerDay as $dtstartInDays  => $eventsThatDay) {
        $dayEventCount = count($eventsThatDay);
        
        //print($dtstartInDays . "");
        //print_r($eventsThatDay);
        //print("<br/>");
        
        if( array_key_exists($dayEventCount, $numOfDaysWithThatEventCount) ) {
            $numOfDaysWithThatEventCount[$dayEventCount]++;
        } else {
            $numOfDaysWithThatEventCount[$dayEventCount] = 1;
        }
    }
    
    $dayEventCount = 0;
    
    $maxDayEventCount = 0;
    
    print("dayEventCount, numOfDays<br/>");
    foreach($numOfDaysWithThatEventCount as $dayEventCount => $numOfDaysWithThatCount) {
        print($dayEventCount . "\t" . $numOfDaysWithThatCount . "<br/>");
        
        if($dayEventCount > $maxDayEventCount) {
            $maxDayEventCount = $dayEventCount;
        }
    }
    
    $createdDaysBeforeForThatDaysNumEvent = array();
    $relativePercentageSums = array();
    
    for($i = 0;$i < $maxTimeDifferenceInDays;$i++) {
        $relativePercentageSums[$i] = 0;
    }
    
    for($i = 0;$i < $maxDayEventCount;$i++) {
        $createdDaysBeforeForThatDaysNumEvent[$i] = array();
        
        for($j = 0;$j < $maxTimeDifferenceInDays;$j++) {
            $createdDaysBeforeForThatDaysNumEvent[$i][$j] = 0;
        }
    }
    print("createdDaysBeforeForThatDaysNumEvent<br/>");
    
    $numOfDaysWithEvents = 0;
    
    foreach($eventsPerDay as $dtstartInDays => $eventsThatDay) {
        
        sort($eventsThatDay);
        //print_r($eventsThatDaySorted);
        
        for($i = 0;$i < count($eventsThatDay);$i++) {
            $createdDaysBeforeForThatDaysNumEvent[$i][$eventsThatDay[$i]]++;
            // try adding $i/count($eventsThatDay) to 
            
            for($j = 0;$j <= $eventsThatDay[$i];$j++) {
                //$relativePercentageSums[$j] += ($i+1)/count($eventsThatDay);
                $relativePercentageSums[$j] += (1/count($eventsThatDay));
            }
        }
        
        $numOfDaysWithEvents++;
        
    }
    
    
    for($j = 0;$j < $maxTimeDifferenceInDays;$j++) {
    
        for($i = 0;$i < $maxDayEventCount;$i++) {
        
            print($createdDaysBeforeForThatDaysNumEvent[$i][$j] . "\t");
            
        }
        
        print("<br/>");
    }
    
    print("relativePercentageSums<br/>");
    
    for($i = 0;$i < $maxTimeDifferenceInDays;$i++) {
        // consider calculating the relativePercentageSums array for each user and then averaging those. this will normalize the influence of each person instead of the current approach which could let a person with a high number of events dominate this data.
        print( ($relativePercentageSums[$i]/$numOfDaysWithEvents) . "<br/>");
            
    }
    
}    
else {    
?>

<form action="dataAnalysisPerDay.php" method="POST">
  <p>  
    password:<input type="password" name="password">
  </p>
</form>   

<?php
}
?>