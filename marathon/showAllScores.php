<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

$email = "bartonphillips@gmail.com";

$h->title = "Show All Scores";
$h->banner = "<h1>$h->title</h1>";

$h->css =<<<EOF
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(13), #results td:nth-of-type(13) { background: lightpink; }
#results tbody td { text-align: right; }
EOF;
$tbl =<<<EOF
<table id='results' border='1'>
<thead>
<tr><th>Team</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan1</th><th>Jan2</th><th>Feb1</th><th>Feb2</th><th>Mar</th><th>Apr</th><th>May</th><th>Total</th></tr>
</thead
<tbody>
EOF;
  
$S->query("select distinct fkteam from scores order by fkteam");
$r = $S->getResult();

while($team = $S->fetchrow($r, 'num')[0]) {
  $list .= "<tr><td>$team</td>";
  
  $S->query("select score from scores where fkteam=$team order by moNo");
  $total = 0;

  // BLP 2022-08-26 - NOTE: if I do $score = $S->fetchrow('num')[0], I could get a zero back which looks like a
  // null and  would stop the while loop! So while I could do this ($score =
  // $S->fetchrow('num')[0]) !== null), it is probably safer to always use an array as the receiver
  // in a while loop. I think I have fixed all of my code.
    
  while([$score] = $S->fetchrow('num')) {
    $total += $score;
    $list .= "<td>$score</td>";
  }
  $list .= "<td>$total</td></tr>";
}

$tbl .= "$list</tbody></table>";

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
$team
$tbl
<br>
<a href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
