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
    
    function normalizeAndSerializeInputKnownLength(&$input, $length) {
        for($i = 0;$i <= $length;$i++) {
            if(! array_key_exists($i, $input) ) {
                $input[$i] = 0;
            }
        }
        
        $inputSum = 0;
        
        for($i = 0;$i <= $length;$i++) {
            $inputSum += $input[$i];         
        }
        
        for($i = 0;$i <= $length;$i++) {
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
    
    function calcNumOfDaysWithThatEventCount($eventsPerDay){
        $numOfDaysWithThatEventCount = array();
        
        $maxEventsInADay = 1;
        
        foreach($eventsPerDay as $dtstartInDays  => $eventsThatDay) {
            if(count($eventsThatDay) > $maxEventsInADay) {
                $maxEventsInADay = count($eventsThatDay);
            }
        }
        
        for($i = 1;$i <= $maxEventsInADay;$i++) {
            $numOfDaysWithThatEventCount[$i] = 0;         
        }
        
        foreach($eventsPerDay as $dtstartInDays  => $eventsThatDay) {
            $numOfDaysWithThatEventCount[count($eventsThatDay)]++;
        }

        foreach($numOfDaysWithThatEventCount as $dayEventCount => $numOfDaysWithThatCount) {
            $numOfDaysWithThatEventCount[$dayEventCount] = $numOfDaysWithThatCount/(count($eventsPerDay));
        }
        
        return (serialize($numOfDaysWithThatEventCount));
    }
    
    function generateAndStoreEventsCreatedDaysBefore($nonrecurringEvents,  $recurringEvents, $processEventFunction, $user_id, $data_analysis_type, $count_or_length, &$stmt) {
        $eventsCreatedDaysBefore = array();
        $maxDay = -1000000;
        
        foreach($nonrecurringEvents as $event) {
            if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                $processEventFunction($eventsCreatedDaysBefore, $maxDay, 
                $event["google_start"], $event["google_end"], $event["google_created"]);
                /*
                $startUnixTimestamp = strtotime($event["google_start"]);
                $createdUnixTimestamp = strtotime($event["google_created"]);    
                
                $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
                $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                    
                if( array_key_exists($timeDifferenceInDays, $eventsCreatedDaysBefore) ) {
                    $eventsCreatedDaysBefore[$timeDifferenceInDays]++;
                } else {
                    $eventsCreatedDaysBefore[$timeDifferenceInDays] = 1;
                }
                
                if($timeDifferenceInDays > $maxDay) {
                    $maxDay = $timeDifferenceInDays;
                }
                */
            }
        }
        
        $eventsCreatedDaysBeforeSerialized = normalizeAndSerializeInputKnownLength($eventsCreatedDaysBefore, $maxDay);
        
        $nonrecurring_included = 1;
        $recurring_included = 0;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type,$nonrecurring_included, $recurring_included, $count_or_length, $eventsCreatedDaysBeforeSerialized);
        $stmt->execute();
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
    
    function generateEventsPerDayCount($nonrecurringEvents,  $recurringEvents, $processEventFunction, &$eventsPerDay, &$maxTimeDifferenceInDays, &$eventsPerDayR, &$maxTimeDifferenceInDaysR, &$eventsPerDayNAndR, &$maxTimeDifferenceInDaysNAndR) {
        
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
    }
    
    function generateAndStoreRelativePercentageSums($eventsPerDay, $maxTimeDifferenceInDays, $eventsPerDayR, $maxTimeDifferenceInDaysR, $eventsPerDayNAndR, $maxTimeDifferenceInDaysNAndR, $user_id, $data_analysis_type, $count_or_length, &$stmt) {
        
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
    
    function generateAndStoreNumOfDaysWithThatEventAmt($eventsPerDay, $eventsPerDayR, $eventsPerDayNAndR, $user_id, $data_analysis_type, $count_or_length, &$stmt) {
        $numOfDaysWithThatEventCountNSerialized = calcNumOfDaysWithThatEventCount($eventsPerDay);

        $numOfDaysWithThatEventCountRSerialized = calcNumOfDaysWithThatEventCount($eventsPerDayR);
        
        $numOfDaysWithThatEventCountNAndRSerialized = calcNumOfDaysWithThatEventCount($eventsPerDayNAndR);
        
        $nonrecurring_included = 1;
        $recurring_included = 0;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $numOfDaysWithThatEventCountNSerialized);
        $stmt->execute();
       
        $nonrecurring_included = 0;
        $recurring_included = 1;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $numOfDaysWithThatEventCountRSerialized);
        $stmt->execute();
        
        $nonrecurring_included = 1;
        $recurring_included = 1;
        
        $stmt->bind_param('isiiis', $user_id, $data_analysis_type, $nonrecurring_included, $recurring_included, $count_or_length, $numOfDaysWithThatEventCountNAndRSerialized);
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
            
            $eventsPerDay = array();
            $maxTimeDifferenceInDays = 0;

            $eventsPerDayR = array();
            $maxTimeDifferenceInDaysR = 0;
        
            $eventsPerDayNAndR = array();
            $maxTimeDifferenceInDaysNAndR = 0;
            
            generateEventsPerDayCount($nonrecurringEvents,  $recurringEvents, $processEventForRelativePercentageSumsCount, &$eventsPerDay, &$maxTimeDifferenceInDays, &$eventsPerDayR, &$maxTimeDifferenceInDaysR, &$eventsPerDayNAndR, &$maxTimeDifferenceInDaysNAndR);
            
            generateAndStoreRelativePercentageSums($eventsPerDay, $maxTimeDifferenceInDays, $eventsPerDayR, $maxTimeDifferenceInDaysR, $eventsPerDayNAndR, $maxTimeDifferenceInDaysNAndR, $user_id, "relative_percentage_sums", 0, $stmt);
            
            generateAndStoreNumOfDaysWithThatEventAmt($eventsPerDay, $eventsPerDayR, $eventsPerDayNAndR, $user_id, "num_of_days_with_that_event_amt", 0, &$stmt);
            /*
            $processEventForEventsCreatedDaysBeforeStartCount = 
                function (&$eventsCreatedDaysBeforeStartCount, &$maxDay, $google_start, $google_end, $google_created) {
                    
                    $startUnixTimestamp = strtotime($google_start);
                    $createdUnixTimestamp = strtotime($google_created);    
                    
                    $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                        
                    if( array_key_exists($timeDifferenceInDays, $eventsCreatedDaysBeforeStartCount) ) {
                        $eventsCreatedDaysBeforeStartCount[$timeDifferenceInDays]++;
                    } else {
                        $eventsCreatedDaysBeforeStartCount[$timeDifferenceInDays] = 1;
                    }
                    
                    if($timeDifferenceInDays > $maxDay) {
                        $maxDay = $timeDifferenceInDays;
                    }
                                        
                };
            */
            //generateAndStoreTimeData($maxTimeDifferenceInDaysNAndR, $nonrecurringEvents,  $recurringEvents, $processEventForEventsCreatedDaysBeforeStartCount, $user_id, "event_created_days_before_start_count", 0, $stmt);
            
            $processEventForEventsCreatedDaysBeforeCount = 
                function (&$eventsCreatedDaysBefore, &$maxDay, $google_start, $google_end, $google_created) {
                     $startUnixTimestamp = strtotime($event["google_start"]);
                    $createdUnixTimestamp = strtotime($event["google_created"]);    
                    
                    $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                        
                    if( array_key_exists($timeDifferenceInDays, $eventsCreatedDaysBefore) ) {
                        $eventsCreatedDaysBefore[$timeDifferenceInDays]++;
                    } else {
                        $eventsCreatedDaysBefore[$timeDifferenceInDays] = 1;
                    }
                    
                    if($timeDifferenceInDays > $maxDay) {
                        $maxDay = $timeDifferenceInDays;
                    }
                };
            
            
            generateAndStoreEventsCreatedDaysBefore($nonrecurringEvents,  $recurringEvents, $processEventForEventsCreatedDaysBeforeCount, $user_id, "events_created_days_before", 0, &$stmt);
            
            /*
            $eventsCreatedDaysBefore = array();
            $maxDay = -1000000;
            
            foreach($nonrecurringEvents as $event) {
                if(eventPassesFilter($event["google_start"], $event["google_end"], $event["google_created"])){
                    $startUnixTimestamp = strtotime($event["google_start"]);
                    $createdUnixTimestamp = strtotime($event["google_created"]);    
                    
                    $timeDifferenceInSeconds = $startUnixTimestamp - $createdUnixTimestamp;
                    $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                        
                    if( array_key_exists($timeDifferenceInDays, $eventsCreatedDaysBefore) ) {
                        $eventsCreatedDaysBefore[$timeDifferenceInDays]++;
                    } else {
                        $eventsCreatedDaysBefore[$timeDifferenceInDays] = 1;
                    }
                    
                    if($timeDifferenceInDays > $maxDay) {
                        $maxDay = $timeDifferenceInDays;
                    }
                }
            }
            
            $eventsCreatedDaysBeforeSum = 0;
            
            for($i = 0;$i <= $maxDay;$i++) {
                if(! array_key_exists($i, $eventsCreatedDaysBefore) ) {
                    $eventsCreatedDaysBefore[$i] = 0;
                } else {
                    $eventsCreatedDaysBeforeSum += $eventsCreatedDaysBefore[$i];
                }
            }
            
            for($i = 0;$i <= $maxDay;$i++) {
                $eventsCreatedDaysBefore[$i] /= $eventsCreatedDaysBeforeSum;
            }
            
            $eventsCreatedDaysBeforeSerialized = serialize($eventsCreatedDaysBefore);
            
            $nonrecurring_included = 1;
            $recurring_included = 0;
            $data_analysis_type = "events_created_days_before";
            $count_or_length = 0;
            
            $stmt->bind_param('isiiis', $user_id, $data_analysis_type,$nonrecurring_included, $recurring_included, $count_or_length, $eventsCreatedDaysBeforeSerialized);
            $stmt->execute();
            */
        }
        
        $stmt->close();

    }
           
    $mysqli->close();
?>