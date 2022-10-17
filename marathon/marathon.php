<?php
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

// If $_POST or $_GET check authorization.
// This file is called from each of the other pages: addphone.php, enterBulkScores.php,
// enterScores.php, showAllScores.php, showScores.php, and showTeams.php via $_GET. Only start()
// uses $_POST.

if($_POST['page'] == 'auth' || $_GET['page'] == 'auth') {
  $S = new Database($_site);

  $email = $_POST['email'] ?? $_GET['email'];
  
  if(empty($email)) {
    youareok(false, 'NO_EMAIL');
    exit();
  }
  
  if($S->query("select team, name1, name2 from teams where email1='$email' or email2='$email'")) {
    [$team, $name1, $name2] = $S->fetchrow('num');

    youareok(true, $email, $team, $name1, $name2);
  } else {
    youareok(false, $email);
  }
  exit();
}

// We have only two functions start() and yourarok()

function start():never {
  global $_site, $h, $b;
  $S = new SiteClass($_site);
  
  $h->title = "Marathon Bridge";
  $h->css =<<<EOF
.center { text-align: center; }
button {
  border-radius: 5px;
  background: green;
  color: white;
}
input { width: 350px; }
input, button { font-size: 20px; }
EOF;
    
  [$top, $footer] = $S->getPageTopBottom($h, $b);

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
  exit();
}

// This is the other function. It checks to see if $ok is true or false and then either logs an
// error in the badplayer table or outputs the OK page.

function youareok(bool $ok, string $email, ?int $team=null, ?string $name1=null, ?string $name2=null): never {
  global $_site, $h, $b;
  $S = new SiteClass($_site);

  $h->title = "Marathon Bridge";
  $h->banner = "<h1>Marathon Bridge</h1>";
  $h->css = ".center {text-align: center;}";
  
  [$top, $footer] = $S->getPageTopBottom($h, $b);

  if(!$ok) {
    //You are NOT OK

    $S->query("insert into $S->masterdb.badplayer (ip, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
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
  
  if($email == 'bartonphillips@gmail.com') { // Is it me?
    $onlyBlp =<<<EOF
<br><a href="enterBulkScores.php?email=$email">Enter Bulk Scores</a><br>
<a href="addphone.php?email=$email">Add/Edit Phone nubers in Teams</a>
EOF;
  }

  // Render the OK page. It it is me add the $onlyBlp links
  
  echo <<<EOF
$top
<hr>
<h1 class='center'>Your are OK</h1>
<p>Your are team $team.<br>
Your team consists of $name1 and $name2.<br>
If this is all correct you can proceed to <b>Enter Scores</b>, <b>Show Spreadsheet</b> or <b>Show Teams</b><br>
If this is NOT CORRECT please email bartonphillips@gmail.com.</p>
<hr>
<!--<a href="enterScores.php?team=$team&name1=$name1&name2=$name2&email=$email">Enter Your Team Scores</a><br>
<a href="showScores.php?team=$team&name1=$name1&name2=$name2&email=$email">Show All Of Your Scores</a><br>-->
<a href="showAllScores.php?email=$email">Show All Scores</a><br>
<a href="showTeams.php?team=$team&name1=$name1&name2=$name2&email=$email">Show Teams</a><br>
<a href="whoplayswho.php?email=$email">Who Plays Whom</a>
$onlyBlp
<br><br><a href="marathon.php">Return to main page</a>
<hr>
$footer
EOF;
  exit();
}

start();

