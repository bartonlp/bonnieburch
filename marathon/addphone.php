<?php
// BLP 2023-02-23 - using new approach
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

// Do Auth via email first.

$email = $_GET['email'];

if(empty($email) || !$S->query("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->query("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$T = new dbTables($S);

// Submit the changes.

if($_POST['page'] == 'submit') {
  //vardump("POST", $_POST);
  $phone1 = $_POST['phone1'];
  $phone2 = $_POST['phone2'];
  $email1 = $_POST['email1'];
  $email2 = $_POST['email2'];
  
  $email = $_POST['email'];

  $updates = ''; // String to show who has been updated
  
  for($i=1; $i<13; ++$i) {
    // If everything is empty skip.
    
    if(empty($phone1[$i]) && empty($phone2[$i]) && empty($email1[$i]) && empty($email2[$i])) continue;

    // Get the original info.
    
    $S->query("select name1, name2, email1, email2, phone1, phone2 from teams where team=$i");
    [$name1, $name2, $e1, $e2, $p1, $p2] = $S->fetchrow('num');

    $str = '';

    // Is the new info different from the old info?
    
    if($phone1[$i] != $p1) $str .= "phone1='$phone1[$i]',";
    if($phone2[$i] != $p2) $str .= "phone2='$phone2[$i]',";
    if($email1[$i] != $e1) $str .= "email1='$email1[$i]',";
    if($email2[$i] != $e2) $str .= "email2='$email2[$i]',";

    // If no change do next team

    if(empty($str)) continue;

    $msg = rtrim($str, ","); // strip off the trailing ','
    $updates .= "Team: $i, $name1, $name2, str=$msg<br>";

    // UPDATE the table.
    
    $S->query("update teams set $str lasttime=now() where team=$i");
  }

  $S->title = "Add Phone POST";
  $S->banner = "<h1>$S->title</h1>";

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<h2>Updated</h2>
<div>$updates</div>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
  
  exit();
}

// Callback tor $T. Make the input fields.

function callback(&$row, &$desc) {
  $row['phone1'] = "<input class='phone1' data-form-type='other' name='phone1[".$row["team"]."]' value='". $row["phone1"]."'>";
  $row['phone2'] = "<input class='phone2' data-form-type='other' name='phone2[".$row["team"]."]' value='". $row["phone2"]."'>";
  $row['email1'] = "<input class='email1' data-form-type='other' name='email1[".$row["team"]."]' value='". $row["email1"]."'>";
  $row['email2'] = "<input class='email2' data-form-type='other' name='email2[".$row["team"]."]' value='". $row["email2"]."'>";
}

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from teams", ['callback'=>'callback', 'attr'=>['id'=>'teams', 'border'=>'1']])[0];

$S->title = "Add Phone";
$S->banner = "<h1>$S->title</h1>";
$S->css =<<<EOF
 #teams th, #teams td { padding: 0 5px; }
 .phone1, .phone2, .email1, .email2 { font-size: 20px; width: 150px; height: 40px; padding: 0 5px; border: none; cursor: pointer; }
 .email1, .email2 { width: 350px; }
 button { border-radius: 5px; background: green; color: white; font-size: 25px; }
EOF;

// maskedinput.js formats the phone items

$S->h_script = <<<EOF
  <script src="https://bartonphillips.net/js/allnatural/js/maskedinput.js"></script>
  <script>
jQuery(document).ready(function($) {
  $(".phone1").mask("(999) 999-9999");
  $(".phone2").mask("(999) 999-9999");
});
  </script>
EOF;

[$top, $footer] = $S->getPageTopBottom();

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
