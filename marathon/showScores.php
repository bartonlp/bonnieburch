<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

$h->title = "Show Spreadsheet";
$h->banner = "<h1>Show Spreadsheet</h1>";
$h->css = <<<EOF
#scores thead tr { background: lightblue; }
#scores tfoot tr { background: yellow; }
#scores td, #scores th { padding: 0px 5px; }
#scores tbody td:first-of-type { text-align: center; }
#scores tbody td:nth-of-type(4) { text-align: right; }
#scores tfoot td:nth-of-type(4) { text-align: right; }
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

$team = $_GET['team'];
$name1 = $_GET['name1'];
$name2 = $_GET['name2'];
$email = $_GET['email'];
$total = 0;

$S->query("select month, score from scores where fkteam=$team order by moNo");
while([$month, $score] = $S->fetchrow('num')) {
  $total += $score;
  $rows .= "<tr><td>$team</td><td>$name1 & $name2</td><td>$month</td><td>$score</td></tr>";
}

echo <<<EOF
$top
<hr>
<table id='scores' border='1'>
<thead>
<tr><th>Team</th><th>Team Members</th><th>Month</th><th>Score</th></tr>
</thead>
<tbody>
$rows
</tbody>
<tfoot>
<tr><th colspan='3'>Total</th><th>$total</th></tr>
</tfoot>
</table>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;

