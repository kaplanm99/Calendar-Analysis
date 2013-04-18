<?php
    
    require_once('recurrence.php');            
    require('getEvents.php');
    require('getEventsPerDay.php');
    
    function processEventForDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreatedCount, &$dayOfTheWeekEventStartedCount, &$monthOfTheYearEventCreatedCount, &$eventsPerDay, &$maxTimeDifferenceInDays, $google_start, $google_end, $google_created) {
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
    
    function normalizeAndSerializeInput(&$input) {
        $inputSum = 0;
        
        for($i = 1;$i <= count($input);$i++) {
            $inputSum += $input[$i];         
        }
        
        for($i = 1;$i <= count($input);$i++) {
            $input[$i] /= $inputSum;        
        }
        
        $inputSerialized = serialize($input);
        
        return $inputSerialized;
    }
    
    function calculateRelativePercentageSumsNormalizedAndSerialized(&$eventsPerDay, &$maxTimeDifferenceInDays){
        $relativePercentageSums = array();
        $numOfDaysWithEvents = 0;

        calculateRelativePercentageSums($relativePercentageSums, $numOfDaysWithEvents, $eventsPerDay, $maxTimeDifferenceInDays);

        $relativePercentageSumsNormalized = array();
        
        for($i = 0;$i < $maxTimeDifferenceInDays;$i++) {    
            $relativePercentageSumsNormalized[$i] = $relativePercentageSums[$i]/$numOfDaysWithEvents;
        }
        
        $relativePercentageSumsSerialized = serialize($relativePercentageSumsNormalized);
        
        return $relativePercentageSumsSerialized;
    }
    
    $users = array();
    
    $user_ids = getUserIds();
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);
    if ($stmt = $mysqli->prepare("INSERT INTO `data_analysis` (`user_id`, `data_analysis_type`, `nonrecurring_included`, `recurring_included`, `count_or_length`, `array_serialized`) VALUES (?,?,?,?,?,?);")){
            
        
    
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
            
            $eventsPerDay = array();
            $maxTimeDifferenceInDays = 0;

            $eventsPerDayR = array();
            $maxTimeDifferenceInDaysR = 0;
            
            $eventsPerDayNAndR = array();
            $maxTimeDifferenceInDaysNAndR = 0;
            
            foreach($nonrecurringEvents as $event) {
                processEventForDayOfTheWeekEventCreated($dayOfTheWeekNonreccurringEventCreatedCount, $dayOfTheWeekNonreccurringEventStartedCount, $monthOfTheYearNonreccurringEventCreatedCount, 
                $eventsPerDay, $maxTimeDifferenceInDays, $event["google_start"], $event["google_end"], $event["google_created"]);
            }
            
            foreach($recurringEvents as $event) {
                processEventForDayOfTheWeekEventCreated($dayOfTheWeekReccurringEventCreatedCount, $dayOfTheWeekReccurringEventStartedCount, $monthOfTheYearReccurringEventCreatedCount,
                $eventsPerDayR, $maxTimeDifferenceInDaysR,
                $event["google_start"], $event["google_end"], $event["google_created"]);
            }
            
            for($i = 1;$i <= 7;$i++) {
                $dayOfTheWeekNonreccurringAndReccurringEventCreatedCount[$i] = $dayOfTheWeekNonreccurringEventCreatedCount[$i] +  $dayOfTheWeekReccurringEventCreatedCount[$i];

                $dayOfTheWeekNonreccurringAndReccurringEventStartedCount[$i] =   $dayOfTheWeekNonreccurringEventStartedCount[$i] +             $dayOfTheWeekReccurringEventStartedCount[$i];
            
            }
            
            for($i = 1;$i <= 12;$i++) {
                $monthOfTheYearNonreccurringAndReccurringEventCreatedCount[$i] = $monthOfTheYearNonreccurringEventCreatedCount[$i] +
                $monthOfTheYearReccurringEventCreatedCount[$i];
            }
            
            $eventsPerDayNAndR = array();
            
            foreach($eventsPerDay as $dtstartInDays => $eventsThatDay) {
                foreach($eventsThatDay as $eventThatDay) {
                    $eventsPerDayNAndR[$dtstartInDays][] = $eventThatDay;
                }
            }
            
            foreach($eventsPerDayR as $dtstartInDays => $eventsThatDay) {
                foreach($eventsThatDay as $eventThatDay) {
                    $eventsPerDayNAndR[$dtstartInDays][] = $eventThatDay;
                }
            }
            
            if($maxTimeDifferenceInDays > $maxTimeDifferenceInDaysR) {
                $maxTimeDifferenceInDaysNAndR = $maxTimeDifferenceInDays;
            } else {
                $maxTimeDifferenceInDaysNAndR = $maxTimeDifferenceInDaysR;
            }
            
            
            $relativePercentageSumsSerialized = calculateRelativePercentageSumsNormalizedAndSerialized($eventsPerDay, $maxTimeDifferenceInDays);
            
            $relativePercentageSumsRSerialized = calculateRelativePercentageSumsNormalizedAndSerialized($eventsPerDayR, $maxTimeDifferenceInDaysR);

            $relativePercentageSumsNAndRSerialized = calculateRelativePercentageSumsNormalizedAndSerialized($eventsPerDayNAndR, $maxTimeDifferenceInDaysNAndR);
            
            
            $dayOfTheWeekNonreccurringEventCreatedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekNonreccurringEventCreatedCount);
            $dayOfTheWeekReccurringEventCreatedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekReccurringEventCreatedCount);
            $dayOfTheWeekNonreccurringAndReccurringEventCreatedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekNonreccurringAndReccurringEventCreatedCount);
            
            $dayOfTheWeekNonreccurringEventStartedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekNonreccurringEventStartedCount);
            $dayOfTheWeekReccurringEventStartedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekReccurringEventStartedCount);
            $dayOfTheWeekNonreccurringAndReccurringEventStartedCountSerialized = normalizeAndSerializeInput($dayOfTheWeekNonreccurringAndReccurringEventStartedCount);
            
            
            $monthOfTheYearNonreccurringEventCreatedCountSerialized = normalizeAndSerializeInput($monthOfTheYearNonreccurringEventCreatedCount);
            $monthOfTheYearReccurringEventCreatedCountSerialized = normalizeAndSerializeInput($monthOfTheYearReccurringEventCreatedCount);
            $monthOfTheYearNonreccurringAndReccurringEventCreatedCountSerialized = normalizeAndSerializeInput($monthOfTheYearNonreccurringAndReccurringEventCreatedCount);
            
            
            
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
            
            $data_analysis_type = "relative_percentage_sums";
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $relativePercentageSumsSerialized);
            $stmt->execute();
           
            $nonrecurring_included = 0;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $relativePercentageSumsRSerialized);
            $stmt->execute();
            
            $nonrecurring_included = 1;
            $recurring_included = 1;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $relativePercentageSumsNAndRSerialized);
            $stmt->execute();
           
        }
        
        $stmt->close();

    }
           
    $mysqli->close();
?>