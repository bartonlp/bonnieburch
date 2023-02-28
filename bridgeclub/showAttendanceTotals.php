<?php
// BLP 2023-02-24 - use new approach
// Show the total to date of the Bridge members
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

$S->title = "Bridge Attendance Totals";
$S->desc = "Lot of bridge playing here";
$S->banner = "<h1>Bridge Attendance Totals</h1>";
$S->css =<<<EOF
  .tfoot { background: yellow; }
  .total { text-align: right; }
  #show-totals td:last-of-type { text-align: right }
  #show-totals td, th { padding: 0 5px; }
@media print {
  header, footer, hr, .noPrint, #printbtn, #return { display: none; }
  #printTitle { display: block; margin: 0; }
  #scroll { overflow: visible; }
  #show-totals {
    font-size: 10pt;
  }
EOF;

[$top, $footer] = $S->getPageTopBottom();

$sql = "select id, name from bridge order by lname";
$S->query($sql);
$r = $S->getResult();
while([$id, $name] = $S->fetchrow($r, 'num')) {
  $sql = "select fid from weeks where fid=$id and date <= '$wed'";
  $cnt = $S->query($sql);
  if(!$cnt) continue;
  $sql = "select fid from weeks where fid=$id and date >= '$julyOn'";
  $julyCnt = $S->query($sql);
  $total += $cnt;
  $julyTotal += $julyCnt;
  
  $list .= "<tr><td>$name</td><td>$cnt</td><td>$julyCnt</td></tr>";
}

$total = number_format($total);

echo <<<EOF
$top
<hr>
<h1 id="printTitle">Totals as of $fullDate</h1>
<p class="noPrint">Today is $today.<br>
Showing only people who have attended at least once.</p>
<table id="show-totals" border="1">
<thead>
<tr><th>Name</th><th>Total<br>Count</th><th>From<br>July</th></tr>
</thead>
<tbody>
$list
</tbody>
<tfoot>
<tr><th class="tfoot">Total</th><th class="tfoot total">$total</th><th class="tfoot fromJuly">$julyTotal</th></tr>
</tfoot>
</table>
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
<a id="return" href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
