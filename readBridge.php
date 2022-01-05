<?php
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$S->query("select id, fname, lname from bridge");
$r = $S->getResult();

echo "<h1>This is just RAW output</h1>";
while([$id, $nfname, $nlname] = $S->fetchrow($r, 'num')) {
  echo "<h4>$id, $nfname, $nlname</h4>";
  if($S->query("select date, cash, lasttime from weeks where fid=$id order by date")) {
    echo "<ul>";
    while([$date, $cash, $lasttime] = $S->fetchrow('num')) {
      echo "<li>$date, $cash, $lasttime</li>";
    }
    echo "</ul><br>";
  }
}
echo "<a href='/bridge'>Return to Home</a>";