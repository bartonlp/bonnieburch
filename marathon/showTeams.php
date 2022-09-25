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

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from teams", ['attr'=>['id'=>'teams', 'border'=>'1']])[0];

$h->title = "Show Teams";
$h->banner = "<h1>$h->title</h1>";
$h->css =<<<EOF
#teams { font-size: calc(18px + .4vw); }
#teams th, #teams td { padding: 0 5px; }
@media(max-width:  1850px) {
  #teams { font-size: 15px; }
}
EOF;

$email = $_GET['email'];

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
$tbl
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;

