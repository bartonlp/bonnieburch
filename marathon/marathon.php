<?php
// BLP 2023-02-23 - Using new approach
/*
CREATE TABLE `teams` (
  `team` int NOT NULL,
  `name1` varchar(100) NOT NULL,
  `name2` varchar(100) NOT NULL,
  `email1` varchar(100) NOT NULL,
  `email2` varchar(100) NOT NULL,
  `created` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  PRIMARY KEY (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `scores` (
  `fkteam` int NOT NULL,
  `month` varchar(20) NOT NULL,
  `score` int DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME")); // Get Startup info from mysitemap.json
$S = new SiteClass($_site);

// If $_POST or $_GET check authorization.
// This file is called from each of the other pages: addphone.php, enterBulkScores.php,
// enterScores.php, showAllScores.php, showScores.php, and showTeams.php via $_GET. Only the Start
// page uses $_POST to authenticate.

if($_REQUEST['page'] == 'auth') {
  $email = $_REQUEST['email'];

  if(empty($email)) {
    youareok(false, 'NO_EMAIL');
    exit();
  }

  // If the email address in in the team table you are OK.
  
  if($S->sql("select team, name1, name2 from teams where email1='$email' or email2='$email'")) {
    [$team, $name1, $name2] = $S->fetchrow('num');

    youareok(true, $email, $team, $name1, $name2);
  } else {
    // If you are not then 
    youareok(false, $email);
  }
  exit();
}

// Checks to see if $ok is true or false and then either logs an
// error in the badplayer table or outputs the OK page.

function youareok(bool $ok, string $email, ?int $team=null, ?string $name1=null, ?string $name2=null): never {
  global $S;

  $S->title = "Marathon Bridge";
  $S->banner = "<h1>Marathon Bridge</h1>";
  $S->css = ".center {text-align: center;} .red { color: red; }";
  
  [$top, $footer] = $S->getPageTopBottom();

  if(!$ok) {
    //You are NOT OK

    $S->sql("insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', 'counted', '$S->self', 1, -1, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

    error_log("marathon.php: $S->ip, 'NOT_AUTH', $errmsg, $S->agent");
                
    echo <<<EOF
$top
<hr>
<h1>Sorry $name you are not authorized</h1>
<a href="marathon.php">Return to main page</a>
<hr>
$footer
EOF;
    exit();
  }

  // You Are OK!
  $greeting = "<h1 class='center'>$greeting</h1>";
  if($email == 'bonnieburch2015@gmail.com') { // Is it bonnie?
    $greeting = "<h1 class='center red'>Hello Bonnie. You are the Administrator</h1>";
    $onlyBlp =<<<EOF
<br><a href="enterBulkScores.php?email=$email">Enter Bulk Scores</a><br>
<a href="addphone.php?email=$email">Add/Edit Phone numbers in Teams</a><br>
<a href="sendmails-sendgrid.php?email=$email">Send Bulk Emails</a><br>
EOF;
  }

  // Render the OK page. It it is me add the $onlyBlp links
  
  echo <<<EOF
$top
<hr>
$greeting
<p>Your are team $team.<br>
Your team consists of $name1 and $name2.<br>
If this is all correct you can proceed to <b>Enter Scores</b>, <b>Show Spreadsheet</b> or <b>Show Teams</b><br>
If this is NOT CORRECT please email bartonphillips@gmail.com.</p>
<hr>
<a href="showAllScores.php?email=$email">Show All Scores</a><br>
<a href="showTeams.php?email=$email">Show Teams</a><br>
<a href="whoplayswho.php?email=$email">Who Plays Whom</a>
$onlyBlp
<br><br><a href="marathon.php">Return to main page</a>
<hr>
$footer
EOF;
  exit();
}

// Start Page

$S->title = "Marathon Bridge";
$S->css =<<<EOF
.center { text-align: center; }
button {
  border-radius: 5px;
  background: green;
  color: white;
}
input { width: 350px; }
input, button { font-size: 20px; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

// Call auth

echo <<<EOF
$top
<hr>
<form method='post'>
Login with your email address: <input type='text' name='email'><br>
<button type='submit' name='page' value='auth'>Continue</button>
</form>
<hr>
$footer
EOF;

