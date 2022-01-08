<?php
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
// This file uses editAttendance.php

require("startup.i.php");

$S = new $_site->className($_site);

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
<hr>
<p>Today is $today</p>
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
<hr>
$footer
EOF;
