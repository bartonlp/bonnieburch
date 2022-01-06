<?php
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
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
while([$id, $name] = $S->fetchrow($r, 'num')) {
  echo "<h4>$id, $name</h4>";
  if($S->query("select date, lasttime from weeks where fid=$id order by date")) {
    echo "<ul>";
    while([$date, $lasttime] = $S->fetchrow('num')) {
      echo "<li>$date, $lasttime</li>";
    }
    echo "</ul><br>";
  }
}
echo "<a href='/bridge'>Return to Home</a>";