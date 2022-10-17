<?php
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

$email = $_GET['email'];

if(empty($email) || !$S->query("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->query("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$h->title = "Show All Scores";
$h->banner = "<h1>$h->title</h1>";

$h->css =<<<EOF
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(14), #results td:nth-of-type(14) { background: lightpink; }
#results tbody td { text-align: right; }
#results tbody td:nth-of-type(2) { text-align: left; }
EOF;
$tbl =<<<EOF
<table id='results' border='1'>
<thead>
<tr><th>Team</th><th>Players</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan1</th><th>Jan2</th><th>Feb1</th><th>Feb2</th><th>Mar</th><th>Apr</th><th>May</th><th>Total</th></tr>
</thead
<tbody>
EOF;
  
$S->query("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team order by fkteam");
$r = $S->getResult();

while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
  $list .= "<tr><td>$team</td><td>$name1 & $name2</td>";
  
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
