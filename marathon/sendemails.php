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

$_site = require_once(getenv("SITELOADNAME"));

if($_POST["send"]) {
  $envelope["from"]= "barton@bartonphillips.org";
  $subject = $_POST['subject'];
  $filename = $_POST['filename'];
  $contents = file_get_contents($filename);

  if($attachment = $_POST['attachment']) {
    $attachContents = file_get_contents($attachment);
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
  
  if($_POST['sendto']) {
    $envelope["cc"]  = $_POST['sendto'];
  } elseif($_POST['sendfile']) {
    $tmp = file_get_contents($_POST['sendfile']);
    $envelope["cc"]  = rtrim(preg_replace(["~\n~m", "~, *~"], ",", $tmp), ",");
  } elseif($_POST['marithon']) {
    // Lookup email addresses in the teams table.
    $S = new Database($_site);
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
      $envelope["cc"] = rtrim($tmp, ",");
    } else {
      echo "<h1>NO valid email addresses found in the teams table?</h1>";
      exit();
    }
  } else {
    $errorMsg = "<h1>You must provide either a CC list or a filename</h1>";
    goto END;
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

  $to = "bartonphillips@gmail.com";

  if(imap_mail("$to", "$subject", "", $headers) === false) {
    echo "Error<br>" . imap_errors();
    exit();
  }

  $tmp = preg_replace("~,~", "<br>", $envelope['cc']);
  echo "<h1>Mail Sent to $to</h1><p>CC:<br>$tmp</p><p>Attachment: $attachment</p>";
  exit();
  
END:  
}

// Start of Page

$S = new SiteClass($_site);

$S->title = "Send Bulk Emails";  
$S->banner = "<h1>$S->title</h1>";
$S->css =<<<EOF
form table input { width: 1000px; font-size: 30px; }
td:first-of-type { padding-right: 20px; }
input[type="checkbox"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
form button { padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
$errorMsg
<p>The CC (Carbon Copies) must be seperated by commas. The entries in the CC-file can be seperated by commas or new lines.</p>
<form method="POST">
<table>
<tr><td>Enter Filename to Send</td><td><input type="text" name="filename" value="$filename" required></td></tr>
<tr><td>Enter CC: </td><td><input type="text" name="sendto" data-form-type="other"></td></tr>
<tr><td>OR Enter Filename with CC: </td><td><input type="text" name="sendfile" data-form-type="other"></td></tr>
<tr><td>OR CC: to Marithon group</td><td><input type="checkbox" name="marithon"></td><tr>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject" required></td></tr>
<tr><td>Attachment File</td><td><input type="text" name="attachment"</td></tr>
</table>
<br>
<button id="send" type="submit" name="send" value="true">Send Email</button>
</form>
<hr>
$footer
EOF;
