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
*/

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
$T = new dbTables($S);

$email = $_GET['email'];

if(empty($email) || !$S->query("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->query("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from teams", ['attr'=>['id'=>'teams', 'border'=>'1']])[0];

$h->title = "Show Teams";
$h->banner = "<h1>$h->title</h1>";
$h->css =<<<EOF
#teams { font-size: calc(18px + .4vw); }
#teams th, #teams td { padding: 0 5px; }
@media(max-width:  1850px) {
  #teams { font-size: 15px; }
}
@media print {
  header, footer, hr, #printbtn, #return { display: none; }
  #teams {
    font-size: 12pt;
  }
}
EOF;

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
$tbl
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
<a id="return"href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;

