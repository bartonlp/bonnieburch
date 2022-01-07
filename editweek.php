<?php
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(254) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
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

if($which = $_POST['page']) {
  $h->title = "$which Done";
  $h->desc = $whcih;
  $h->banner = "<h1>Record {$which}ed</h1>";

  $id = $_POST['id'];
  $name = $_POST['name'];
  $week = $_POST['week'];
  
  switch($which) {
    case 'Delete':
      $S->query("delete from weeks where fid=$id and date='$week'");
      break;
    case 'Add':
      $S->query("insert ignore into weeks (fid, date, lasttime) values($id, '$week', now())");
      break;
    default:
      echo "<h1>Go Away</h1>";
      exit();
  }

  $week = date("l F j, Y", strtotime($week));
  $msg = "The record for $name for week $week has been {$which}ed.";

  [$top, $footer] = $S->getPageTopBottom($h);

  echo <<<EOF
$top
$msg
<br>
<a href="spreadsheet.php">Return to Attendance Spread Sheet</a>
$footer
EOF;
  exit();
}

if($_GET) {
  $week = $_GET['week'];
  $id = $_GET['id'];
  $page = $_GET['page'];

  $S->query("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];

  $date = date("m-d-Y", strtotime($week));
  
  switch($page) {
    case 'delete':
      $msg = "Are you sure you want to delete week $week for $name?";
      break;
    case 'add':
      $msg = "Are you sure you want to add that $name attended for week $week?";
      break;
    default:
      echo "<h1>Go Away</h1>";
      exit();
  }

  $page = ucfirst($page);
  
  $h->title = "Are You Sure";
  $h->desc = $page;
  $h->banner = "<h1>Are You Sure?</h1>";

  [$top, $footer] = $S->getPageTopBottom($h);

  // $page is now Capitalized!
  
  echo <<<EOF
$top
<h2>$msg</h2>
<form method="post">
<button type="submit" name="page" value="$page">Yes $page</button>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="name" value="$name">
<input type="hidden" name="week" value="$week">
<button><a href="spreadsheet.php">NO, Go Back to Bridge Attendance Spread Sheet</a></button>
</form>
$footer
EOF;
  exit();
}
