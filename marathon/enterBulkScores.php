<?php
/*
CREATE TABLE `scores` (
  `fkteam` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `score` int DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `teams` (
  `team` int NOT NULL,
  `name1` varchar(100) NOT NULL,
  `name2` varchar(100) NOT NULL,
  `email1` varchar(100) NOT NULL,
  `email2` varchar(100) NOT NULL,
  `created` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  PRIMARY KEY (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;  
*/

$_site = require_once(getenv("SITELOADNAME"));

if($_POST['page'] == 'submit') {
  $S = new $_site->className($_site);
  $h->title = "Enter Bulck Scores";
  $h->banner = "<h1>$h->title</h1>";

  $h->css =<<<EOF
#results tbody td { text-align: right; }
.posted { font-weight: bold; }
EOF;

  [$top, $footer] = $S->getPageTopBottom($h);
  
  $email = $_POST['email'];
  $teams = $_POST['team']; // team is an array of teams
  $month = $_POST['month'];  

  // First insert the id and date.

  foreach($teams as $k=>$v) {
    $v = !empty($v) ? $v : 0;
    $n = $S->query("update scores set score=$v, created=now(), lasttime=now() where fkteam=$k and month='$month'");
  }
  
  $hdr =<<<EOF
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
    while([$score] = $S->fetchrow('num')) {
      $total += $score;
      $list .= "<td>$score</td>";
    }
    $list .= "<td>$total</td></tr>";
  }

  $tbl .= "$hdr$list</tbody></table>";

  echo <<<EOF
$top
<hr>
<h1>Data Posted</h1>
<h1>Totals</h1>
$tbl
<br>
<a href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

// First Page

$S = new $_site->className($_site);

$h->title = "Enter Bulk Scores";
$h->banner = "<h1>$h->title</h1>";

$h->desc = "Lot of bridge playing here";
$h->css =<<<EOF
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) input { text-align: right; width: 100px; }
button { font-size: var(--blpFontSize); border-radius: 10px; padding: 5px; color: white; background: green; }
#enterscore tbody input { font-size: calc(22px + .4vw); }
#enterscore thead select { font-size: calc(22px + .4vw); }
#enterscore tbody td:last-of-type input { text-align: right; }
#enterscore { border-collapse: collapse; }
#enterscore tbody tr { border: 1px solid black; }
#enterscore tbody td:first-of-type { padding: 0 5px; width: 400px; border-right: 1px solid black; }
EOF;

$email = $_GET['email'];

[$top, $footer] = $S->getPageTopBottom($h);

$S->query("select team, name1, name2 from teams order by team");
while([$team, $name1, $name2] = $S->fetchrow('num')) {
  $names .= <<<EOF
<tr><td>$team</td><td>$name1 & $name2</td>
<td><input type='text' name='team[$team]'></td></tr>
EOF;
}

echo <<<EOF
$top
<hr>
<form method="post">
<table id="enterscore">
<thead>
<tr><td>Select Month:</td>
<td><select name="month">
<option>September</option>
<option>October</option>
<option>November</option>
<option>December</option>
<option>January (1)</option>
<option>January (2)</option>
<option>February (1)</option>
<option>February (2)</option>
<option>March</option>
<option>April</option>
<option>May</option>
</select></td><td></td><tr>
<tr><th>Team</th><th>Team Members</th><th>Score</th><tr>
</thead>
<tbody>
$names
</tbody>
</table>
<br>
<input type="hidden" name="email" value="$email">
<button name="page" value="submit">Submit</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
