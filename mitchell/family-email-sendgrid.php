<?php
// REMEMBER: The From address must be setup in sendgrid.com. Goto settings and then Sender
// Authorize. Follow instructions. NOTE: do not use the full url only the part before the domain
// name.

// Email to all of Bonnie's family
// First we need a database:
// create table family (
//  fname varchar(256) not null,
//  lname varchar(256) not null,
//  phone varchar(20) default null,
//  email varchar(256) default null,
//  address varchar(256) default null,
//  created datetime not null,
//  lasttime datetime not null,
//  primary key(fname,lname);

// BLP 2024-11-11 - I have to fixup apostrophies and double quotes. See this date.
//  Also removed the from file logic and the femail which was in Marathon but not here.
// BLP 2024-05-14 - Now uses sendgrid: https://app.sendgrid.com/
// You must use composer to load the sendgrid PHP files: 'composer require sendgrid/sendgrid'

use SendGrid\Mail\Mail;

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new SiteClass($_site);

$S->css =<<<EOF
form table input { width: 1000px; font-size: 30px; }
td:first-of-type { padding-right: 20px; }
input[type="checkbox"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
#past, form button { padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
form textarea { font-size: var(--blpFontSize); width: 800px; height: 400px; }
/* For senditpreview */
#wait { position: relative; }
#wait img {
  position: absolute;
  top: -18px;
}
EOF;

// Upload file errors:

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
  global $S, $fileUploadErrors; // BLP 2024-11-11 - added $fileUploadErrors

  // info has
  // email
  // subject
  // texttosend
  // femail[] checkbox 
  // attachment
  // sendpreview button

  // Small function to check if the text has markup.
  // It returns the contents after fixing the txt.
  
  function checktext(string $txt):string {
    if(($r = preg_match("~<.*>~m", $txt)) === 0) {
      // BLP 2024-11-11 - replace apostrophies
      
      $x = str_replace("'", "&apos;", $txt);

      // BLP 2024-11-11 - remove \r before the end and \n after the end.
      
      $contents = preg_replace("~^(.*?)\r$\n~m", "$1<br>", $x);
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
    $contents = preg_replace('~"~m', "&quot;", $contents);

    file_put_contents("./data/lasttext.data", $txt); // Save original text

    return $contents;
  }

  $errorMsg = '';
  $from =  "MitchellFamily@bonnieburch.com";
    
  $to = "barton@bartonphillips.com";
    
  $subject = $info['subject'];
  $sendfile = $info['sendfile'];
  $texttosend = $info['texttosend'];
  $femail = $info['femail'];
  
  $date = date("Y-m-d");
  $msg = '';
  
  if(!empty($texttosend)) {
    $contents = checktext($texttosend);
  } else {
    $errorMsg .= "<h2>You must supply a message.</h2>";
  }
  
  if(empty($subject)) {
    $errorMsg .= "<h2>No valid 'Subject'</h2>";
  }

  if($femail) {
    foreach($femail as $e) {
      if(empty($e)) continue; // If no email address skip
      $cc[] = $e;
    }
  } else {
    $errorMsg .= "<h2>You must provide a CC list</h2>";
  }

  if($attachment = $_FILES['attachment']['name']) {
    if($err = $_FILES['attachment']['error']) {
      $errorMsg .= $fileUploadErrors[$err]; // BLP 2024-11-11 - uses global
    } else {
      $name = basename($attachment);
      $data = base64_encode(file_get_contents($_FILES['attachment']['tmp_name']));
      $type = $_FILES['attachment']['type'];
    }
    $attachments = [$data,$type,$name,'attachment'];
  }

  if(!empty($errorMsg)) {
    return ['ERROR', $errorMsg];
  } else {
    return ['HEADERS', 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc,
            'contents'=>$contents, 'attachments'=>$attachments];
  }
}

//******************
// Check Authorized

$xemail = $_REQUEST['email'];

if(empty($xemail) || !$S->sql("select fname, lname from bonnie.family where email='$xemail'")) {
  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

// Get the past Text

if($_POST['past']) {
  $text = file_get_contents("data/lasttext.data");

  echo $text;
  exit();
}

//***********************
// The main pages does a post to 'sendpreview' which if everything looks OK does a POST to 'sendit'

if($_POST['sendit']) {
  $email = $_POST['email'];

  $info = json_decode($_POST['post'], true);

  $email = new Mail();

  $email->setFrom($info['from']);
  $email->setSubject($info['subject']);
  $email->addTo($info['to']);

  $email->setReplyTo("bonnieburch2015@gmail.com"); // BLP 2024-11-16 - Add replyto.
  
  foreach($info['cc'] as $cc) {
    $email->addCc($cc);
  }
  $email->addContent("text/plain", preg_replace("~<br>~", "\n", $info['contents'])); //"View this in HTML mode");

  // BLP 2024-11-11 - replace the quote that was removed in sendpreview()

  $info['contents'] = str_replace('~', '&quot;', $info['contents']);

  $email->addContent("text/html", $info['contents']);

  if($info['attachments']) {
    $email->addAttachment($info['attachments']);
  }

  $apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
  
  $sendgrid = new \SendGrid($apiKey);

  $response = $sendgrid->send($email);
  if($response->statusCode() > 299) {
    $code = $response->statusCode();
    //print_r($response->headers()); // ONLY for debugging
    //print $response->body() . "\n"; // ONLY for debugging
    $body = json_decode($response->body());
    $errorMsg = "Error Code: $code<br>SendGrid: {$body->errors[0]->message}<br>";
    
    $S->title = "Send Error";
    $S->banner = "<h1>$S->title</h1>";
    $msg = "<p>$errorMsg</p>";
  } else {
    $S->title = "Data Sent";
    $S->banner = "<h1>$S->title</h1>";
    $msg =<<<EOF
<h2>You information has been sent.</h2>
EOF;
  }

  header("Location: https://bonnieburch.com/mitchell/family-email-sendgrid.php?page=end&msg=$msg&email=$xemail");
  exit();
}

// This is the FINAL message after 'sendit' above. The insures that we can not resend the message
// by pressing F5 etc.

if($_GET['page'] == 'end') {
  $msg = $_GET['msg'];
  
  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
$msg
<a href="family.php?page=auth&email=$xemail">Return to Bonnie's Home Page</a>
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

  $xemail = $_POST['email'];
  
  $info = getheader($_POST);

  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
    goto PREVIEW_END;
    
  } elseif($info[0] == "HEADERS") {
    $S->title = "Preview";
    $S->banner = "<h1>$S->title</h1>";

    $S->b_inlineScript = <<<EOF
$("#sendit").on("click", function(e) {
  $(this).hide();
  $("#wait").html("<h2>Please Wait&nbsp;&nbsp;<img src='https://bartonphillips.net/images/loading.gif' width=100 height=100'></h2>");
});
EOF;

    [$top, $footer] = $S->getPageTopBottom();

    // BLP 2024-11-11 - Can't have a quote in json

    $contents = $info['contents'];
    
    $info['contents'] = str_replace('&quot;', '~', $contents);
    
    $postdata = json_encode($info);

    if($attach = $info['attachments']) {
      $attachStr = "<br>attachment: $attach[2]";
    }

    $ccstr = implode(",", $info['cc']);

    // BLP 2024-11-11 - use $contents for the Message and $postdata with the ~ fix for the hidden
    // post.

    echo <<<EOF
$top
<hr>
<p>From: {$info['from']}<br>
Subject: {$info['subject']}<br>
Send To: $ccstr</p>
<p>Message:<br>$contents
$attachStr</p>
<form method="POST">
<input type="hidden" name="email" value="$xemail">
<input type="hidden" name="post" value='$postdata'>
<button id="sendit" type="submit" name="sendit" value="sendit">Send It</button>
<div id="wait"></div>      
</form>
<br><a href="family-email-sendgrid.php?page=auth&email=$xemail">Return to Send Mail</a>
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

$S->title = "Family Emails";  
$S->banner = "<h1>$S->title</h1>";

$S->b_inlineScript =<<<EOF
$("#all").on("click", function() {
  if($("#all").prop('checked')) {
    $("input[type='checkbox']").prop('checked', true);
  } else {
    $("input[type='checkbox']").prop('checked', false);
  }
});

$("#past").on("click", function() {
  $.ajax({
    url: "https://bonnieburch.com/mitchell/family-email-sendgrid.php",
    data: { "past": true, "email": "bonnieburch2015@gmail.com" }, // BLP 2023-10-07 - email needed for check auth above.
    type: 'post',
    success: function(data) {
      console.log("data: ", data);
      $("#textarea").html(data);
    },
    error: function(err) {
      console.log(err);
    }
  });
});
EOF;

[$top, $footer] = $S->getPageTopBottom();

$familyTbl =<<<EOF
<table id="family" border="1">
<thead>
<tr><th>Select</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><tr>
</thead>
<tbody>
EOF;

$S->sql("select fname, lname, phone, email, address from bonnie.family order by lname");
while([$fname, $lname, $phone, $fileEmail, $address] = $S->fetchrow('num')) {
  $familyTbl .= "<tr><td><input type='checkbox' name='femail[]' value='$fileEmail'></td>".
                "<td>$fname $lname</td><td>$phone</td><td>$fileEmail</td><td>$address</td></tr>";
}

$familyTbl .= <<<EOF
</tbody>
</table>
EOF;

echo <<<EOF
$top
<hr>
$errorMsg
<button id="past" value="TRUE">Get Last 'Text to send'</button>

<form enctype="multipart/form-data" method="POST">
<table>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject"></td></tr>
<!--<tr><td>Enter Send to Filename</td><td><input type="file" name="filename"></td></tr>-->
<tr><td>Enter Text to send</td><td><textarea id="textarea" name="texttosend" rows="5" cols="50" placeholder="Enter Text"></textarea></td></tr>
</table>
<p>Select to whom you want to send:</p>
<input id="all" type="checkbox">Select All
$familyTbl
<table>
<tr><td>Attachment File</td><td><input type="file" name="attachment"></td></tr>
</table>
<br>
<input type="hidden" name="email" value="$xemail">
<button id="send" type="submit" name="sendpreview" value="true">Preview, then Send Email</button>
</form>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
