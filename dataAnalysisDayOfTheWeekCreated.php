<?php
    
    require_once('recurrence.php');            
    require('getEvents.php');
    require('getEventsPerDay.php');
    
    function processEventForDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreatedCount, &$dayOfTheWeekEventStartedCount, &$monthOfTheYearEventCreatedCount, $google_start, $google_end, $google_created) {
        $startUnixTimestamp = strtotime($google_start);
        $endUnixTimestamp = strtotime($google_end);
        $createdUnixTimestamp = strtotime($google_created);    
        
        $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
        $eventLengthInHours = $eventLengthInSeconds/(60*60);
        
        $dtstartInDays = intval($startUnixTimestamp/(60*60*24));
        
        // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
        if($startUnixTimestamp > 0 && $createdUnixTimestamp > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
            $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
            $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
            
            if($timeDifferenceInDays >= 0) {
                $dayFormat = "N";
                $monthFormat = "n";
                    
                $google_createdDT = new DateTime($google_created);
                $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                $google_created_MonthOfTheYear = $google_createdDT->format($monthFormat);
                
                $dayOfTheWeekEventCreatedCount[$google_created_DayOfTheWeek]++;
                $monthOfTheYearEventCreatedCount[$google_created_MonthOfTheYear]++;

                
                $google_startDT = new DateTime($google_start);
                $google_start_DayOfTheWeek = $google_startDT->format($dayFormat);
                    
                $dayOfTheWeekEventStartedCount[$google_start_DayOfTheWeek]++;
                    
            }
        }
    }
    
    function normalizeAndSerializeDayOfTheWeek(&$dayOfTheWeek) {
        $dayOfTheWeekSum = 0;
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekSum += $dayOfTheWeek[$i];         
        }
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeek[$i] /= $dayOfTheWeekSum;        
        }
        
        $dayOfTheWeekSerialized = serialize($dayOfTheWeek);
        
        return $dayOfTheWeekSerialized;
    }
    
    function normalizeAndSerializeMonthOfTheYear(&$monthOfTheYear) {
        $monthOfTheYearSum = 0;
        
        for($i = 1;$i <= 12;$i++) {
            $monthOfTheYearSum += $monthOfTheYear[$i];         
        }
        
        for($i = 1;$i <= 12;$i++) {
            $monthOfTheYear[$i] /= $monthOfTheYearSum;        
        }
        
        $monthOfTheYearSerialized = serialize($monthOfTheYear);
        
        return $monthOfTheYearSerialized;
    }
    
    function getDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreatedCount, &$dayOfTheWeekEventStartedCount, &$monthOfTheYearEventCreatedCount, $events, $recurring) {

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
                    
                    processEventForDayOfTheWeekEventCreated($dayOfTheWeekEventCreatedCount,$dayOfTheWeekEventStartedCount,
                    $monthOfTheYearEventCreatedCount,                    $date['dtstart'], $date['dtend'], $event["google_created"]);
                    
                    $z++;
                    
                    if($z > 160) break;              
                }
            
            }
            
        } else {
            foreach($events as $event) {
                processEventForDayOfTheWeekEventCreated($dayOfTheWeekEventCreatedCount, $dayOfTheWeekEventStartedCount, $monthOfTheYearEventCreatedCount, $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }

            
    }
    
    
    $users = array();
    
    $user_ids = getUserIds();
    
    foreach($user_ids as $user_id) {    
    
        $nonrecurringEvents = getEventsByUser(false,$user_id);
        $recurringEvents = getEventsByUser(true,$user_id);
            
        $dayOfTheWeekNonreccurringEventCreatedCount = array();
        $dayOfTheWeekReccurringEventCreatedCount = array();        
        $dayOfTheWeekNonreccurringAndReccurringEventCreatedCount = array();
        
        $dayOfTheWeekNonreccurringEventStartedCount = array();
        $dayOfTheWeekReccurringEventStartedCount = array();        
        $dayOfTheWeekNonreccurringAndReccurringEventStartedCount = array();
        
        $monthOfTheYearNonreccurringEventCreatedCount = array();
        $monthOfTheYearReccurringEventCreatedCount = array();        
        $monthOfTheYearNonreccurringAndReccurringEventCreatedCount = array();
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringEventCreatedCount[$i] = 0;
            $dayOfTheWeekReccurringEventCreatedCount[$i] = 0;    

            $dayOfTheWeekNonreccurringEventStartedCount[$i] = 0;
            $dayOfTheWeekReccurringEventStartedCount[$i] = 0;
        }
        
        for($i = 1;$i <= 12;$i++) {
            $monthOfTheYearNonreccurringEventCreatedCount[$i] = 0;
            $monthOfTheYearReccurringEventCreatedCount[$i] = 0;
        }
        
        getDayOfTheWeekEventCreated($dayOfTheWeekNonreccurringEventCreatedCount, $dayOfTheWeekNonreccurringEventStartedCount, $monthOfTheYearNonreccurringEventCreatedCount, $nonrecurringEvents, false);
        getDayOfTheWeekEventCreated($dayOfTheWeekReccurringEventCreatedCount, $dayOfTheWeekReccurringEventStartedCount, $monthOfTheYearReccurringEventCreatedCount, $recurringEvents, true);
        
        for($i = 1;$i <= 7;$i++) {
            $dayOfTheWeekNonreccurringAndReccurringEventCreatedCount[$i] = $dayOfTheWeekNonreccurringEventCreatedCount[$i] +  $dayOfTheWeekReccurringEventCreatedCount[$i];

            $dayOfTheWeekNonreccurringAndReccurringEventStartedCount[$i] =   $dayOfTheWeekNonreccurringEventStartedCount[$i] +             $dayOfTheWeekReccurringEventStartedCount[$i];
        
        }
        
        for($i = 1;$i <= 12;$i++) {
            $monthOfTheYearNonreccurringAndReccurringEventCreatedCount[$i] = $monthOfTheYearNonreccurringEventCreatedCount[$i] +
            $monthOfTheYearReccurringEventCreatedCount[$i];
        }
        
        $dayOfTheWeekNonreccurringEventCreatedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekNonreccurringEventCreatedCount);
        $dayOfTheWeekReccurringEventCreatedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekReccurringEventCreatedCount);
        $dayOfTheWeekNonreccurringAndReccurringEventCreatedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekNonreccurringAndReccurringEventCreatedCount);
        
        $dayOfTheWeekNonreccurringEventStartedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekNonreccurringEventStartedCount);
        $dayOfTheWeekReccurringEventStartedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekReccurringEventStartedCount);
        $dayOfTheWeekNonreccurringAndReccurringEventStartedCountSerialized = normalizeAndSerializeDayOfTheWeek($dayOfTheWeekNonreccurringAndReccurringEventStartedCount);
        
        
        $monthOfTheYearNonreccurringEventCreatedCountSerialized = normalizeAndSerializeMonthOfTheYear($monthOfTheYearNonreccurringEventCreatedCount);
        $monthOfTheYearReccurringEventCreatedCountSerialized = normalizeAndSerializeMonthOfTheYear($monthOfTheYearReccurringEventCreatedCount);
        $monthOfTheYearNonreccurringAndReccurringEventCreatedCountSerialized = normalizeAndSerializeMonthOfTheYear($monthOfTheYearNonreccurringAndReccurringEventCreatedCount);
        
        
        
        require('db/config.php');
        $mysqli = new mysqli($host, $username, $password, $db);
        if ($stmt = $mysqli->prepare("INSERT INTO `data_analysis` (`user_id`, `data_analysis_type`, `nonrecurring_included`, `recurring_included`, `count_or_length`, `array_serialized`) VALUES (?,?,?,?,?,?);")){
            
            $user_id = intval($user_id);
            $data_analysis_type = "day_of_the_week_created";
            $count_or_length = 0;
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekNonreccurringEventCreatedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 0;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekReccurringEventCreatedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 1;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekNonreccurringAndReccurringEventCreatedCountSerialized);
            $stmt->execute();
            
            ////////////////////////////////////////////////////////////
            
            $data_analysis_type = "day_of_the_week_started";
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekNonreccurringEventStartedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 0;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekReccurringEventStartedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 1;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $dayOfTheWeekNonreccurringAndReccurringEventStartedCountSerialized);
            $stmt->execute();
            
            
            $data_analysis_type = "month_of_the_year_created";
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $monthOfTheYearNonreccurringEventCreatedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 0;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $monthOfTheYearReccurringEventCreatedCountSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 1;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $monthOfTheYearNonreccurringAndReccurringEventCreatedCountSerialized);
            $stmt->execute();
            
           
            
            
            
            
            $stmt->close();

        }
           
        $mysqli->close();
        
        //$dayOfTheWeekEventCreatedUnserialized = unserialize($dayOfTheWeekEventCreatedSerialized);
        
        //print_r($dayOfTheWeekEventCreatedUnserialized);
    }
    
?>