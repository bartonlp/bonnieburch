<?php
// BLP 2023-02-23 - Using new approach
// Show All of the Scores. They can be printed or emailed to everyone.
// This uses the file 'marathon-msg.txt' which has the message and salutation that appear on the
// Bulk Email. 
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

$tbl =<<<EOF
<table id='results' border='1'>
<thead>
<tr><th>Team</th><th>Players</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan1</th><th>Jan2</th><th>Feb1</th><th>Feb2</th><th>Mar</th><th>Apr</th><th>May</th><th>Total</th></tr>
</thead
<tbody>
EOF;
  
$S->query("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team order by fkteam");
$r = $S->getResult();

while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
  $list .= "<tr><td>$team</td><td>$name1 & $name2</td>";
  
  $S->query("select score from scores where fkteam=$team order by moNo");
  $total = 0;

  // BLP 2022-08-26 - NOTE: if I do $score = $S->fetchrow('num')[0], I could get a zero back which looks like a
  // null and  would stop the while loop! So while I could do this ($score =
  // $S->fetchrow('num')[0]) !== null), it is probably safer to always use an array as the receiver
  // in a while loop. I think I have fixed all of my code.
    
  while([$score] = $S->fetchrow('num')) {
    $total += $score;
    $list .= "<td>$score</td>";
  }
  $list .= "<td>$total</td></tr>";
}

// The table fully formed

$tbl .= "$list</tbody></table>";

// Send a Bulk Email.

if($_GET['send']) {
  // Create the css for the email.
  
  $css =<<<EOF
<style>
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(14), #results td:nth-of-type(14) { background: lightpink; }
#results tbody td { text-align: right; }
#results tbody td:nth-of-type(2) { text-align: left; }
</style>
EOF;

  // Check if marathone-msg.txt is pressent
  
  if(file_exists("marathon-msg.txt")) {
    // If it is requior it. It has 'msg', and 'sal'
  
    $ar = require("marathon-msg.txt");
    $msg = $ar['msg'];
    $sal = $ar['sal'];
  }

  // Finish up the Email we are sending.
  
  $tbl =<<<EOF
$css
$msg
$tbl
$sal
EOF;

  $S->title = "Send Emails";
  $S->banner = "<h1>$S->title</h1>";

  [$top, $footer] = $S->getPageTopBottom();

  $envelope["from"]= "barton@bartonphillips.org";

  $date = date("m-d-Y");

  $subject = "Current Scores as of $date";

  $tmp = '';
    
  if($S->query("select email1, email2 from teams")) {
    while([$email1, $email2] = $S->fetchrow('num')) {
      if(!empty($email1)) {
        $tmp .= "$email1,";
      }
      if(!empty($email2)) {
        $tmp .= "$email2,";
      }
    }
  }
  $envelope["cc"] = rtrim($tmp, ",");
  //$envelope["cc"] = "barton@bartonphillips.com";
  
  $part1["type"] = TYPEMULTIPART;
  $part1["subtype"] = "alternative";

  $part2["type"] = TYPETEXT;
  $part2["encoding"] = ENC7BIT;
  $part2["subtype"] = "html"; 
  $part2["contents.data"] = $tbl;

  // Make the body parts
  
  $body[] = $part1;
  $body[] = $part2;

  // Create the header from the envelope and the body parts
  
  $headers = imap_mail_compose($envelope, $body);

  // This is the main person we are sending this to.
  
  $to = "bartonphillips@gmail.com";

  if(imap_mail("$to", "$subject", "", $headers) === false) {
    echo "Error<br>" . imap_errors();
    exit();
  }

  $tmp = preg_replace("~,~", "<br>", $envelope['cc']);

  echo <<<EOF
$top
<hr>
$tbl
<hr>
$footer
EOF;
  
  exit();
}

$S->title = "Show All Scores";
$S->banner = "<h1>$S->title</h1>";

$S->css =<<<EOF
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(14), #results td:nth-of-type(14) { background: lightpink; }
#results tbody td { text-align: right; }
#results tbody td:nth-of-type(2) { text-align: left; }
@media print {
  header, footer, hr, #printbtn, #return { display: none; }
  #teams {
    font-size: 12pt;
  }
}
EOF;

if($email == "bartonphillips@gmail.com") {
  $showBulkEmailMsg = "<a href='showAllScores.php?send=true&email=$email'>Send Bulk Emails</a><br>";
}

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
$team
$tbl
<br>
<input type='image' id='printbtn' src='https://bartonphillips.net/images/print.gif' onclick='window.print()' style='width: 100px'/><br>
$showBulkEmailMsg
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
