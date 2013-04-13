<?php
session_start();

require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_CalendarService.php';

require('db/config.php');        
  
$client = new Google_Client();
$client->setApplicationName($googleApplicationName);
$client->setClientId($googleClientId);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($googleRedirectUri);
$client->setDeveloperKey($googleDeveloperKey);
$cal = new Google_CalendarService($client);

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
  return;
}

if (isset($_SESSION['token'])) {
 $client->setAccessToken($_SESSION['token']);
}

if (isset($_REQUEST['logout'])) {
  unset($_SESSION['token']);
  $client->revokeToken();
}

if ($client->getAccessToken()) {
  $ignore = 0;
  
  require('db/config.php');
  $mysqli = new mysqli($host, $username, $password, $db);
  if ($stmt = $mysqli->prepare("INSERT INTO `user`(`ignore`) VALUES (?);")){
    $stmt->bind_param('i', $ignore);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
  }
   
  $mysqli->close();

  ////////////////////////
  
  require('db/config.php');
  $mysqli = new mysqli($host, $username, $password, $db);
  if ($stmt = $mysqli->prepare("INSERT INTO `event`(`user_id`, `google_event_id`, `google_created`, `google_updated`, `google_start`, `google_end`, `google_recurrence`, `google_recurring_event_id`) VALUES (?,?,?,?,?,?,?,?);")){
    
      $calList = $cal->calendarList->listCalendarList();
      //$calListMarkup = "<h1>Calendar List</h1><pre>" . print_r($calList["items"], true) . "</pre>";
      
      foreach ($calList["items"] as $tempCal) {
        $eventList = $cal->events->listEvents($tempCal["id"]);
        //$calListMarkup = $calListMarkup . print_r($eventList["items"], true);
        
        foreach ($eventList["items"] as $tempEvt) {
          //print_r($tempEvt);
          //print("<br/>");
          
          $google_recurrence = "";
          
          if( array_key_exists('recurrence', $tempEvt) ) {
            $google_recurrence = serialize($tempEvt['recurrence']);
          }
          
          $google_recurring_event_id = "";
          
          if( array_key_exists('recurringEventId', $tempEvt) ) {
            $google_recurring_event_id = $tempEvt['recurringEventId'];
          }
          
          $stmt->bind_param('isssssss', $user_id, $tempEvt['id'], $tempEvt['created'], $tempEvt['updated'], $tempEvt['start']['dateTime'], $tempEvt['end']['dateTime'], $google_recurrence, $google_recurring_event_id);
          $stmt->execute();
    
          
          
          /*
          user_id   get this from insert into user table.
          google_event_id   $tempEvt['id']  might not be anonymous
          google_created    $tempEvt['created']
          google_updated    $tempEvt['updated']
          google_start      $tempEvt['start']['dateTime']
          google_end        $tempEvt['end']['dateTime']
          google_recurrence if isset $tempEvt['recurrence'] then serialize($tempEvt['recurrence'])
          and unserialize($str) during analysis
          google_recurring_event_id if isset $tempEvt['recurringEventId'] then save it but using [id] anonymize this value using my tables event id in a pass after events have been added.
          need to store a user_id that uniquely identifies a user so single user analysis can be done. this can be generated with a users table that just have 1 autoincremented field and returns the insert_id. but that user_id should be anonymous so it probably shouldn't be stored with the email. we could have a table of bcrypt hashed and salted emails of participants that we check before adding events so we don't accidentally have duplicate data if the user reloads the page  idk if thats anonymous enough. we could delete these at the end but idk if that's enough. maybe we could try this: http://stackoverflow.com/questions/6333623/mysql-syntax-for-inserting-a-new-row-in-middle-rows with an email only table where the id of that table does not correspond to the id on the user table or anything else.  
          
          http://stackoverflow.com/questions/6262186/convert-array-to-string-php
          http://php.net/manual/en/function.serialize.php
          http://www.php.net/manual/en/function.unserialize.php http://googleappsdeveloper.blogspot.com/2011/12/calendar-v3-best-practices-recurring.html
          http://tools.ietf.org/html/rfc5545#section-3.8.5
          all day events should not be used in analysis      
          */
          
          
        }
        //print_r($eventList["items"]);
        
      }
  
    $stmt->close();
  }
   
  $mysqli->close();

  // anonymize by setting recurring_event_id to event_id where e1.google_event_id = e2.google_recurring_event_id
          
  require('db/config.php');
  $mysqli = new mysqli($host, $username, $password, $db);
  if ($stmt = $mysqli->prepare("SELECT e1.event_id, e2.event_id FROM event e1, event e2 WHERE e1.google_event_id = e2.google_recurring_event_id AND e1.user_id = ? AND e2.user_id = ?;")){
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($e1_event_id, $e2_event_id);
    
    while($stmt->fetch()) {
        
        require('db/config.php');
        $mysqli2 = new mysqli($host, $username, $password, $db);
        if ($stmt2 = $mysqli2->prepare("UPDATE event SET recurring_event_id = ? WHERE event_id = ? AND user_id = ?;")){
            $stmt2->bind_param('iii', $e1_event_id, $e2_event_id, $user_id);
            $stmt2->execute();
            $stmt2->close();
        }   
        $mysqli2->close();
        
    }
    
    $stmt->close();
  }
   
  $mysqli->close();
  
  
  // for all event rows set google_event_id and google_recurring_event_id to "". 
  
  require('db/config.php');
  $mysqli = new mysqli($host, $username, $password, $db);
  if ($stmt = $mysqli->prepare("UPDATE event SET google_event_id = '', google_recurring_event_id = '' WHERE user_id = ?;")){
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $stmt->close();
  }
  $mysqli->close();
  
  
  print ("Thank you for helping us");
  
  // The access token may have been updated lazily.
  $_SESSION['token'] = $client->getAccessToken(); 
  
} else {
  $authUrl = $client->createAuthUrl();
  print "You will be asked to log into your Google account and provide permission for this application to access the events on your Google calendar. The data collected will not be used in any published research but will merely allow us to optimize data collection and analyses techniques for a future study about the use of scheduling on calendars. We will not modify your Google Calendar or any event on it in any way.
  <br/><br/>
  If you agree to participate, we will store and analyze the following data fields from every event on your Google Calendar: created, updated, start dateTime, end dateTime, recurrence. The event fields: id and recurring_event_id are temporaliy stored (a few seconds) and then are deleted. This data will not be associated with your name and email. Neither your name or email will be recorded.
  <br/><br/>
  Taking part is completely voluntary. If you decide not to take part, it will not affect your current or future relationship with Cornell University.
  <br/><br/>";
  print "<a class='login' href='$authUrl'>Continue</a>";
}

?>