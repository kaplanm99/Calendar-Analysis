<?php

if( isset($_POST['password']) && $_POST['password'] == "pilot_calendar_study_results!!!!" ) {    

    $eventsCreatedDaysBefore = array();
    $eventLengthSumsCreatedDaysBefore = array();
    
    $users = array();
    
    $dayOfTheWeekEventCreated = array();
    $dayOfTheWeekEventStart = array();
    
    $monthOfTheYearEventCreated = array();
    $monthOfTheYearEventStart = array();
    
    for($i = 1;$i <= 7;$i++) {
        $dayOfTheWeekEventCreated[$i] = 0;
        $dayOfTheWeekEventStart[$i] = 0;
    }
    
    for($i = 1;$i <= 12;$i++) {
        $monthOfTheYearEventCreated[$i] = 0;
        $monthOfTheYearEventStart[$i] = 0;
    }
    
    $maxDay = -1000000;
    $minDay = 1000000;
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    
    if ($stmt = $mysqli->prepare("SELECT `id` FROM `user` WHERE 1;")) {
        $stmt->execute();
        $stmt->bind_result($u_id);
        
        while($stmt->fetch()) {
            $users[$u_id]["maxDay"] = -1000000;
            $users[$u_id]["minDay"] = 1000000;
            $users[$u_id]["eventCountSum"] = 0;
            $users[$u_id]["eventLengthSum"] = 0;
            
            require('db/config.php');
            $mysqli2 = new mysqli($host, $username, $password, $db);                    
            if ($stmt2 = $mysqli2->prepare("SELECT AVG(c) FROM (SELECT COUNT(*) as c FROM `event` WHERE `user_id`= ? GROUP BY `google_start`)  counts;")) {
                $stmt2->bind_param('i', $u_id);
                $stmt2->execute();
                $stmt2->bind_result($avgEventCountOnEventDays);
            
                if($stmt2->fetch()){
                    $users[$u_id]["avgEventCountOnEventDays"] = floatval($avgEventCountOnEventDays);
                }
            
                $stmt2->close();
            }
             
            $mysqli2->close();
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    
    if ($stmt = $mysqli->prepare("SELECT `google_created`, `google_start`, `google_end`, `user_id` FROM `event` WHERE `google_recurrence` = '' AND `recurring_event_id` = 0;")) {
        $stmt->execute();
        $stmt->bind_result($google_created, $google_start, $google_end, $user_id);
        
        while($stmt->fetch()) {
            $eventLengthInHours = (strtotime($google_end) - strtotime($google_start))/(60*60);
            
            // require that events are scheduled for the future. require that events are not zero length and that events are not all day events
            if(strtotime($google_start) > 0 && strtotime($google_created) > 0 && $eventLengthInHours > 0 && $eventLengthInHours < 24) {
                $timeDifferenceInSeconds = strtotime($google_start) - strtotime($google_created);
                $timeDifferenceInDays = intval( $timeDifferenceInSeconds/(60*60*24) );
                //$timeDifferenceInDays = $timeDifferenceInDays . "";
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
                    
                    $start_day = strtotime($google_start)/(60*60*24);
                    
                    if($start_day > $users[$user_id]["maxDay"]) {
                        $users[$user_id]["maxDay"] = $start_day;
                    }
                    
                    if($start_day < $users[$user_id]["minDay"]) {
                        $users[$user_id]["minDay"] = $start_day;
                    }
                    
                    $users[$user_id]["eventCountSum"]++;
                    $users[$user_id]["eventLengthSum"] += $eventLengthInHours;
                    
                    $dayFormat = "N";
                    $monthFormat = "n";
  
                    $google_createdDT = new DateTime($google_created);
                    $google_created_DayOfTheWeek = $google_createdDT->format($dayFormat);
                    $google_created_MonthOfTheYear = $google_createdDT->format($monthFormat);
                    
                    $dayOfTheWeekEventCreated[$google_created_DayOfTheWeek]++;
                    $monthOfTheYearEventCreated[$google_created_MonthOfTheYear]++;
                    
                    $google_startDT = new DateTime($google_start);
                    $google_start_DayOfTheWeek = $google_startDT->format($dayFormat);
                    $google_start_MonthOfTheYear = $google_startDT->format($monthFormat);
                    
                    $dayOfTheWeekEventStart[$google_start_DayOfTheWeek]++;
                    $monthOfTheYearEventStart[$google_start_MonthOfTheYear]++;
                }
            }
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    //print($maxDay . " " . $minDay  . "<br/>");
    
    print("dayOfTheWeekEventCreated:<br/>");
    
    for($i = 1;$i <= 7;$i++) {
        print($dayOfTheWeekEventCreated[$i] . "<br/>");
    }
    
    print("<br/>");
    
    print("dayOfTheWeekEventStart:<br/>");
    
    for($i = 1;$i <= 7;$i++) {
        print($dayOfTheWeekEventStart[$i] . "<br/>");
    }
    
    /////////////////////////
    
    print("monthOfTheYearEventCreated:<br/>");
    
    for($i = 1;$i <= 12;$i++) {
        print($monthOfTheYearEventCreated[$i] . "<br/>");
    }
    
    print("<br/>");
    
    print("monthOfTheYearEventStart:<br/>");
    
    for($i = 1;$i <= 12;$i++) {
        print($monthOfTheYearEventStart[$i] . "<br/>");
    }
    
    /////////////////////////
    
    print("<br/>");
    
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
    
    // calculate average events scheduled per day by summing events scheduled per user and also finding the difference in days between each users earliest and latest start day. then divide sum/days for each user and then average the average events per day per user values.
    $user_index = 0;
    
    $groupAvgEventCountPerDaySum = 0;
    $groupAvgEventLengthPerDaySum = 0;
    $groupAvgEventCountOnEventDaysSum = 0;
    
    foreach ($users as $user) {
        $numOfDays = $user["maxDay"]-$user["minDay"];
        $userAvgEventCountPerDay = $user["eventCountSum"]/$numOfDays;
        $userAvgEventLengthPerDay = $user["eventLengthSum"]/$numOfDays;
        
        print($user_index . " avgEventCountPerDay = " . $userAvgEventCountPerDay . ", avgEventLengthPerDay = " . $userAvgEventLengthPerDay . ", avgEventCountOnEventDays = " . $user["avgEventCountOnEventDays"] . "<br/>");
        
        $groupAvgEventCountPerDaySum += $userAvgEventCountPerDay;
        $groupAvgEventLengthPerDaySum += $userAvgEventLengthPerDay;
        $groupAvgEventCountOnEventDaysSum += floatval($user["avgEventCountOnEventDays"]);
        
        $user_index++;
    }
    
    $groupAvgEventCountPerDay = $groupAvgEventCountPerDaySum/$user_index;
    $groupAvgEventLengthPerDay = $groupAvgEventLengthPerDaySum/$user_index;
    $groupAvgEventCountOnEventDays = $groupAvgEventCountOnEventDaysSum/$user_index;
    
    print("groupAvgEventCountPerDay = " . $groupAvgEventCountPerDay . ", groupAvgEventLengthPerDay = " . $groupAvgEventLengthPerDay . ", groupAvgEventCountOnEventDays = " . $groupAvgEventCountOnEventDays . "<br/>");
    
}
else {    
?>

<form action="dataAnalysis.php" method="POST">
  <p>  
    password:<input type="password" name="password">
  </p>
</form>   

<?php
}
?>