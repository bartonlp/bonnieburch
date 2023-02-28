<?php
// BLP 2023-02-24 - use new approach
// Add pressent to weeks table for Wed. games
// This file uses editDonation.php

require("startup.i.php");
$S = new $_site->className($_site);

$S->query("select distinct date from money order by date");

while([$date] = $S->fetchrow('num')) {
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
      $m = $money;
      $money = "$". number_format($money);
      for($i=$ii; $i< count($datear); ++$i) {
        if($datear[$i] != $date) {
          //echo "NOT: $i, $id, $datear[$i], $date<br>";
          $line .= "<td></td>";
          ++$ii;
        } else {
          //echo "OK: $i, $id, $datear[$i], $date<br>";
          $line .= "<td class='money' data-id='$id' data-week='$date'>$money</td>";
          $ar[$i] += $m;
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

  //$fill = "<th colspan='" . count($datear) . "'></th>";
  
  $S->query("select sum(money) from money where fid=$id");
  $total = "$". number_format($S->fetchrow('num')[0]);
  
  $rows .= "<tr><td class='no'>$name</td><td>$total</td>$line</tr>";
}

$finaltotal = "$". number_format($finaltotal);

$foot = "<tr><th class='tfoot'>Total</th><th class='tfoot total'>$finaltotal</th>";

for($i=0; $i < count($datear); ++$i) {
  $mm = "$" . number_format($ar[$i]);
  $foot .= "<th class='total'>$mm</th>";
}
$foot .= "</tr>";

$money = <<<EOF
<table id="money" border="1">
<thead>
<tr><th>name</th><th>total</th>$dates</tr>
</thead>
<tbody>
$rows
</tbody>
<tfoot>
$foot
</tfoot>
</table>
EOF;

$S->title = "Bridge Donation Spread Sheet";
$S->banner = "<h1>Bridge Donation Spread Sheet</h1>";
$S->css =<<<EOF
  #scroll { overflow-x: auto; }
  td { text-align: right; }
  td.no { text-align: left; }
  td, th { padding: 0 5px; }
  .tfoot { background: yellow; }
  .total { text-align: right }
  #printTitle { display: none; }
@page { size: landscape; margin: .1in .125in .1in .125in; }
@media print {
  header, footer, hr, #info, #printbtn, #return { display: none; }
  #printTitle { display: block; margin: 0; padding: 0; line-height: .5in; }
  #scroll { overflow: visible; }
  #money {
    font-size: 10pt;
    width: 100%;
  }
}  
EOF;

$S->b_inlineScript =<<<EOF
  $(".money").on("click", function(e) {
    let id = $(this).attr('data-id');
    let week = $(this).attr('data-week');
    
    location.replace("editDonation.php?page=edit&id="+id+"&week="+week);
  });
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<p id='info'>To edit a donation for a player for a date click on the dollar amount show.<br>
To add an amount for a player not shown go to <a href="addDonation.php">Add Donation Info</a>.</p>
<h1 id='printTitle'>Donation Spread Sheet</h1> <!-- Hidden except while printing -->
<div id='scroll'>
$money
</div>
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>

<a id='return' href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
