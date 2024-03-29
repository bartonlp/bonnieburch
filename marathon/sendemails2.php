<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

//****************
// getheader()
// Parses the $info and returns an array of information

function getheader($info) {
  global $S;

  // Small function to check if the text has markup.
  // It returns the contents after fixing the txt.
  
  function checktext(string $txt):string {
    $contents = '';

    $r = preg_match("~<.*>~m", $txt);
    if($r === false) exit("preg_match failed: " . __LINE__);

    if($r === 0) { // BLP 2023-10-07 - no match.
      $ar = explode("\n", $txt);
      foreach($ar as $a) {
        $a = rtrim($a);
        $contents .= "$a<br>";
      }
    } else { // BLP 2023-10-07 - match found
      $ar = explode("\n", $txt); // BLP 2023-10-07 - break $txt appart

      foreach($ar as $a) {
        $a = rtrim($a);
        if(preg_match("~^<.*>$~", $a) === 0) { // Not found
          $contents .= "$a<br>";
        } else { // found
          $contents .= $a;
        }
      }
    }

    $contents = preg_replace("~\"~m", "&amp;quot;", $contents);
    $contents .= "<br>";
    file_put_contents("./data/lasttext.data", $txt);

    return $contents;
  }

  $errorMsg = '';
  $from =  "Marathon@mail.bartonphillips.com";
  $to = "barton@bartonphillips.com";

  // Make the table of scores
  
  $S->sql("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team order by fkteam");
  $r = $S->getResult();

  while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
    $list .= "<tr><td style='text-align: right; padding: 3px;'>$team</td><td style='text-align: left; padding: 3px'>$name1 & $name2</td>";

    $S->sql("select score from scores where fkteam=$team order by moNo");
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

  $contents = checktext($info['texttosend']);

  // The table fully formed

  $contents = "$contents<table id='results' border='1'><tbody>$list</tbody></table>$sal";

  $subject = $info['subject'];

  $date = date("Y-m-d");
  $subject = "$subject (Current Scores as of $date)";

  $cc = '';
  
  if($info['radio'] == "marathon") {
    if($S->sql("select email1, email2 from teams")) {
      while([$email1, $email2] = $S->fetchrow('num')) {
        if(!empty($email1)) {
          $cc .= "$email1,";
        }
        if(!empty($email2)) {
          $cc .= "$email2,";
        }
      }
      $cc = rtrim($cc, ",");
    }
  } elseif($info['radio'] == "individual") {
    $cc = $info['sendto'];
  } else {
    $errorMsg .= "<h2>You must select either 'Marathon group' or 'individuals'</h2>";
  }
  
  if(empty($subject)) {
    $errorMsg .= "<h2>No valid 'Subject'</h2>";
  }

  $recipients = "{\"address\": {\"email\": \"$to\",\"header_to\": \"$to\"}},";
  
  foreach(explode(',', $cc) as $c) {
    $recipients .= "{\"address\": {\"email\": \"$c\",\"header_to\": \"$to\"}},";
  }

  $recipients = rtrim($recipients, ',');

  if(!empty($errorMsg)) {
    return ['ERROR', $errorMsg];
  } else {
    $post =<<<EOF
{"recipients": [
  $recipients
],
  "content": {
    "from": "Marathon@mail.bartonphillips.com",
    "reply_to": "Barton Phillips<bartonphillips@gmail.com>",
    "headers": {
      "CC": "$cc"
    },
    "subject": "$subject",
    "text": "View This in HTML Mode",
    "html": "$contents"
  }
}
EOF;
    return ['HEADERS', $post, 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc,
            'contents'=>$contents];
  }
}

//******************
// Check Authorized

$email = $_REQUEST['email'];

if(empty($email) || !$S->sql("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->sql("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
            "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
            "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

// BLP 2023-10-07 - From inlineScript Ajax

if($_POST['past']) {
  $txt = file_get_contents("./data/lasttext.data");
  echo $txt;
  exit();
}

//*****************************
// FROM form POST main page to 'sendpreview'
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
button { border-radius: 10px; font-size: 25px; color: white; background: green; width: 100px; height: 50px; margin-top: 20px; }
#msg { margin-bottom: 0; padding-bottom: 0; }
EOF;
    
    [$top, $footer] = $S->getPageTopBottom();

    // Here we set the session variable with $info form getheader()
    // This is how we pass the info to 'sendit'
    
    // Display the preview info and give the option to 'sendit'

    $postdata = preg_replace(["~\n~", '~"~'], ['', "`"], $info[1]);

    echo <<<EOF
$top
<hr>
<p>From: {$info['from']}<br>
Subject: {$info['subject']}<br>
CC: {$info['cc']}</p>
<p id="msg">Message:</p>{$info['contents']}
<form method="POST" action="sparkpost2.php">
<input type="hidden" name="email" value="$email">
<input type="hidden" name="post" value="$postdata">
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
<br><a href="sendemails2.php?page=auth&email=$email">Return to Send Mail</a>
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
input[type="radio"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
#send { padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
/*#send span { border-radius: 7px; border: 2px solid black; padding: 3px;}*/
#texttosend { width: 1000px; font-size: 30px; }
EOF;

$S->b_inlineScript = <<<EOF
$("#toindividuals").hide();

$("#marathon").on("click", function() {
  if(this.flag) {
    $("input", this).prop("checked", false);
    $("#marathon").show();
    $("#individual").show();
  } else {
    $("#marathon").show();
    $("#individual").hide();
  }
  this.flag = !this.flag;
});

$("#individual").on("click", function() {
  if(this.flag) {
    $("input", this).prop("checked", false);
    $("#marathon").show();
    $("#individual").show();
    $("#toindividuals").hide();
  } else {
    $("#marathon").hide();
    $("#toindividuals").show();
  }
  this.flag = !this.flag;
});

$("#past").on("click", function() {
  $.ajax({
    url: "https://bonnieburch.com/marathon/sendemails2.php",
    data: { "past": true, "email": "bonnieburch2015@gmail.com" }, // BLP 2023-10-07 - email needed for check auth above.
    type: 'post',
    success: function(data) {
      $("textarea").html(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
});
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
$errorMsg
<button id="past" value="TRUE">Get Last 'Text to send"</button>

<form enctype="multipart/form-data" method="POST">
<table>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject"></td></tr>
<tr><td>Enter Text to send</td><td><textarea id="texttosend" name="texttosend" rows="5" placeholder="Enter Text"></textarea></td></tr>
<tr id="marathon"><td>Send to Marathon group</td><td><input type="radio" name="radio" value="marathon"></td></tr>
<tr id="individual"><td>Send to individuals.</td><td><input type="radio" name="radio" value="individual"></td></tr>
<tr id="toindividuals"><td>Enter CC: </td><td><input type="text" name="sendto" value="$sendto" data-form-type="other"></td></tr>
</table>
<br>
<input type="hidden" name="email" value="$email">
<button id="send" type="submit" name="sendpreview" value="true">Preview, then <span>Send It</span></button>
</form>
<hr>
$footer
EOF;
