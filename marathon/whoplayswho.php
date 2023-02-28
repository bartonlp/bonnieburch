<?php
// BLP 2023-02-24 - use new approach
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

$S->title = "Who Plays Whom";
$S->banner = "<h1>$S->title</h1>";

$S->css =<<<EOF
@page { size: landscape; margin: .1in .125in .1in .125in; }
@media print {
  header, footer, hr, #info, #printbtn, #return { display: none; }
  #who {
    font-size: 8pt;
    width: 100%;
  }
}  
EOF;

$whoHosts = ['Sep' => [[1,2], [3,4], [5,6], [7,8], [9,10], [11,12]],
             'Oct' => [[2,3], [4,5], [6,7], [8,9], [10,11], [12,1]],
             'Nov' => [[3,1], [5,2], [4,10], [9,7], [11,8], [6,12]],
             'Dec' => [[7,3], [8,5], [10,6], [2,9], [1,11], [12,4]],
             'Jan1' => [[11,6], [5,3], [2,8], [4,7], [12,9], [10,1]],
             'Jan2' => [[7,1], [10,12], [3,9], [4,2], [11,5], [6,8]],
             'Feb1' => [[1,6], [9,11], [2,7], [8,4], [5,10], [12,3]],
             'Feb2' => [[1,8], [10,2], [7,12], [5,9], [11,3], [4,6]],
             'Mar' => [[9,4], [3,10], [6,2], [12,8], [11,7], [1,5]],
             'Apr' => [[8,3], [5,12], [4,1], [9,6], [2,11], [7,10]],
             'May' => [[1,9], [6,3], [10,8], [5,7], [11,4], [12,2]]];

foreach($whoHosts as $key=>$value) {
  $tbl .= "<tr><td>$key</td>";
  foreach($value as $v) {
    $tbl .= "<td>*$v[0] & $v[1]<br>";
    $S->query("select name1, name2 from marathon.teams where team = '$v[0]'");
    [$name1a, $name2a] = $S->fetchrow('num');
    $S->query("select name1, name2 from marathon.teams where team = '$v[1]'");
    [$name1b, $name2b] = $S->fetchrow('num');
    $tbl .= "$name1a & $name2a vs $name1b & $name2b</td>";
  }
  $tbl .= "</tr>";
}

$tbl =<<<EOF
<table id='who' border='1'>
<tbody>
$tbl
</tbody>
</table>
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
$tbl
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;

    
         

