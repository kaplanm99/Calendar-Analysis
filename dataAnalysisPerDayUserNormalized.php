<?php

require_once('recurrence.php');            
require('getEvents.php');
require('getEventsPerDay.php');

function printNumOfDaysWithThatEventCount($eventsPerDay, $user_id){
    $numOfDaysWithThatEventCount = array();
    
    foreach($eventsPerDay as $dtstartInDays  => $eventsThatDay) {
        $dayEventCount = count($eventsThatDay);
        
        if( array_key_exists($dayEventCount, $numOfDaysWithThatEventCount) ) {
            $numOfDaysWithThatEventCount[$dayEventCount]++;
        } else {
            $numOfDaysWithThatEventCount[$dayEventCount] = 1;
        }
    }

    print("user_id = $user_id : dayEventCount, numOfDays<br/>");
    foreach($numOfDaysWithThatEventCount as $dayEventCount => $numOfDaysWithThatCount) {
        print($dayEventCount . "\t" . $numOfDaysWithThatCount . "<br/>");
    }
}

if( isset($_POST['password']) && $_POST['password'] == "pilot_calendar_study_results!!!!" ) {    

    $relativePercentageSumsUserNormalized = array();

    $user_ids = getUserIds();
    
    $usersWithEventsCount = 0;
    
    foreach($user_ids as $user_id) {    
    
        $eventsPerDay = array();
        $maxTimeDifferenceInDays = 0;
        
        $nonrecurringEvents = getEventsByUser(false,$user_id);
        $recurringEvents = getEventsByUser(true,$user_id);
            
        getEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $nonrecurringEvents, false);
        getEventsPerDay(&$eventsPerDay, &$maxTimeDifferenceInDays, $recurringEvents, true);    
        
        /*
        $relativePercentageSums = array();
        $numOfDaysWithEvents = 0;
        
        calculateRelativePercentageSums($relativePercentageSums, $numOfDaysWithEvents, $eventsPerDay, $maxTimeDifferenceInDays);
        
        if($numOfDaysWithEvents) {
            $usersWithEventsCount++;
        }
        
        print("user_id = $user_id : relativePercentageSums<br/>");
        
        for($i = 0;$i < $maxTimeDifferenceInDays;$i++) {
            
            if( !array_key_exists($i, $relativePercentageSumsUserNormalized) ) {
                $relativePercentageSumsUserNormalized[$i] = 0; 
            }
            
            $relativePercentageSumsUserNormalized[$i] += $relativePercentageSums[$i]/$numOfDaysWithEvents;           
            print( ($relativePercentageSums[$i]/$numOfDaysWithEvents) . "<br/>"); 
        }
        */
        
        printNumOfDaysWithThatEventCount($eventsPerDay, $user_id);
    }
    
    /*
    print("relativePercentageSumsUserNormalized<br/>");
    
    for($i = 0;$i < count($relativePercentageSumsUserNormalized);$i++) {
        print( ($relativePercentageSumsUserNormalized[$i]/$usersWithEventsCount) . "<br/>");
        
    }
    */
    
}    
else {    
?>

<form action="dataAnalysisPerDayUserNormalized.php" method="POST">
  <p>  
    password:<input type="password" name="password">
  </p>
</form>   

<?php
}
?>