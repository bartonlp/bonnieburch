<?php
// Show the total to date of the Bridge members

require("startup.i.php");

$S = new $_site->className($_site);

$h->title = "Bridge Attendance Totals";
$h->desc = "Lot of bridge playing here";
$h->banner = "<h1>Bridge Attendance Totals</h1>";
$h->css =<<<EOF
<style>
  .tfoot { background: yellow; }
  .total { text-align: right; }
  #show-totals td:last-of-type { text-align: right }
</style>
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

$sql = "select id, name from bridge order by lname";
$S->query($sql);
$r = $S->getResult();
while([$id, $name] = $S->fetchrow($r, 'num')) {
  $sql = "select fid from weeks where fid=$id and date <= '$wed'";
  $cnt = $S->query($sql);
  if(!$cnt) continue;
  $total += $cnt;
  
  //$cnt= $S->fetchrow('num')[0];
  $list .= "<tr><td>$name</td><td>$cnt</td></tr>";
}

$total = number_format($total);

echo <<<EOF
$top
<hr>
<h1>Totals as of $fullDate</h1>
<p>Today is $today.<br>
Showing only people who have attended at least once.</p>
<table id="show-totals">
<thead>
<tr><th></th><th>Count</th></tr>
</thead>
<tbody>
$list
</tbody>
<tfoot>
<tr><th class="tfoot">Total</th><th class="tfoot total">$total</th></tr>
</tfoot>
</table>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
