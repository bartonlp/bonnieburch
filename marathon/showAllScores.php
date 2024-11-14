<?php
// BLP 2023-02-23 - Using new approach
// Show All of the Scores. They can be printed or emailed to everyone.
// This uses the file 'marathon-msg.txt' which has the message and salutation that appear on the
// Bulk Email. 
/*
 CREATE TABLE `teams` (
  `team` int NOT NULL,
  `name1` varchar(100) NOT NULL,
  `name2` varchar(100) NOT NULL,
  `email1` varchar(100) NOT NULL,
  `email2` varchar(100) NOT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `created` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  PRIMARY KEY (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `scores` (
  `fkteam` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `moNo` int DEFAULT NULL,
  `score` int DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`fkteam`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

// ************
// BLP 2024-11-02 - Depending on the year there may be more or less teams. The $lastTeam variable
// is the is the number of the last team. It is used to limit the 'select left join' below.
$lastTeam = 10; // BLP 2024-11-02 - for the season 2024-2025 there are only 10 teams.
// ************

$email = $_GET['email'];

if(empty($email) || !$S->sql("select team from marathon.teams where email1='$email' or email2='$email'")) {
  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$tbl =<<<EOF
<table id='results' border='1'>
<thead>
<tr><th>Team</th><th>Players</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>May</th><th>Total</th></tr>
</thead
<tbody>
EOF;

// BLP 2024-11-02 - For this season the number of teams is 1-10, $lastTeam == 10

$S->sql("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team where t.team <= $lastTeam order by s.fkteam");
$r = $S->getResult();

while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
  $list .= "<tr><td>$team</td><td>$name1 & $name2</td>";
  
  $S->sql("select score from scores where fkteam=$team order by moNo");
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

// The table fully formed

$tbl .= "$list</tbody></table>";

$S->title = "Show All Scores";
$S->banner = "<h1>$S->title</h1>";

$S->css =<<<EOF
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(14), #results td:nth-of-type(14) { background: lightpink; }
#results tbody td { text-align: right; }
#results tbody td:nth-of-type(2) { text-align: left; }
@media print {
  header, footer, hr, #printbtn, #return { display: none; }
  #teams {
    font-size: 12pt;
  }
}
EOF;

$S->b_script = <<<EOF
<script src="https://bartonphillips.net/tablesorter-master/dist/js/jquery.tablesorter.min.js"></script>
<link rel="stylesheet" href="https://bartonphillips.net/css/newtblsort.css">
<script>
  $("#results").tablesorter({sortList: [[14, 0]]});
</script>  
EOF;

[$top, $footer] = $S->getPageTopBottom();

// ******************
// This is the empty $_GET, which is the first page.
// If it is ME then add the send option to do the $_GET['send'] option.

if($email == "bartonphillips@gmail.com") {
  $showBulkEmailMsg = "<a href='sendemails2.php?email=$email'>Send Bulk Emails</a><br>";
}

echo <<<EOF
$top
<hr>
$team
$tbl
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
$showBulkEmailMsg
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
