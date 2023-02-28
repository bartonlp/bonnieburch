<?php
// BLP 2023-02-24 - use new approach
// Show the total donations

require("startup.i.php");

$S = new $_site->className($_site);

$S->title = "Bridge Donation Totals";
$S->desc = "Lot of bridge playing here";
$S->banner = "<h1>Bridge Donation Totals</h1>";
$S->css =<<<EOF
  .tfoot { background: yellow; }
  .total { text-align: right; }
  #show-totals td:last-of-type { text-align: right }
  #show-totals td, th { padding: 0 5px; }
@page { margin: .05in .125in;}
@media print {
  header, footer, hr, .noPrint, #printbtn, #return { display: none; }
  #printTitle { display: block; margin: 0; }
  #scroll { overflow: visible; }
  #show-totals {
    font-size: 9.5pt;
  }
EOF;

[$top, $footer] = $S->getPageTopBottom();

$sql = "select id, name from bridge order by lname";
$S->query($sql);
$r = $S->getResult();
while([$id, $name] = $S->fetchrow($r, 'num')) {
  $sql = "select money from money where fid=$id and date <= '$wed'";
  $S->query($sql);
  $money = 0;
  
  while([$m] = $S->fetchrow('num')) {
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
<hr>
<h1>Totals as of $fullDate</h1>
<p class="noPrint">Today is $today.<br>
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
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
<a id="return" href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;

