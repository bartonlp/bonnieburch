<?php
// Add Attendance info into weeks table for Wed. games
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

// Define a week and the first wed. we will use.

define(WEEK, 604800);
define(STARTWED, 1641358800);

$unixToday = strtotime("today");
//$unixToday = strtotime('2022-02-15');
$today = date("l F j, Y", $unixToday);

$unixWed = strtotime("Wednesday", $unixToday);
$unixPrevWed = strtotime("previous Wednesday", $unixToday);
$unixNextWed = strtotime("next Wednesday", $unixToday) + 604800;
$nextWed = date('Y-m-d', $unixNextWed);

if($unixToday >= $unixWed && $unixToday < $unixNextWed) {
  $wed = date('Y-m-d', $unixWed);
} else {
  $wed = date("Y-m-d", $unixPrevWed);
  $unixWed = $unixPrevWed;
} 

$fullDate = date("l F j, Y", $unixWed);

// Post the ADD

if($_POST) {
  $_site->footerFile = null;
  
  $S = new $_site->className($_site);
  
  $h->css =<<<EOF
<style>
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) { text-align: right; }
.posted { font-weight: bold; }
</style>
EOF;

  [$top, $footer] = $S->getPageTopBottom($h);
  
  $ids = $_POST['id']; // id is an array of checked on elements

  // First insert the id and date.
  
  foreach($ids as $k=>$v) {
    $S->query("select name from bridge where id=$k");
    $name = $S->fetchrow('num')[0];
    $names .= "$name, ";
    
    try {
      $sql = "insert into weeks (fid, date, lasttime) values('$k', '$wed', now())";
      $S->query($sql);
    } catch(Exception $e) {
      if($e->getCode() == 1062) { // 1062 is dup key error
        $err .= "This date has already been entered for $name.<br>";
      } else {
        throw($e);
      }
    }
  }

  $sql = "select id, name from bridge";
  $S->query($sql);
  $r = $S->getResult();
  while([$id, $name] = $S->fetchrow($r, 'num')) {
    $sql = "select count(*) from weeks where fid=$id and date <= '$wed'";
    $S->query($sql);
    $cnt = $S->fetchrow('num')[0];
    $list .= "<tr><td>$name</td><td>$cnt</td></tr>";
  }

  // POSTED page with totals

  $name = "<span class='posted'>For: </span> " . rtrim($names, ', ') . "<br>";

  $err = $err ? "<br>$err" : null;
  
  echo <<<EOF
$top
<h1>Data Posted $today</h1>
$name
$err
<h1>Totals as of $fullDate</h1>
<table id="week-posted">
<thead>
<tr><th></th><th>Count</th></tr>
</thead>
<tbody>
$list
</tbody>
</table>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
  exit();
}

// First Page

$S = new $_site->className($_site);

$h->title = "Bridge";
$h->desc = "Lot of bridge playing here";
$h->css =<<<EOF
<style>
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) input { text-align: right; width: 100px; }
</style>
EOF;

$h->css =<<<EOF
<style>
  button { font-size: var(--blpFontSize); border-radius: 10px; padding: 5px; color: white; background: green; }
  input[type='checkbox'] { width: 30px; height: 30px; }
  #week tbody td:last-of-type { text-align: center; }
  #week { border-collapse: collapse; }
  #week tbody tr { border: 1px solid black; }
  #week tbody td:first-of-type { padding: 0 5px; width: 400px; border-right: 1px solid black; }
</style>
EOF;
  
[$top, $footer] = $S->getPageTopBottom($h);

$S->query("select id, name from bridge order by lname");
while([$id, $name] = $S->fetchrow('num')) {
  $names .= <<<EOF
<tr><td>$name</td>
<td><input type='checkbox' name='id[$id]'></td></tr>
EOF;
}

echo <<<EOF
$top
<h1>$fullDate</h1>
<p>Today is $today</p>
<form method="post">
<table id="week">
<thead>
<tr><th></th><th>Present</th><tr>
</thead>
<tbody>
$names
</tbody>
</table>
<br>
<button name="submit">Submit</button>
</form>
<br>
<a href="/bridge">Return to Home Page</a>
$footer;
EOF;
