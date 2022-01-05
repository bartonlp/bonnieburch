<?php
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$orgLines = <<<EOF
Arbit,Mel
Burch,Bonnie
Craft,Anna
Crosser,Bonnie
Donaldson,Patsy
Dorsey,Bill
Dupree,Gail
Duling,Pam
Eckberg,Dru
Fogleman,Joe
Furness,Howard
Gignilliat,John
Gordon,Heidi
Hausman,Fred
Herman,Olga
Hudson,Julia
Jacobs,Marilyn
Jambor,Paul
Jones,Ginny
Kerrick,Joann
Kirkman,Ken
Kleeman,Susanne
Lambert,Richard
Mistak,Becky
Moldestad,Tudi
Ohsol,Fred
Ohsol,Mary Ann
Pagnutti,Peter
Parker,Mary
Peterson,Carolyn
Phillips,Jim
Phillips,Barton
Riley,Mitchell
Schmidt,Jamie
Stockdale,Lowell
Tant,Sandra
Town,Chris
VanMiddlesworth,Bernie
Weil,Sissy
Waller,Joyce
Wengert,Bob
White,Gary
Wilkins,Ken
Wilkins,Dru
Willis,Ruth
Wingrove,Earl
Wooten,Judy
Wooten,Sim
Worthington,Eliza
Young,Mym
EOF;

$lines = explode("\n", $orgLines);
$S->query("drop table if exists bridge");
$sql = "create table bridge(id int not null auto_increment, fname varchar(255), lname varchar(255), primary key(id))";
$S->query($sql);
foreach($lines as $line) {
  [$lname, $fname] = explode(',', $line);
  $S->query("insert into bridge (fname, lname) values('$fname', '$lname')");
}

$S->query("select id, fname, lname from bridge");
while([$id, $nfname, $nlname] = $S->fetchrow('num')) {
  echo "$id, $nfname, $nlname<br>";
}
