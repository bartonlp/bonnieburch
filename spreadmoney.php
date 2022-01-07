<?php
// Add pressent to weeks table for Wed. games
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

$S = new $_site->className($_site);

$S->query("select distinct date from money order by date");

while($date = $S->fetchrow('num')[0]) {
  $d = date("m-d-y", strtotime($date));
  $dates .= "<th>$d</th>";
  $datear[] = $date;
}

$S->query("select id, name from bridge order by lname");
$r = $S->getResult();
while([$id, $name] = $S->fetchrow($r, 'num')) {
  if($S->query("select date, money from money where fid=$id order by date")) {
    $line = '';
    $ii = 0;
    while([$date, $money] = $S->fetchrow('num')) {
      $finaltotal += $money;
      $money = "$". number_format($money);
      for($i=$ii; $i< count($datear); ++$i) {
        if($datear[$i] != $date) {
          //echo "NOT: $i, $id, $datear[$i], $date<br>";
          $line .= "<td></td>";
          ++$ii;
        } else {
          //echo "OK: $i, $id, $datear[$i], $date<br>";
          $line .= "<td class='money' data-id='$id' data-week='$date'>$money</td>";
          ++$ii;
          break;
        }
      }
    }

    for($i=$ii; $i < count($datear); ++$i) {
      $line .= "<td></td>";
    }

  } else {
    continue;
  }

  $fill = "<th colspan='" . count($datear) . "'></th>";
  
  $S->query("select sum(money) from money where fid=$id");
  $total = "$". number_format($S->fetchrow('num')[0]);
  
  $rows .= "<tr><td class='no'>$name</td><td>$total</td>$line</tr>";
}

$h->title = "Bridge Money Spread Sheet";
$h->banner = "<h1>Bridge Money Spread Sheet</h1>";
$h->css =<<<EOF
<style>
  td { text-align: right; }
  td.no { text-align: left; }
  td, th { padding: 0 5px; }
  .tfoot { background: yellow; }
  .total { text-align: right }
</style>
EOF;

$b->script =<<<EOF
<script>
  $(".money").on("click", function(e) {
    let id = $(this).attr('data-id');
    let week = $(this).attr('data-week');
    
    location.replace("editmoney.php?page=edit&id="+id+"&week="+week);
  });
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

$finaltotal = "$". number_format($finaltotal);

echo <<<EOF
$top
<table id="money" border="1">
<thead>
<tr><th>name</th><th>total</th>$dates</tr>
</thead>
<tbody>
$rows
</tbody>
<tfoot>
<tr><th class="tfoot">Total</th><th class="tfoot total">$finaltotal</th>$fill</tr>
</tfoot>
</table>
<br>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;


