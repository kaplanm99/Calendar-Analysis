<?php

require_once('recurrence.php');            

if( isset($_POST['password']) && $_POST['password'] == "pilot_calendar_study_results!!!!" ) {    

    $eventsCreatedDaysBefore = array();
    $eventLengthSumsCreatedDaysBefore = array();
    
    $dayOfTheWeekRecurrenceEventCreated = array();
    $dayOfTheWeekRecurrenceEventStart = array();
    
    $monthOfTheYearRecurrenceEventCreated = array();
    $monthOfTheYearRecurrenceEventStart = array();
    
    for($i = 1;$i <= 7;$i++) {
        $dayOfTheWeekRecurrenceEventCreated[$i] = 0;
        $dayOfTheWeekRecurrenceEventStart[$i] = 0;
    }
    
    for($i = 1;$i <= 12;$i++) {
        $monthOfTheYearRecurrenceEventCreated[$i] = 0;
        $monthOfTheYearRecurrenceEventStart[$i] = 0;
    }
    
    $users = array();
    
    $maxDay = -1000000;
    $minDay = 1000000;
    
    $recurrenceEventSum = 0;
    $recurrenceSequenceCount = 0;
    
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
                
                $eventLengthInHours = (strtotime($date['dtend']) - strtotime($date['dtstart']))/(60*60);
                
                //print($eventLengthInHours . "<br/>"); 
                                
                if(strtotime($date['dtstart']) > 0 && strtotime($google_created) > 0 && $eventLengthInHours > 0 && $eventLengthInHours < 24) {
                    $timeDifferenceInSeconds = strtotime($date['dtstart']) - strtotime($google_created);
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                    
                    //print($timeDifferenceInSeconds . " , " . $timeDifferenceInDays . "<br/>"); 
                
                    if($timeDifferenceInDays >= 0) {
                        if( array_key_exists($timeDifferenceInDays, $eventsCreatedDaysBefore) ) {
                            $eventsCreatedDaysBefore[$timeDifferenceInDays]++;
                            $eventLengthSumsCreatedDaysBefore[$timeDifferenceInDays] += $eventLengthInHours;
                        } else {
                            $eventsCreatedDaysBefore[$timeDifferenceInDays] = 1;
                            $eventLengthSumsCreatedDaysBefore[$timeDifferenceInDays] = $eventLengthInHours;
                        }
                        
                        if($timeDifferenceInDays > $maxDay) {
                            $maxDay = $timeDifferenceInDays;
                        }
                        
                        if($timeDifferenceInDays < $minDay) {
                            $minDay = $timeDifferenceInDays;
                            //print($google_start . " " . $google_created . "<br/>");
                        }
                        
                        $dayFormat = "N";
                        $monthFormat = "n";
  
                        $google_createdDT = new DateTime($google_created);
                        $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                        $google_created_MonthOfTheYear = $google_createdDT->format($monthFormat);
                        
                        $dayOfTheWeekRecurrenceEventCreated[$google_created_DayOfTheWeek]++;
                        $monthOfTheYearRecurrenceEventCreated[$google_created_MonthOfTheYear]++;
                        
                        $reStartDT = new DateTime($date['dtstart']);
                        $reStartDT_DayOfTheWeek = $reStartDT->format($dayFormat);
                        $reStartDT_MonthOfTheYear = $reStartDT->format($monthFormat);
                                                
                        $dayOfTheWeekRecurrenceEventStart[$reStartDT_DayOfTheWeek]++;
                        $monthOfTheYearRecurrenceEventStart[$reStartDT_MonthOfTheYear]++;
                        
                    }
                }
                $z++;
                
                if($z > 120) break;              
            }
            
            print("Length " . $z . " Recurrence Finished<br/>");
            
            $z++;
            $recurrenceEventSum += $z;
            $recurrenceSequenceCount++;
            
            
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    $recurrenceAVGEventPerSequence = $recurrenceEventSum/$recurrenceSequenceCount;
    
    print("recurrenceAVGEventPerSequence = " . $recurrenceAVGEventPerSequence . "<br/>");
    
    print("dayOfTheWeekRecurrenceEventCreated:<br/>");
    
    for($i = 1;$i <= 7;$i++) {
        print($dayOfTheWeekRecurrenceEventCreated[$i] . "<br/>");
    }
    
    print("<br/>");
    
    
    print("dayOfTheWeekRecurrenceEventStart:<br/>");
    
    for($i = 1;$i <= 7;$i++) {
        print($dayOfTheWeekRecurrenceEventStart[$i] . "<br/>");
    }
    
    print("<br/>");
    
    /////////////////////////
    
    print("monthOfTheYearRecurrenceEventCreated:<br/>");
    
    for($i = 1;$i <= 12;$i++) {
        print($monthOfTheYearRecurrenceEventCreated[$i] . "<br/>");
    }
    
    print("<br/>");
    
    print("monthOfTheYearRecurrenceEventStart:<br/>");
    
    for($i = 1;$i <= 12;$i++) {
        print($monthOfTheYearRecurrenceEventStart[$i] . "<br/>");
    }
    
    print("<br/>");
    /////////////////////////    
    
    for($i = $minDay;$i <= $maxDay;$i++) {
        if(! array_key_exists($i, $eventsCreatedDaysBefore) ) {
            $eventsCreatedDaysBefore[$i] = 0;
            $eventLengthSumsCreatedDaysBefore[$i] = 0;
        }
    }
    
    // calculate the by x days before value by looping in reverse order from above and summing event count and lengths of day values greater than x  
    $eventsCreatedByEndDaysBefore = array();
    $eventLengthSumsCreatedByEndDaysBefore = array();
    
    $eventsCreatedByEndDaysBefore[$maxDay+1] = 0;
    $eventLengthSumsCreatedByEndDaysBefore[$maxDay+1] = 0;
    
    
    for($i = $maxDay;$i >= $minDay;$i--) {
        $eventsCreatedByEndDaysBefore[$i] = $eventsCreatedByEndDaysBefore[$i+1]+$eventsCreatedDaysBefore[$i];
        
        $eventLengthSumsCreatedByEndDaysBefore[$i] = $eventLengthSumsCreatedByEndDaysBefore[$i+1]+$eventLengthSumsCreatedDaysBefore[$i];
    }
    
    print("the # of events scheduled on day x before the event occurs\tthe length of events scheduled on day x before the event occurs\tthe # of events scheduled by day x before the event occurs\tthe length of events scheduled by day x before the event occurs<br/>");
    
    for($i = $minDay;$i <= $maxDay;$i++) {
        print($eventsCreatedDaysBefore[$i] . "\t" . $eventLengthSumsCreatedDaysBefore[$i] . "\t" . $eventsCreatedByEndDaysBefore[$i] . "\t" . $eventLengthSumsCreatedByEndDaysBefore[$i] . "<br/>");
    }
}    
else {    
?>

<form action="dataAnalysisRecurrence.php" method="POST">
  <p>  
    password:<input type="password" name="password">
  </p>
</form>   

<?php
}
?>