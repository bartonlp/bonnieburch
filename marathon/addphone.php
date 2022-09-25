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

if($_POST['page'] == 'submit') {
  $phone1 = $_POST['phone1'];
  $phone2 = $_POST['phone2'];
  $email = $_POST['email'];
  
  for($i=1; $i<13; ++$i) {
    if(empty($phone1[$i]) && empty($phone2[$i])) continue;
    
    if(empty($phone1[$i])) {
      $S->query("update teams set phone2='$phone2[$i]', lasttime=now() where team=$i");
    } elseif(empty($phone2[$i])) {
      $S->query("update teams set phone1='$phone1[$i]', lasttime=now() where team=$i");
    } else {
      $S->query("update teams set phone1='$phone1[$i]', phone2='$phone2[$i]', lasttime=now() where team=$i");
    }
  }
  $h->title = "Add Phone POST";
  $h->banner = "<h1>$h->title</h1>";
  [$top, $footer] = $S->getPageTopBottom($h);
  
  echo <<<EOF
$top
<hr>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
  
  exit();
}

function callback(&$row, &$desc) {
  $row['phone1'] = "<input class='phone1' name='phone1[".$row["team"]."]'>";
  $row['phone2'] = "<input class='phone2' name='phone2[".$row["team"]."]'>";
}

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from teams", ['callback'=>'callback', 'attr'=>['id'=>'teams', 'border'=>'1']])[0];

$h->title = "Add Phone";
$h->banner = "<h1>$h->title</h1>";
$h->css =<<<EOF
 #teams th, #teams td { padding: 0 5px; }
 .phone1, .phone2 { font-size: 20px; width: 150px; }
 button { border-radius: 5px; background: green; color: white; font-size: 20px; }
EOF;
$h->script = <<<EOF
  <script src="https://bartonphillips.net/js/allnatural/js/maskedinput.js"></script>
  <script>
jQuery(document).ready(function($) {
  $(".phone1").mask("(999) 999-9999");
  $(".phone2").mask("(999) 999-9999");
});
  </script>
EOF;

$email = $_GET['email'];

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
<form method='post'>
$tbl
<input type='hidden' name='email' value='$email'>
<button type='submit' name='page' value='submit'>Submit</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;

