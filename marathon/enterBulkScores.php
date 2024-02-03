<?php
// BLP 2023-02-23 - Using new approach.
/*
CREATE TABLE `scores` (
  `fkteam` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `moNo` int DEFAULT NULL,
  `score` int DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`fkteam`,`month`)
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
$S = new SiteClass($_site);

// GET the date we are to enter the scores. Because Barton always forgets!

if($_POST['page'] == "DATE") {
  $month = $_POST['month'];

  $email = $_GET['email'];

  if(empty($email) || !$S->sql("select team from marathon.teams where email1='$email' or email2='$email'")) {
    $S->sql("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

    error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

    echo "<h1>Not Authorized</h1><p>Go Away</p>";  
    exit();
  }

  $S->title = "Enter Bulk Scores";
  $S->banner = "<h1>$S->title</h1>";

  $S->desc = "Lot of bridge playing here";
  $S->css =<<<EOF
#month span { color: red; }
#enterscore tbody td:first-of-type { text-align: center; width:100px; }
#enterscore table tbody td:nth-of-type(2) { text-align: left; }
#enterscore table thead th:nth-of-type(3) { text-align: right; }
#enterscore table tbody td:nth-of-type(3) input { text-align: right; width: 100px; }
button { font-size: var(--blpFontSize); border-radius: 10px; padding: 5px; color: white; background: green; }
#enterscore tbody input { font-size: calc(22px + .4vw); }
#enterscore thead select { font-size: calc(22px + .4vw); }
#enterscore tbody td:last-of-type input { text-align: right; }
#enterscore { border-collapse: collapse; }
#enterscore tbody tr { border: 1px solid black; }

EOF;

  [$top, $footer] = $S->getPageTopBottom();

  $S->sql("select team, name1, name2 from teams order by team");
  while([$team, $name1, $name2] = $S->fetchrow('num')) {
    $names .= <<<EOF
<tr><td>$team</td><td>$name1 & $name2</td>
<td><input type='text' name='team[$team]'></td></tr>
EOF;
  }

  echo <<<EOF
$top
<hr>
<h2 id="month">For the month of <span>$month</span></h2>
<form method="post">
<table id="enterscore" border="1">
<thead>
<tr><th>Team</th><th>Team Members</th><th>Score</th><tr>
</thead>
<tbody>
$names
</tbody>
</table>
<br>
<input type="hidden" name="email" value="$email">
<input type="hidden" name="month" value="$month">
<button name="page" value="submit">Submit</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

// Now that we have the DATE we can get the rest of the stuff

if($_POST['page'] == 'submit') {
  $S->title = "Enter Bulk Scores";
  $S->banner = "<h1>$S->title</h1>";

  $S->css =<<<EOF
#results tbody td { text-align: right; padding: 0 5px; }
#results tbody td:nth-of-type(2) { text-align: left; }
.posted { font-weight: bold; }
EOF;

  [$top, $footer] = $S->getPageTopBottom();
  
  $email = $_POST['email'];
  $teams = $_POST['team']; // team is an array of teams
  $month = $_POST['month'];  

  // First insert the id and date.

  foreach($teams as $k=>$v) {
    if($v === '') continue;
    
    $n = $S->sql("update scores set score=$v, created=now(), lasttime=now() where fkteam=$k and month='$month'");
  }
  
  $hdr =<<<EOF
<table id='results' border='1'>
<thead>
<tr><th>Team</th><th>Players</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan1</th><th>Jan2</th><th>Feb1</th><th>Feb2</th><th>Mar</th><th>Apr</th><th>May</th><th>Total</th></tr>
</thead
<tbody>
EOF;
  
  $S->sql("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam=t.team order by s.fkteam");
  $r = $S->getResult();

  while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
    $S->sql("select score from scores where fkteam=$team order by moNo");
    $list .= "<tr><td>$team</td><td>$name1 & $name2</td>";

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

// Start Page Just ask for the Month because Barton always forgets.

$S->title = "Enter Month Played";
$S->banner = "<h1>$S->title</h1>";
$S->desc = "Lot of bridge playing here";
$S->css =<<<EOF
button { font-size: var(--blpFontSize); border-radius: 10px; padding: 5px; color: white; background: green; }
td { padding: 5px; }
input { font-size: calc(22px + .4vw); }
select { font-size: calc(22px + .4vw); }
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<form method="post">
<table id="enterdate">
<tbody>
<tr><td>Select Month:</td>
<td><select name="month">
<option>September</option>
<option>October</option>
<option>November</option>
<option>December</option>
<option>January 1</option>
<option>January 2</option>
<option>February 1</option>
<option>February 2</option>
<option>March</option>
<option>April</option>
<option>May</option>
</select></td><td></td><tr>
</tbody>
</table>
<input type="hidden" name="email" value="$email">
<button name="page" value="DATE">Submit</button>
</form>
$footer
EOF;
