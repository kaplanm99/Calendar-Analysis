<?php

function getUserIds() {
    $user_ids = array();

    $query = "SELECT DISTINCT `id` FROM `user`;";
     
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->execute();
        $stmt->bind_result($id);
        
        while($stmt->fetch()) {
            $user_ids[] = $id;            
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    return $user_ids;    
}

function getEventsAllUsers($recurring) {
    $events = array();

    $query = "";
    
    if($recurring) {
        $query = "SELECT `user_id`, `google_created`, `google_start`, `google_end`, `google_recurrence` FROM `event` WHERE `google_recurrence` <> '' AND `recurring_event_id` = 0;";
    } else {
        $query = "SELECT `user_id`, `google_created`, `google_start`, `google_end`, `google_recurrence` FROM `event` WHERE `google_recurrence` = '' AND `recurring_event_id` = 0;";
    }
        
    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->execute();
        $stmt->bind_result($user_id, $google_created, $google_start, $google_end, $google_recurrence);
        
        while($stmt->fetch()) {
            $event = array('user_id' => $user_id, 'google_created' => $google_created, 'google_start' => $google_start, 'google_end' => $google_end, 'google_recurrence' => $google_recurrence);
                
            $events[] = $event;            
        }
        
        $stmt->close();        
    }
       
    $mysqli->close();
    
    return $events;    
}

function getEventsByUser($recurring, $user_id) {
    
    $eventsByUser = array();
    
    $query = "";
    
    if($recurring) {
        $query = "SELECT `user_id`, `google_created`, `google_start`, `google_end`, `google_recurrence` FROM `event` WHERE `google_recurrence` <> '' AND `recurring_event_id` = 0 AND `user_id` = ?;";
    } else {
        $query = "SELECT `user_id`, `google_created`, `google_start`, `google_end`, `google_recurrence` FROM `event` WHERE `google_recurrence` = '' AND `recurring_event_id` = 0 AND `user_id` = ?;";
    }

    require('db/config.php');
    $mysqli = new mysqli($host, $username, $password, $db);                    
    if ($stmt = $mysqli->prepare($query)) {
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($user_id, $google_created, $google_start, $google_end, $google_recurrence);
            
        while($stmt->fetch()) {
            
            if($recurring){
            
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
                
                while($date = $recurrence->next()) {
                    
                    $event = array('user_id' => $user_id, 'google_created' => $google_created, 'google_start' => $date['dtstart'], 'google_end' => $date['dtend']);
                    
                    $eventsByUser[] = $event;
                    
                    $z++;
                    
                    if($z > 160) break;              
                }
            
            } else {            
                $event = array('user_id' => $user_id, 'google_created' => $google_created, 'google_start' => $google_start, 'google_end' => $google_end);
                
                $eventsByUser[] = $event;            
            }
        }
     
        $stmt->close();
    } 
    
    $mysqli->close();
 
    return $eventsByUser; 
}

?>