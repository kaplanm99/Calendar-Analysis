<?php
    
    require_once('recurrence.php');            
    require('getEvents.php');
    require('getEventsPerDay.php');
    
    function eventPassesFilter($google_start, $google_end, $google_created){
    
        $startUnixTimestamp = strtotime($google_start);
        $endUnixTimestamp = strtotime($google_end);
        $createdUnixTimestamp = strtotime($google_created);    
        
        $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
        $eventLengthInHours = $eventLengthInSeconds/(60*60);
        
        $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
        $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
        
        // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
        return ($startUnixTimestamp < strtotime("now") && $startUnixTimestamp > 0 && $createdUnixTimestamp > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24 && $timeDifferenceInDays >= 0);
    
    }
    
    /*
    function processEventForDayOfTheWeekEventCreated(&$dayOfTheWeekEventCreatedCount, &$dayOfTheWeekEventStartedCount, &$monthOfTheYearEventCreatedCount, &$eventsPerDay, &$maxTimeDifferenceInDays, $google_start, $google_end, $google_created) {
        $startUnixTimestamp = strtotime($google_start);
        $endUnixTimestamp = strtotime($google_end);
        $createdUnixTimestamp = strtotime($google_created);    
        
        $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
        $eventLengthInHours = $eventLengthInSeconds/(60*60);
        
        $dtstartInDays = intval($startUnixTimestamp/(60*60*24));
        
        // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
        if($startUnixTimestamp < strtotime("now") && $startUnixTimestamp > 0 && $createdUnixTimestamp > 0 && $eventLengthInSeconds > 0 && $eventLengthInHours < 24) {
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
    */
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
    
    function generateAndStoreTimeData($arrayLength, $nonrecurringEvents,  $recurringEvents, $processEventFunction, $user_id, $data_analysis_type, $count_or_length, &$stmt) {
        $nonreccurringData = array();
        $reccurringData = array();        
        $nonreccurringAndReccurringData = array();
        
        for($i = 1;$i <= $arrayLength;$i++) {
            $nonreccurringData[$i] = 0;
            $reccurringData[$i] = 0;           
        }
        
        foreach($nonrecurringEvents as $event) {
            if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                $processEventFunction($nonreccurringData, 
                $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }

        foreach($recurringEvents as $event) {
            if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                $processEventFunction($reccurringData,
                $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }
        
        for($i = 1;$i <= $arrayLength;$i++) {
            $nonreccurringAndReccurringData[$i] = $nonreccurringData[$i] +
            $reccurringData[$i];
        }
        
        $nonreccurringDataSerialized = normalizeAndSerializeInput($nonreccurringData);
        $reccurringDataSerialized = normalizeAndSerializeInput($reccurringData);
        $nonreccurringAndReccurringDataSerialized = normalizeAndSerializeInput($nonreccurringAndReccurringData);
        
        $nonrecurring_included = 1;
        $recurring_included = 0;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $nonreccurringDataSerialized);
        $stmt->execute();
        
        $nonrecurring_included = 0;
        $recurring_included = 1;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $reccurringDataSerialized);
        $stmt->execute();
        
        $nonrecurring_included = 1;
        $recurring_included = 1;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $nonreccurringAndReccurringDataSerialized);
        $stmt->execute();
        
    }
    
    function generateAndStoreRelativePercentageSums($nonrecurringEvents,  $recurringEvents, $processEventFunction, $user_id, $data_analysis_type, $count_or_length, &$stmt) {
        
        $eventsPerDay = array();
        $maxTimeDifferenceInDays = 0;

        $eventsPerDayR = array();
        $maxTimeDifferenceInDaysR = 0;
        
        $eventsPerDayNAndR = array();
        $maxTimeDifferenceInDaysNAndR = 0;
        
        foreach($nonrecurringEvents as $event) {
            if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                $processEventFunction($eventsPerDay, $maxTimeDifferenceInDays, 
                $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }

        foreach($recurringEvents as $event) {
            if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                $processEventFunction($eventsPerDayR, $maxTimeDifferenceInDaysR,
                $event["google_start"], $event["google_end"], $event["google_created"]);
            }
        }
        
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
    
    
    $users = array();
    $user_ids[] = 11;
    //$user_ids = getUserIds();
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);
    if ($stmt = $mysqli->prepare("INSERT INTO `data_analysis` (`user_id`, `data_analysis_type`, `nonrecurring_included`, `recurring_included`, `count_or_length`, `array_serialized`) VALUES (?,?,?,?,?,?);")){
            
        
    
        foreach($user_ids as $user_id) {    
    
            $user_id = intval($user_id);
    
            $nonrecurringEvents = getEventsByUser(false,$user_id);
            $recurringEvents = getEventsByUser(true,$user_id);
            
            $processEventForDayOfTheWeekEventCreatedCount = 
                function (&$dayOfTheWeekEventCreatedCount, $google_start, $google_end, $google_created) {
                    $dayFormat = "N";
                    
                    $google_createdDT = new DateTime($google_created);
                    $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                    $dayOfTheWeekEventCreatedCount[$google_created_DayOfTheWeek]++;                
                };
            
            generateAndStoreTimeData(7, $nonrecurringEvents,  $recurringEvents, $processEventForDayOfTheWeekEventCreatedCount, $user_id, "day_of_the_week_created", 0, $stmt);
            
            $processEventForDayOfTheWeekEventCreatedLength = 
                function (&$dayOfTheWeekEventCreatedLength, $google_start, $google_end, $google_created) {
                    $startUnixTimestamp = strtotime($google_start);
                    $endUnixTimestamp = strtotime($google_end);
                    
                    $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
                    $eventLengthInHours = $eventLengthInSeconds/(60*60);
        
                    $dayFormat = "N";
                    
                    $google_createdDT = new DateTime($google_created);
                    $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                    $dayOfTheWeekEventCreatedLength[$google_created_DayOfTheWeek] += $eventLengthInHours;                
                };
            
            generateAndStoreTimeData(7, $nonrecurringEvents,  $recurringEvents, $processEventForDayOfTheWeekEventCreatedLength, $user_id, "day_of_the_week_created", 1, $stmt);
            
            $processEventForDayOfTheWeekEventStartedCount = 
                function (&$dayOfTheWeekEventStartedCount, $google_start, $google_end, $google_created) {
                    $dayFormat = "N";
                    
                    $google_startDT = new DateTime($google_start);
                    $google_start_DayOfTheWeek = $google_startDT->format($dayFormat);
                    
                    $dayOfTheWeekEventStartedCount[$google_start_DayOfTheWeek]++;                
                };
            
            generateAndStoreTimeData(7, $nonrecurringEvents,  $recurringEvents, $processEventForDayOfTheWeekEventStartedCount, $user_id, "day_of_the_week_started", 0, $stmt);
            
            $processEventForDayOfTheWeekEventStartedLength = 
                function (&$dayOfTheWeekEventStartedLength, $google_start, $google_end, $google_created) {
                    $startUnixTimestamp = strtotime($google_start);
                    $endUnixTimestamp = strtotime($google_end);
                    
                    $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
                    $eventLengthInHours = $eventLengthInSeconds/(60*60);
                    
                    $dayFormat = "N";
                    
                    $google_startDT = new DateTime($google_start);
                    $google_start_DayOfTheWeek = $google_startDT->format($dayFormat);
                    
                    $dayOfTheWeekEventStartedLength[$google_start_DayOfTheWeek] += $eventLengthInHours;                
                };
            
            generateAndStoreTimeData(7, $nonrecurringEvents,  $recurringEvents, $processEventForDayOfTheWeekEventStartedLength, $user_id, "day_of_the_week_started", 1, $stmt);
            
            $processEventForMonthOfTheYearCreatedCount = 
                function (&$monthOfTheYearEventCreatedCount, $google_start, $google_end, $google_created) {
                    $monthFormat = "n";
                    
                    $google_createdDT = new DateTime($google_created);
                    $google_created_MonthOfTheYear = $google_createdDT->format($monthFormat);
                    
                    $monthOfTheYearEventCreatedCount[$google_created_MonthOfTheYear]++;              
                };
            
            generateAndStoreTimeData(12, $nonrecurringEvents,  $recurringEvents, $processEventForMonthOfTheYearCreatedCount, $user_id, "month_of_the_year_created", 0, $stmt);
            
            $processEventForMonthOfTheYearCreatedLength = 
                function (&$monthOfTheYearCreatedLength, $google_start, $google_end, $google_created)
                {
                    $startUnixTimestamp = strtotime($google_start);
                    $endUnixTimestamp = strtotime($google_end);
                    
                    $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
                    $eventLengthInHours = $eventLengthInSeconds/(60*60);
                    
                    $monthFormat = "n";
                    
                    $google_createdDT = new DateTime($google_created);
                    $google_created_MonthOfTheYear = $google_createdDT->format($monthFormat);
                    
                    $monthOfTheYearCreatedLength[$google_created_MonthOfTheYear] += $eventLengthInHours;              
                };
            
            generateAndStoreTimeData(12, $nonrecurringEvents,  $recurringEvents, $processEventForMonthOfTheYearCreatedLength, $user_id, "month_of_the_year_created", 1, $stmt);
            
            $processEventForMonthOfTheYearEventStartedCount = 
                function (&$monthOfTheYearEventStartedCount, $google_start, $google_end, $google_created) {
                    $monthFormat = "n";
                    
                    $google_startDT = new DateTime($google_start);
                    $google_start_MonthOfTheYear = $google_startDT->format($monthFormat);
                    
                    $monthOfTheYearEventStartedCount[$google_start_MonthOfTheYear]++;
                                    
                };
            
            generateAndStoreTimeData(12, $nonrecurringEvents,  $recurringEvents, $processEventForMonthOfTheYearEventStartedCount, $user_id, "month_of_the_year_started", 0, $stmt);
            
            $processEventForMonthOfTheYearEventStartedLength = 
                function (&$monthOfTheYearEventStartedLength, $google_start, $google_end, $google_created) {
                    $startUnixTimestamp = strtotime($google_start);
                    $endUnixTimestamp = strtotime($google_end);
                    
                    $eventLengthInSeconds = $endUnixTimestamp - $startUnixTimestamp;
                    $eventLengthInHours = $eventLengthInSeconds/(60*60);
                    
                    $monthFormat = "n";
                    
                    $google_startDT = new DateTime($google_start);
                    $google_start_MonthOfTheYear = $google_startDT->format($monthFormat);
                    
                    $monthOfTheYearEventStartedLength[$google_start_MonthOfTheYear] += $eventLengthInHours;
                                    
                };
            
            generateAndStoreTimeData(12, $nonrecurringEvents,  $recurringEvents, $processEventForMonthOfTheYearEventStartedLength, $user_id, "month_of_the_year_started", 1, $stmt);
            
            $processEventForRelativePercentageSumsCount = 
                function (&$eventsPerDay, &$maxTimeDifferenceInDays, $google_start, $google_end, $google_created)
                {
                    $startUnixTimestamp = strtotime($google_start);
                    $createdUnixTimestamp = strtotime($google_created);    
                    
                    $dtstartInDays = intval($startUnixTimestamp/(60*60*24));
                    
                    $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                        
                        
                    if(! array_key_exists($dtstartInDays, $eventsPerDay) ) {
                        $eventsPerDay[$dtstartInDays] = array();
                    }
                    
                    $eventsPerDay[$dtstartInDays][] = $timeDifferenceInDays;
                    
                    if($timeDifferenceInDays > $maxTimeDifferenceInDays) {
                        $maxTimeDifferenceInDays = $timeDifferenceInDays;
                    }                                    
                };
            
            generateAndStoreRelativePercentageSums($nonrecurringEvents,  $recurringEvents, $processEventForRelativePercentageSumsCount, $user_id, "relative_percentage_sums", 0, $stmt);
            
            
            
        }
        
        $stmt->close();

    }
           
    $mysqli->close();
?>