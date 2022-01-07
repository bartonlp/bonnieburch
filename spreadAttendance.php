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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci,

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

define(WEEK, 604800);
define(STARTWED, 1641358800);

$unixToday = strtotime("today");
//$unixToday = strtotime('2022-02-15');
$today = date("l F j, Y", $unixToday);

// This is the start date: Unix Time stamp for Wednesday 2022-01-05 is 1641358800

for($i=STARTWED, $ii=0; $unixToday > $i; ++$ii, $i = $i + WEEK) {
  $wed = date("m-d", $i);
  $hdr .= "<th>$wed</th>";
  $wedList[] = date("Y-m-d", $i);
}

$fill = "<th colspan='$ii'></th>";

$hdr = "<tr><th>Name</th><th>Total</th>$hdr</tr>";

$n = $S->query("select id, name from bridge order by lname");

$r = $S->getResult();

while([$fid, $name] = $S->fetchrow($r, 'num')) {
  $total = 0;
  $row = '';
  for($i=0; $i < count($wedList); ++$i) {
    $n = $S->query("select `date` from weeks where fid=$fid and `date`='$wedList[$i]'");

    if($n) {
      [$date] = $S->fetchrow('num');
      ++$total;
      $row .= "<td class='h-a' data-id='$fid' data-week='$wedList[$i]'>H</td>";
    } else {
      $row .= "<td class='h-a' data-week='$wedList[$i]'></td>";
    }
  }
  $finaltotal += $total;

  $row = "<tr><td data-name='$fid'>$name</td><td>$total</td>$row</tr>\n";
  $rows .= $row;
}

$h->title = "Bridge Attendance Spread";
$h->desc = "A spread sheet of bridge attendance";
$h->banner = "<h1>Bridge Attendance Spread Sheet</h1>";

$h->css =<<<EOF
<style>
  #spread-attendance tbody td { padding: 0 5px; }
  #spread-attendance tbody td:nth-of-type(2) { text-align: right }
  .tfoot { background: yellow; }
  .total { text-align: right; }
</style>
EOF;

$b->script =<<<EOF
<script>
  $("#spread-attendance td.h-a").on("click", function(e) {
    let id = $(this).attr('data-id');
    let week = $(this).attr('data-week');
    
    if(id) {
      location.replace("editAttendance.php?page=delete&id="+id+"&week="+week);
    } else {
      id = $(this).closest("tr").find("td:first-child").attr('data-name');
      location.replace("editAttendance.php?page=add&id="+id+"&week="+week);
    }
  });
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<h2>Today is $today</h2>
<p>To add attendance or delete attendance click on the <b>H</b> or <b>blank</b> cell under the date.</p>
<table id="spread-attendance" border='1'>
<thead>
$hdr
</thead>
<tbody>
$rows
</tbody>
<tfoot>
<tr><th class="tfoot">Total</th><th class="tfoot total">$finaltotal</th>$fill</tr>
</tfoot>
</table>
<br>              
<a href="index.php">Return to Home Page</a>
$footer
EOF;
