<?php
// BLP 2023-02-24 - use new approach
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
// This file uses editAttendance.php
/*
CREATE TABLE `weeks` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `fid` (`fid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Attendance';
*/

require("startup.i.php");

$S = new $_site->className($_site);

// This is the start date: Unix Time stamp for Wednesday 2022-01-05 is 1641358800

for($i=STARTWED; $unixToday > ($i - WEEK); $i = $i + WEEK) {
  $wed = date("m-d", $i);
  $hdr .= "<th>$wed</th>";
  $wedList[] = date("Y-m-d", $i);
}

$hdr = "<tr><th>Name</th><th>Total</th><th>July<br>Total</td>$hdr</tr>";

$n = $S->query("select id, name from bridge order by lname");

$r = $S->getResult();

while([$fid, $name] = $S->fetchrow($r, 'num')) {
  $total = 0;
  $row = '';

  for($i=0; $i < count($wedList); ++$i) {
    if($S->query("select `date` from weeks where fid=$fid and `date`='$wedList[$i]'")) {
      [$date] = $S->fetchrow('num');
      ++$total;
      ++$ar[$i];
      $row .= "<td class='h-a center' data-id='$fid' data-week='$wedList[$i]'>H</td>";
    } else {
      $row .= "<td class='h-a' data-week='$wedList[$i]'></td>";
    }
  }

  $julyCnt = 0;
  $S->query("select `date` from weeks where fid=$fid and `date` >='$julyOn'");
  while([$date] = $S->fetchrow('num')) {
    ++$julyCnt;
  }
  $finalJuly += $julyCnt;
  $finaltotal += $total;

  $row = "<tr><td data-name='$fid'>$name</td><td>$total</td><td>$julyCnt</td>$row</tr>\n";
  $rows .= $row;
}

$S->title = "Bridge Attendance Spread";
$S->desc = "A spread sheet of bridge attendance";
$S->banner = "<h1>Bridge Attendance Spread Sheet</h1>";

$S->css =<<<EOF
  #scroll { overflow-x: auto; }
  #spread-attendance tbody td { padding: 0 5px; }
  #spread-attendance tbody td:nth-of-type(2) { text-align: right }
  #spread-attendance tbody td:nth-of-type(3) { text-align: right }
  .tfoot { background: yellow; }
  .total { text-align: right; padding: 0 5px; }
  .center { text-align: center; }
@page { size: landscape; margin: .125in;}
@media print {
  header, footer, hr, #info, #printbtn, #return { display: none; }
  #printTitle { display: block; margin: 0; }
  #scroll { overflow: visible; }
  #spread-attendance {
    font-size: 6pt;
    width: 100%;
  }
}  
EOF;

$S->b_inlineScript =<<<EOF
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
EOF;

[$top, $footer] = $S->getPageTopBottom();

$foot = "<tr><th class='tfoot'>Total</th><th class='tfoot total'>$finaltotal</th><th class='tfoot total'>$finalJuly</th>";
for($i=0; $i < count($wedList); ++$i) {
  $foot .= "<th>$ar[$i]</th>";
}
$foot .= "</tr>";

echo <<<EOF
$top
<hr>
<p id="info">Today is $today<br>
To add attendance or delete attendance click on the <b>H</b> or <b>blank</b> cell under the date.</p>
<h1 id='printTitle'>Attendance Spread Sheet</h1> <!-- Hidden except while printing -->

<div id="scroll">
<table id="spread-attendance" border='1'>
<thead>
$hdr
</thead>
<tbody>
$rows
</tbody>
<tfoot>
$foot
</tfoot>
</table>
</div>
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
<a id="return" href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
