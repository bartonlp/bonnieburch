<?php
// Send out bulk emails to Marathon Members.
// I lookup the email addresses for members from the teams tables.
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

session_start();

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

function getheader($info) {
  global $S;

  $errorMsg = '';
  $envelope["from"] = $from = "barton@bartonphillips.org";
  $to = "bartonphillips@gmail.com";

  $subject = $info['subject'];
  $showallscores = $info['showallscores'];
  $sendto = $info['sendto'];
  $sendfile = $info['sendfile'];
  $marathon = $info['marathon'];

  $date = date("Y-m-d");

  if($showallscores) {
/*    $css =<<<EOF
<style>
#results th, #results td { padding: 0 5px; }
#results th:nth-of-type(14), #results td:nth-of-type(14) { background: lightpink; }
#results tbody td { text-align: right; }
#results tbody td:nth-of-type(2) { text-align: left; }
</style>
EOF;
*/
    // Check if marathone-msg.txt is pressent
    // This is a local file at bartonlp.org, bonnieburch.com/marathon/marathon-msg.txt
    
    if(file_exists("marathon-msg.txt")) {
      $ar = require("marathon-msg.txt");
      $msg = $ar['msg'];
      $sal = $ar['sal'];
    }

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

    $contents =<<<EOF
$css
$msg
<table id='results' border='1'>
<tbody>
$list
</tbody>
</table>
$sal
EOF;

    $subject = "Current Scores as of $date";
  } else {
    if(empty($_FILES['filename']['name'])) {
      $errorMsg .= "<h2>You must supply a 'Send to Filename' or check 'Show All Scores'</h2>";
    } else {
      $contents = file_get_contents($_FILES['filename']['tmp_name']);
    }
  }

  if(empty($subject)) {
    $errorMsg .= "<h2>No valid 'Subject'</h2>";
  }

  if($attachment = $_FILES['attachment']['name']) {
    $attachContents = file_get_contents($_FILES['attachment']['tmp_name']);
    $part3["type"] = TYPEAPPLICATION;
    $part3["encoding"] = ENCBASE64;
    $part3["subtype"] = "octet-stream";
    $part3["description"] = basename($attachment);
    $part3["disposition.type"] = "attachment";
    $part3["disposition"] = ['filename'=>basename($attachment)];
    $part3["type.parameters"] = ['name'=>basename($attachment)];
    $part3["contents.data"] = base64_encode($attachContents);
    $part3["bytes"] = strlen($part3["content.data"]);
  }

  if($sendto) {
    $cc = $sendto;
    $envelope["cc"]  = $cc;
  } elseif($sendfile) {
    $cc = file_get_contents($sendfile);
    $envelope["cc"]  = rtrim(preg_replace(["~\n~m", "~, *~"], ",", $cc), ",");
  } elseif($marathon) {
    // Lookup email addresses in the teams table.

    $cc = '';
    
    if($S->query("select email1, email2 from teams")) {
      while([$email1, $email2] = $S->fetchrow('num')) {
        if(!empty($email1)) {
          $cc .= "$email1,";
        }
        if(!empty($email2)) {
          $cc .= "$email2,";
        }
      }
      $envelope["cc"] = rtrim($cc, ",");
    } else {
      $errorMsg .= "<h2>NO valid email addresses found in the 'marathon' database?</h2>";
    }
  } else {
    $errorMsg .= "<h2>You must provide either a CC list, a Filename or check 'Marathon group'</h2>";
  }

  $part1["type"] = TYPEMULTIPART;
  $part1["subtype"] = "alternative";

  $part2["type"] = TYPETEXT;
  $part2["encoding"] = ENC7BIT;
  $part2["subtype"] = "html"; 
  $part2["description"] = basename($filename);
  $part2["contents.data"] = $contents;
  
  $body[] = $part1;
  $body[] = $part2;

  if($part3) {
    $body[] = $part3;
  }

  $headers = imap_mail_compose($envelope, $body);

  if(!empty($errorMsg)) {
    return ['ERROR', $errorMsg];
  } else {
    if($part3["contents.data"]) $attStr = $part3['connects.data'];
    return ['HEADERS', $headers, 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc, 'contents'=>$contents, 'attachment'=>$attStr];
  }
}

// Check Authorized

$email = $_REQUEST['email'];

if(empty($email) || !$S->query("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->query("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

// Use $_REQUEST because this can be a POST from this file or a GET from showAllScores.php

if($_GET["send"]) {
  $info = getheader($_GET);
  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
    goto END;
  }
  $to = $info['to'];
  $subject = $info['subject'];
  $headers = $info[1];
  $contents = $info['contents'];
  $cc = $info['cc'];
  
  if(imap_mail("$to", "$subject", "", $headers) === false) {
    $errorMsg = "Error 'imap_mail'<br>" . imap_errors();
    goto END;
  }

  $S->title = "Bulk Emails Sent";
  $S->banner = "<h1>$S->title</h1>";

  [$top, $footer] = $S->getPageTopBottom();

  echo <<<EOF
$top
<hr>
<h2>$subject</h2>
<h3>Mail Sent to $to</h3>
<h4>CC:</h4>
$cc
<h4>Message:</h4>
$contents
<hr>
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
$footer
EOF;
  exit();

END:  
}

// FROM form POST 'sendit'

if($_POST['sendit']) {
  $email = $_POST['email'];
  
  $info = $_SESSION['info'];
  $headers = $info[1];
  $to = $info['to'];
  $subject = $info['subject'];

  if(imap_mail("$to", "$subject", "", $headers) === false) {
    $errorMsg = "Error 'imap_mail'<br>" . imap_errors();
    goto END;
  }

  $S->title = "Data Sent";
  $S->banner = "<h1>$S->title</h1>";
  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<h2>You information has been sent.</h2>
<a href="marathon.php?page=auth&email=$email">Return to Marathon</a>
$footer
EOF;
  exit();
}

// FROM form POST 'sendpreview'

if($_POST['sendpreview']) {
  $info = getheader($_POST);
//  vardump("info", $info);
//  echo "***<br>";

  if($info[0] == 'ERROR') {
    vardump('ERROR', $info[1]);
    exit();
  } elseif($info[0] == "HEADERS") {
    //vardump('HEADERS', $info[1]);
    //echo "to={$info['to']}, subject={$info['subject']}<br>";

    $S->title = "Preview";
    $S->banner = "<h1>$S->title</h1>";

    [$top, $footer] = $S->getPageTopBottom();

    $_SESSION['info'] = $info;
    
    echo <<<EOF
$top
<p>To: {$info['to']}<br>
From: {$info['from']}</p>
Subject: {$info['subject']}<br>
CC: {$info['cc']}</p>
<p>{$info['contents']}</p>
<form method="POST">
<input type="hidden" name="email" value="$email">
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
$footer
EOF;
    exit();
  } else {
    echo "ERROR: ". print_r($info, true)."<br>";
    exit();
  }
  exit();
}

// Start of Page

$S->title = "Send Bulk Emails";  
$S->banner = "<h1>$S->title</h1>";
$S->css =<<<EOF
form table input { width: 1000px; font-size: 30px; }
td:first-of-type { padding-right: 20px; }
input[type="checkbox"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
form button { padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

if($marathon) {
  $marathonStr = "checked";
}
if($showallscores) {
  $showallscoresStr = "checked";
}
echo <<<EOF
$top
<hr>
$errorMsg
<p>The CC (Carbon Copies) must be seperated by commas. The entries in the CC-file can be seperated by commas or new lines.</p>
<form enctype="multipart/form-data" method="POST">
<table>
<tr><td>Enter Send to Filename</td><td><input type="file" name="filename"></td></tr>
<tr><td>OR Send 'Show All Scores'</td><td><input type="checkbox" name="showallscores" $showallscoresStr></td></tr>
<tr><td>Enter CC: </td><td><input type="text" name="sendto" value="$sendto" data-form-type="other"></td></tr>
<tr><td>OR Enter Filename with CC: </td><td><input type="text" name="sendfile" value="$sendfile" data-form-type="other"></td></tr>
<tr><td>OR CC: to Marathon group</td><td><input type="checkbox" name="marathon" $marathonStr></td><tr>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject"></td></tr>
<tr><td>Attachment File</td><td><input type="file" name="attachment"></td></tr>
</table>
<br>
<input type="hidden" name="email" value="$email">
<button id="send" type="submit" name="sendpreview" value="true">Send Email</button>
</form>
<hr>
$footer
EOF;
