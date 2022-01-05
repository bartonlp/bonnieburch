<?php
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
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

$S = new $_site->className($_site);

define(WEEK, 604800);
define(STARTWED, 1641358800);

//$unixToday = date("U");
$unixToday = strtotime('2022-02-15');
$today = date("l F j, Y", $unixToday);

// This is the start date: Unix Time stamp for Wednesday 2022-01-05 is 1641358800

for($i=STARTWED; $unixToday > $i; $i = $i + WEEK) {
  $wed = date("m-d", $i);
  $hdr .= "<th>$wed</th>";
  $wedList[] = date("Y-m-d", $i);
}

$hdr = "<tr><th>Name</th><th>Total</th>$hdr</tr>";

$n = $S->query("select id, fname, lname from bridge order by lname");

$r = $S->getResult();

while([$fid, $fname, $lname] = $S->fetchrow($r, 'num')) {
  $total = 0;
  $row = '';
  for($i=0; $i < count($wedList); ++$i) {
    $n = $S->query("select `date`, cash from weeks where fid=$fid and `date`='$wedList[$i]'");

    if($n) {
      [$date, $cash] = $S->fetchrow('num');
      $total += $cash;
      $cash = ($cash != 0) ? ": <span>$" . number_format($cash) . "</span>" : '';
      $row .= "<td data-id='$fid' data-week='$wedList[$i]'>HERE$cash</td>";
    } else {
      $row .= "<td data-week='$wedList[$i]'></td>";
    }
  }
  $total = "$" . number_format($total);
  $row = "<tr><td data-name='$fid'>$fname $lname:</td><td>$total</td>$row</tr>\n";
  $rows .= $row;
}

$h->title = "Bridge Spread";
$h->desc = "A spread sheet of bridge attendance";
$h->banner = "<h1>Spread Sheet</h1>";

$h->css =<<<EOF
<style>
  table tbody td { padding: 0 5px; }
  table tbody td:nth-of-type(2) { text-align: right; }
  table tbody td span { display: inline-block; text-align: right; width: 4em; }
</style>
EOF;

$b->script =<<<EOF
<script>
  $("td").on("click", function(e) {
    let id = $(this).attr('data-id');
    let week = $(this).attr('data-week');
    
    if(id) {
      location.href = "editweek.php?page=id&id="+id+"&week="+week;;
    } else {
      id = $(this).closest("tr").find("td:first-child").attr('data-name');
      location.href = "editweek.php?page=week&id="+id+"&week="+week;
    }
  });
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<h2>Today is $today</h2>
<table border='1'>
<thead>
$hdr
</thead>
<tbody>
$rows
</tbody>
</table>
<a href="/bridge">Return to Home</a>
$footer
EOF;
