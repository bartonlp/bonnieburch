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

// Start a session. We pass the information in $_SESSION['info']. See below.

session_start();

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

// The values from $_FILES['xx']['error']

$fileUploadErrors = [
                     0 => 'There is no error, the file uploaded with success',
                     1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                     2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                     3 => 'The uploaded file was only partially uploaded',
                     4 => 'No file was uploaded',
                     6 => 'Missing a temporary folder',
                     7 => 'Failed to write file to disk.',
                     8 => 'A PHP extension stopped the file upload.',
                    ];

//****************
// getheader()
// Parses the $info and returns an array of information

function getheader($info) {
  global $S;

  $errorMsg = '';
  $envelope["from"] = $from = "barton@bartonphillips.com";
  $to = "barton@bartonphillips.com";

  $subject = $info['subject'];
  $showallscores = $info['showallscores'];
  $sendto = $info['sendto'];
  $sendfile = $info['sendfile'];
  $texttosend = $info['texttosend'];
  $marathon = $info['marathon'];

  $date = date("Y-m-d");
  $msg = '';
  
  if(!empty($showallscores)) {
    if(!empty($texttosend)) {
      $msg = $texttosend;
    }

    // Check if marathone-msg.txt is pressent
    // This is a local file at bartonlp.org, bonnieburch.com/marathon/marathon-msg.txt
    
    if(file_exists("marathon-msg.txt")) {
      $ar = require("marathon-msg.txt");
      $msg .= $ar['msg'];
      $sal = $ar['sal'];
    }

    $S->query("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team order by fkteam");
    $r = $S->getResult();

    while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
      $list .= "<tr><td style='text-align: right; padding: 3px;'>$team</td><td style='text-align: left; padding: 3px'>$name1 & $name2</td>";

      $S->query("select score from scores where fkteam=$team order by moNo");
      $total = 0;

      // BLP 2022-08-26 - NOTE: if I do $score = $S->fetchrow('num')[0], I could get a zero back which looks like a
      // null and  would stop the while loop! So while I could do this ($score =
      // $S->fetchrow('num')[0]) !== null), it is probably safer to always use an array as the receiver
      // in a while loop. I think I have fixed all of my code.

      while([$score] = $S->fetchrow('num')) {
        $total += $score;
        $list .= "<td style='text-align: right; padding: 3px'>$score</td>";
      }
      $list .= "<td style='background: lightpink; text-align: right; padding: 3px'>$total</td></tr>";
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
  } else if($texttosend) {
    $contents = $texttosend;
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
    if($err = $_FILES['attachment']['error']) {
      $errorMsg .= $fileUploadErrors[$err];
    } else {
      $attachType = $_FILES['attachment']['type'];
      $attachFile = $_FILES['attachment']['tmp_name'];
      $part3["type"] = TYPEAPPLICATION;
      $part3["encoding"] = ENCBASE64;
      $part3["subtype"] = "octet-stream";
      $part3["description"] = basename($attachment);
      $part3["disposition.type"] = "attachment";
      $part3["disposition"] = ['filename'=>basename($attachment)];
      $part3["type.parameters"] = ['name'=>basename($attachment)];
      $part3["contents.data"] = base64_encode(file_get_contents($attachFile));
      $part3["bytes"] = strlen($part3["content.data"]);

      if(array_intersect([$attachType], ['image/jpeg', 'image/gif', 'image/png'])[0] !== null) {
        $uploaddir = '/var/www/bonnieburch.com/marathon/data/';
        $uploadfile = $uploaddir . basename($_FILES['attachment']['name']);

        if(!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadfile)) {
          echo "Move error<br>";
        }
      }
    }
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
    return ['HEADERS', $headers, 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc,
            'contents'=>$contents, 'attachment'=>$attachment, 'attachType'=>$attachType];
  }
}

//******************
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

//********************
// This is the GET from showAllScores.php it passes:
// send=true&showallscores=on&marathon=on&email={email address of user}

if($_GET["send"]) {
  $info = getheader($_GET);
  
  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
    goto ERROR;
  }

  $to = $info['to'];
  $subject = $info['subject'];
  $headers = $info[1];
  $contents = $info['contents'];
  $cc = $info['cc'];

  if(imap_mail("$to", "$subject", "", $headers) === false) {
    $errorMsg = "Error 'imap_mail'<br>" . imap_errors();
    goto ERROR;
  }

  $S->title = "Bulk Emails Sent";
  $S->banner = "<h1>$S->title</h1>";
  $S->css = "h3 { margin-bottom: 0; }";

  [$top, $footer] = $S->getPageTopBottom();

  echo <<<EOF
$top
<hr>
<h2>Subject: $subject</h2>
<h3 id="cc">CC:</h3>
$cc
<h3>Message:</h3>
$contents
<hr>
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
$footer
EOF;
  exit();

ERROR:
  $S->title = "Send Error";
  $S->banner = "<h1>$S->title</h1>";
  [$top, $footer] = $S->getPageTopBottom();
  echo <<<EOF
$top
<hr>
$errorMsg
<a id="return" href="marathon.php?page=auth&email=$email">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

//***********************
// FROM form POST 'sendit'
// The main pages does a post to 'sendpreview' which if everything looks OK does a POST to 'sendit'

if($_POST['sendit']) {
  $email = $_POST['email'];
  
  $info = $_SESSION['info'];
  $headers = $info[1];
  $to = $info['to'];
  $subject = $info['subject'];

  if(imap_mail("$to", "$subject", "", $headers) === false) {
    $errorMsg = "Error 'imap_mail'<br>" . imap_errors();
    $S->title = "Send Error";
    $S->banner = "<h1>$S->title</h1>";
    $msg = "<p>$errorMsg</p>";
  } else {
    $S->title = "Data Sent";
    $S->banner = "<h1>$S->title</h1>";
    $msg =<<<EOF
<h2>You information has been sent.</h2>
<a href="marathon.php?page=auth&email=$email">Return to Marathon</a>
EOF;
  }

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
$msg
<hr>
$footer
EOF;
  unlink("data/{$info['attachment']}"); // Unlink a file if it is in 'data/'. If nothing there we don't care.
  exit();
}

//*****************************
// FROM form POST 'sendpreview'
// The main program does a post to here. If all is OK this does a post to 'sendit'

if($_POST['sendpreview']) {
  $info = getheader($_POST);

  // $info[0] has either 'ERROR' or "HEADERS"
  
  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
    goto PREVIEW_END;
    
  } elseif($info[0] == "HEADERS") {
    $S->title = "Preview";
    $S->banner = "<h1>$S->title</h1>";
    $S->css =<<<EOF
button { border-radius: 5px; font-size: 20px; }
EOF;
    
    [$top, $footer] = $S->getPageTopBottom();

    // Here we set the session variable with $info form getheader()
    // This is how we pass the info to 'sendit'
    
    $_SESSION['info'] = $info;

    // Display the preview info and give the option to 'sendit'

    if($attach = $info['attachment']) {
      $attachFile = "data/$attach";
      $attachStr = "<p>Attachment:<br>$attach</p>";
      if(array_intersect([$info['attachType']], ['image/jpeg', 'image/gif', 'image/png'])[0] !== null) {
        $attachStr .= "<img src='$attachFile' style='width: 300px;'><br>";
      }
    }
    
    echo <<<EOF
$top
<hr>
<p>From: {$info['from']}<br>
Subject: {$info['subject']}<br>
CC: {$info['cc']}</p>
<p>Message:<br>{$info['contents']}</p>
$attachStr
<form method="POST">
<input type="hidden" name="email" value="$email">
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
<br><a href="sendmail.php?page=auth&email=$email">Return to Send Mail</a>
<hr>
$footer
EOF;
    exit();
  } else {
   // This should not happen but if it does just show everything in $info and quit
    
    echo "ERROR: ". print_r($info, true)."<br>";
    exit();
  }
  // We got errors in $errorMsg so pass it back to the main program.
  
PREVIEW_END:
}

//**************
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
<tr><td>Enter Text to send</td><td><textarea name="texttosend" rows="5" cols="50" placeholder="Enter Text"></textarea></td></tr>
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
