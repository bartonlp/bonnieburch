<?php
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `weeks` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `fid` (`fid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `money` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `money` decimal(7,0) DEFAULT '0',
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);

// Check if user is Authorized
$finger = $_COOKIE['BLP-Finger'];
$bonnieFingers = require("/var/www/bartonphillipsnet/bonnieFinger.php");

if(array_intersect([$finger] , $bonnieFingers)[0] === null) {
  echo <<<EOF
<h1>You are NOT AUTHORIZED</h1>
EOF;
  exit();
}
// End of Check if user is Authorized

$S = new $_site->className($_site);

if($_GET) {
  $week = $_GET['week'];
  $id = $_GET['id'];
  $page = $_GET['page'];

  $S->query("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];

  $date = date("m-d-Y", strtotime($week));
  $msg = "The record for $name for week $date has been {$page}ed.";
  
  switch($page) {
    case 'delete':
      $S->query("delete from weeks where fid=$id and date='$week'");
      break;
    case 'add':
      $S->query("insert ignore into weeks (fid, date, lasttime) values($id, '$week', now())");
      break;
    default:
      echo "<h1>Go Away</h1>";
      exit();
  }

  $page = ucfirst($page);
  
  $h->title = "Bridge $page";
  $h->desc = $page;
  $h->banner = "<h1>Bridge Recored $page</h1>";
  $b->script =<<<EOF
<script>
$(".goback").on("click", function() {
//window.history.go(-2);
  location.replace("spreadsheet.php");
});
</script>
EOF;
  

  [$top, $footer] = $S->getPageTopBottom($h, $b);
  
  echo <<<EOF
$top
<h2>$msg</h2>
<!--<a href="/bridge">Return to Home Page</a>-->
<button class="goback">Go Back to Bridge Attendance Spread Sheet</button>
$footer
EOF;
  exit();
}
