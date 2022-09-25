<?php
// startup.i.php is used by all of the bridge apps except index.php
// This is one place for the table comments, the finger test and the date logic.
// Some apps don't need all of the date logic but it does not hurt to have it.

//***************************************
// FOR testing put a date in $todayDateIs
//$todayDateIs = "2022-01-14";
//***************************************

/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(254) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fnamelname` (`fname`, `lname`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='BridgeNames';

CREATE TABLE `weeks` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `fid` (`fid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Attendance';

CREATE TABLE `money` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `money` decimal(7,0) DEFAULT '0',
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Donation';

CREATE TABLE `badplayer` (
  `ip` varchar(20) NOT NULL,
  `botAs` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `count` int DEFAULT NULL,
  `errno` int DEFAULT NULL,
  `errmsg` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;  
*/

$_site = require_once(getenv("SITELOADNAME"));

if($_GET['blp'] != '8653') {
  // Check if user is Authorized. Look at the BLP-Finger cookie.
  
  $finger = $_COOKIE['BLP-Finger'];
  $bonnieFingers = require("../fingers/bonnieFinger.php");

  $S = new Database($_site);
  
  if(array_intersect([$finger] , $bonnieFingers)[0] === null) {
    $S = new Database($_site);
    $S->query("insert into $S->masterdb.badplayer (ip, botAs, type, count, errmsg, agent, created, lasttime) ".
              "values('$S->ip', 'BB_STARTUP', 'STARTUP', 1, 'NOT AUTHOREIZED', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");
    
    echo <<<EOF
  <h1>You are NOT AUTHORIZED</h1>
  EOF;
    error_log("bridgeclub/startup.i.php: finger=$finger, ip=$S->ip, site=$S->siteName. Not Authorized, agent=$S->agent");
    exit();
  }
}

// End of Check if user is Authorized

// Define a week and the first wed. we will use.

$startWed = strtotime("2022-01-05");
$julyOn = "2022-07-06"; // This is the start of the real counting towards the prize!
define("WEEK", 604800);
define("STARTWED", $startWed);

$unixToday = strtotime($todayDateIs ?? "today");
//echo "$unixToday, " . strtotime("today") . "<br>";

$today = date("l F j, Y", $unixToday);

$unixWed = strtotime("Wednesday", $unixToday);
$unixPrevWed = strtotime("previous Wednesday", $unixToday);
$unixNextWed = strtotime("next Wednesday", $unixToday) + WEEK;
$nextWed = date('Y-m-d', $unixNextWed);

if($unixToday >= $unixWed && $unixToday < $unixNextWed) {
  $wed = date('Y-m-d', $unixWed);
} else {
  $wed = date("Y-m-d", $unixPrevWed);
  $unixWed = $unixPrevWed;
} 

$fullDate = date("l F j, Y", $unixWed);