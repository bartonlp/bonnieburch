<?php
// Show the total donations
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

$S = new $_site->className($_site);

$h->title = "Bridge Donation Totals";
$h->desc = "Lot of bridge playing here";
$h->banner = "<h1>Bridge Donation Totals</h1>";
$h->css =<<<EOF
<style>
  .tfoot { background: yellow; }
  .total { text-align: right; }
  td:last-of-type { text-align: right }
  td, th { padding: 0 5px; }
</style>
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

$sql = "select id, name from bridge order by lname";
$S->query($sql);
$r = $S->getResult();
while([$id, $name] = $S->fetchrow($r, 'num')) {
  $sql = "select money from money where fid=$id and date <= '$wed'";
  $S->query($sql);
  $money = 0;
  
  while($m = $S->fetchrow('num')[0]) {
    $money += $m;
    $total += $m;
  }
  if($money == 0) continue;
  
  $money = "$". number_format($money);
      
  $list .= "<tr><td>$name</td><td>$money</td></tr>";
}

$total = "$". number_format($total);

echo <<<EOF
$top
<h1>Totals as of $fullDate</h1>
<p>Today is $today.<br>
Showing only those who have donated.</p>
<table id="show-totals" border="1">
<thead>
<tr><th>Name</th><th>Donation<br>Total</th></tr>
</thead>
<tbody>
$list
</tbody>
<tfoot>
<tr><th class="tfoot">Grand Total</th><th class="tfoot total">$total</th></tr>
</tfoot>
</table>
<br>
<a href="index.php">Return to Home Page</a>
$footer
EOF;

