<?php
// Show the total to date of the Bridge members
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `weeks` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `cash` decimal(7,2) DEFAULT '0.00',
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `fid` (`fid`)
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

//$unixToday = strtotime("today");
$unixToday = strtotime('2022-02-15');
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

$S = new $_site->className($_site);

$h->title = "Bridge";
$h->desc = "Lot of bridge playing here";
  $h->css =<<<EOF
<style>
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) { text-align: right; }
</style>
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

$sql = "select id, fname, lname from bridge";
$S->query($sql);
$r = $S->getResult();
while([$id, $fname, $lname] = $S->fetchrow($r, 'num')) {
  $sql = "select count(*), sum(cash) from weeks where fid=$id and date <= '$wed'";
  $S->query($sql);
  [$cnt, $cash] = $S->fetchrow('num');
  $cash = "$".number_format(($cash ?? 0));
  $list .= "<tr><td>$fname $lname</td><td>$cnt</td><td>$cash</td></tr>";
}

echo <<<EOF
$top
<h1>Totals as of $fullDate</h1>
<p>Today is $today</p>
<table>
<thead>
<tr><th></th><th>Count</th><th>Cash</th></tr>
</thead>
<tbody>
$list
</tbody>
</table>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;

