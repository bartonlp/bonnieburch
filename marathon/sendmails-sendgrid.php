<?php
// BLP 2024-05-14 - This is the sendgrid version
// REMEMBER: The From address must be setup in sendgrid.com. Goto settings and then Sender
// Authorize. Follow instructions. NOTE: do not use the full url only the part before the domain
// name.

use SendGrid\Mail\Mail;

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

//****************
// getheader()
// Parses the $info and returns an array of information

function getheader($info) {
  global $S;
  //vardump("info", $info);
  
  // info has
  // email
  // subject
  // filename
  // texttosend
  // radio (radio button)
  // attachment
  // sendpreview button

  // Small function to check if the text has markup.
  // It returns the contents after fixing the txt.
  
  function checktext(string $txt):string {
    if(($r = preg_match("~<.*>~m", $txt)) === 0) {
      $contents = preg_replace("~^(.*?)$~m", "$1<br>", $txt);
    } elseif($r === 1) {
      $ar = explode("\n", $txt);
      foreach($ar as $a) {
        if(preg_match("~<.*>m~", $a) === 0) {
          $contents .= "$a<br>";
        } else {
          $contents .= $a;
        }
      }
    } else {
      echo "ERROR<br>";
      exit();
    }

    $contents = preg_replace("~\"~m", "&quot;", $contents);
    file_put_contents("./data/lasttext.data", $txt);

    return $contents;
  }

  $errorMsg = '';
  $from = "Marathon@bonnieburch.com"; //"Marathon@mail.bonnieburch.com";
    
  $to = "barton@bartonphillips.com";

  // Make the table of scores
  
  $S->sql("select distinct s.fkteam, t.name1, t.name2 from scores as s left join teams as t on s.fkteam = t.team order by fkteam");
  $r = $S->getResult();

  while([$team, $name1, $name2] = $S->fetchrow($r, 'num')) {
    $list .= "<tr><td style=\"text-align: right; padding: 3px;\">$team</td><td style=\"text-align: left; padding: 3px\">$name1 &amp; $name2</td>";

    $S->sql("select score from scores where fkteam=$team order by moNo");
    $total = 0;

    // BLP 2022-08-26 - NOTE: if I do $score = $S->fetchrow('num')[0], I could get a zero back which looks like a
    // null and  would stop the while loop! So while I could do this ($score =
    // $S->fetchrow('num')[0]) !== null), it is probably safer to always use an array as the receiver
    // in a while loop. I think I have fixed all of my code.

    while([$score] = $S->fetchrow('num')) {
      $total += $score;
      $list .= "<td style=\"text-align: right; padding: 3px\">$score</td>";
    }
    $list .= "<td style=\"background: lightpink; text-align: right; padding: 3px\">$total</td></tr>";
  }

  $contents = checktext($info['texttosend']);

  // The table fully formed

  $contents = "$contents<table id=\"results\" border=\"1\"><tbody>$list</tbody></table>";

  $subject = $info['subject'];
  $sendfile = $info['sendfile'];
  $texttosend = $info['texttosend'];
  $sendTo = $info['sendto'];
  $radio = $info['radio'];
  $date = date("Y-m-d");
  $msg = '';
  
  if(empty($subject)) {
    $errorMsg .= "<h2>No valid 'Subject'</h2>";
  }

  if($radio == "marathon") {
    if($S->sql("select email1, email2 from teams")) {
      while([$email1, $email2] = $S->fetchrow('num')) {
        if(!empty($email1)) {
          $cc[] = $email1;
        }
        if(!empty($email2)) {
          $cc[] = $email2;
        }
      }
    }

  } elseif($sendTo) {
    $sendTo = explode(",", $sendTo);
    
    foreach($sendTo as $e) {
      if(empty($e)) continue; // If no email address skip
      $cc[] = $e;
    }
  } else {
    $errorMsg .= "<h2>You must provide a CC list</h2>";
  }

  if(!empty($errorMsg)) {
    return ['ERROR', $errorMsg];
  } else {
    return ['HEADERS', 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc,
            'contents'=>$contents]; //, 'attachments'=>$attachments];
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

//***********************
// The main pages does a post to 'sendpreview' which if everything looks OK does a POST to 'sendit'

if($_POST['sendit']) {
  $xemail = $_POST['email'];
  $info = json_decode($_POST['post'], true);

  $email = new Mail();

  $email->setFrom($info['from']);
  $email->setSubject($info['subject']);
  $email->addTo($info['to']);
  
  foreach($info['cc'] as $cc) {
    $email->addBcc($cc);
  }
  $email->addContent("text/plain", 'View this in HTML mode');
  $email->addContent("text/html", $info['contents']);


  //$email->addAttachment($info['attachments']);

  $apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
  
  $sendgrid = new \SendGrid($apiKey));

  $response = $sendgrid->send($email);

  if($response->statusCode() > 299) {
    print $response->statusCode() . "<br><pre>";
    print_r($response->headers());
    print "</pre>Body: <pre>";
    print_r(json_decode($response->body()));
    print "</pre>";
    exit();
  }  

  $S->title = "Data Sent";
  $S->banner = "<h1>$S->title</h1>";
  $msg =<<<EOF
<h2>You information has been sent.</h2>
<a href="marathon.php?page=auth&email=$xemail">Return to Bonnie's Home Page</a>
EOF;

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
$msg
<hr>
$footer
EOF;
  exit();
};

//*****************************
// The main program does a post to here. If all is OK this does a post to 'sendit'

if($_POST['sendpreview']) {
  // $_POST has:
  // email
  // subject
  // filename
  // texttosend
  // femail[] checkbox
  // attachment
  // sendpreview button
  //vardump("post", $_POST);
  
  $xemail = $_POST['email'];
  
  $info = getheader($_POST);

  // $info[0] has either 'ERROR' or "HEADERS"
  
  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
    goto PREVIEW_END;
    
  } elseif($info[0] == "HEADERS") {
    $S->title = "Preview";
    $S->banner = "<h1>$S->title</h1>";
    
    [$top, $footer] = $S->getPageTopBottom();

    $info['contents'] = preg_replace("~'~", "&apos;", $info['contents']);
    $info['subject'] = preg_replace("~'~", "&apos;", $info['subject']);

    $postdata = json_encode($info);

    $ccstr = implode(",", $info['cc']);

    echo <<<EOF
$top
<hr>
<p>From: {$info['from']}<br>
Subject: {$info['subject']}<br>
SENDTO: $ccstr</p>
<p>Message:<br>{$info['contents']}</p>
<form method="POST">
<input type="hidden" name="email" value="$xemail">
<input type="hidden" name="post" value='$postdata'>
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
<br><a href="sendmails-sendgrid.php?page=auth&email=$xemail">Return to Send Mail</a>
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
    $("#toindividuals input").focus();
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
