<?php
// Show the total donations

require("startup.i.php");

$S = new $_site->className($_site);

$h->title = "Bridge Donation Totals";
$h->desc = "Lot of bridge playing here";
$h->banner = "<h1>Bridge Donation Totals</h1>";
$h->css =<<<EOF
<style>
  .tfoot { background: yellow; }
  .total { text-align: right; }
  #show-totals td:last-of-type { text-align: right }
  #show-totals td, th { padding: 0 5px; }
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
<hr>
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
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;

