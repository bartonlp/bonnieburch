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

// BLP 2024-12-24 - Reworked logic to use urlencode/urldecode.

// You must use composer to load the sendgrid PHP files: 'composer require sendgrid/sendgrid'

use SendGrid\Mail\Mail; // Now that you have used composer to get sendgrid you can use it.

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new SiteClass($_site);

$S->css =<<<EOF
form table input { width: 1000px; font-size: 30px; }
td:first-of-type { padding-right: 20px; }
input[type="checkbox"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
#past, form button { cursor: pointer; padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
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

  /*
    info has
    email
    subject
    texttosend
    femail[] checkbox 
    attachment
    sendpreview button
  */

  // Small function to check if the text has markup.
  // It returns the urlencoded contents after fixing the txt.

  function checktext(string $txt):string {
    if(($r = preg_match("~<.*>~m", $txt)) === 0) {
      $contents = preg_replace("~^(.*?)$~m", "$1<br>", $txt);
    } elseif($r === 1) {
      $ar = explode("\n", $txt);
      foreach($ar as $a) {
        if(preg_match("~<.*>~", $a) === 0) {
          $contents .= "$a<br>";
        } elseif(preg_match("~<br>$~", $a) === 0) {
          $contents .= "$a<br>";
        } else {
          $contents .= $a;
        }
      }
    }
  
    $y = file_put_contents("./data/lasttext.data", $txt); // Save original text
    if($y === false) {
      echo "Error writing to file<br>";
      $err = error_get_last();
      echo $err['message'];
      exit();
    }

    return urlencode($contents);
  }
  // End function
  
  $errorMsg = '';
  $from =  "MitchellFamily@bonnieburch.com";
  $to = "barton@bartonphillips.com";
  $subject = $info['subject'];
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

  if(!empty($errorMsg)) {
    return ['ERROR', $errorMsg];
  } else {
    return ['HEADERS', 'from'=>$from, 'to'=>$to, 'subject'=>$subject, 'cc'=>$cc,
            'contents'=>$contents];
  }
}

//******************
// Check Authorized

$xemail = $_REQUEST['email']; // For either a POST or a GET. Get the xemail 


if(empty($xemail) || !$S->sql("select fname, lname from bonnie.family where email='$xemail'")) {
  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

// collect_attachments

function collect_attachments(string $field='attachment'): array {
  if(!isset($_FILES[$field])) return [];
  
  $f = $_FILES[$field];
  $list = [];

  // normalize single vs multiple
  $count = is_array($f['name']) ? count($f['name']) : 1;
  for($i=0; $i<$count; $i++) {
    $name = is_array($f['name']) ? $f['name'][$i] : $f['name'];
    $type = is_array($f['type']) ? ($f['type'][$i] ?? '') : ($f['type'] ?? '');
    $tmp  = is_array($f['tmp_name']) ? $f['tmp_name'][$i] : $f['tmp_name'];
    $err  = is_array($f['error']) ? $f['error'][$i] : $f['error'];

    if(!$name || $err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) continue;

    if(!$type){
      $fi = @finfo_open(FILEINFO_MIME_TYPE);
      $type = $fi ? (finfo_file($fi, $tmp) ?: 'application/octet-stream') : 'application/octet-stream';
      if($fi) finfo_close($fi);
    }

    $raw = file_get_contents($tmp);
    if($raw === false) continue;

    $list[] = [base64_encode($raw), $type, basename($name), 'attachment'];
  }
  return $list;
}

// Sendpreview

if($_POST['sendpreview']) {
  // $_POST has:
  // email
  // subject
  // filename
  // texttosend
  // femail[] checkbox
  // sendpreview button

  $info = getheader($_POST);

  if($info[0] == 'ERROR') {
    $errorMsg = $info[1];
  } elseif($info[0] == "HEADERS") {
    $S->title = "Preview";
    $S->banner = "<h1>$S->title</h1>";

    $S->b_inlineScript = <<<EOF
$("#sendit").on("click", function(e) {
  $(this).hide();
  $("#wait").html("<h2>Please Wait&nbsp;&nbsp;<img src='https://bartonphillips.net/images/loading.gif' width=100 height=100'></h2>");
});
$("#backarrow").on("click", function(e) {
  history.back();
});
EOF;

    $S->css .= <<<EOF
#backarrow button {
  cursor: pointer;
  padding: 5px 15px;
  font-size: 30px;
  background-color: lightpink;
  color: black;
  border-radius: 10px;
}
EOF;
    
    [$top, $footer] = $S->getPageTopBottom();

    $contents = urldecode($info['contents']);
    
    $postdata = json_encode($info);

    $attachStr = '';

    try {
      $attachments = collect_attachments('attachment');
      foreach($attachments as [$data, $type, $name, $disp]) {
        $attachStr .= "<br>attachment: $name"; // This is $name.
      }
    } catch(Throwable $e){
      echo "<p>Attachment (1) error: ".htmlspecialchars($e->getMessage()).".</p>";
      exit;
    }

    // Move $attachments to $new.
    
    $new = json_encode($attachments);

    $ccstr = implode(",", $info['cc']);

    // Wright the message.
    
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
<input type='hidden' name='attachments' value='$new'>
<button id="sendit" type="submit" name="sendit" value="sendit">Send It</button>
<div id="wait"></div>      
</form>
<br><a href="family-email-sendgrid.php?page=auth&email=$xemail">Return to Send Mail</a>
<div id="backarrow"><button>Return to Send Mail</button></div>
<hr>
$footer
EOF;
    exit();
  }
  // If we have if(info[0] == 'ERROR') we come to $msg and return to the Main flow.
}

// Sendit. Via <form> sendpreviews.
// This loads sendpreviews
// json_decode($_POST['attachments']
// $info = json_decode($_POST['post'], true));

if(isset($_POST['sendit'])) {
  $attachments = json_decode($_POST['attachments']);
  $info = json_decode($_POST['post'], true);

  $email = new \SendGrid\Mail\Mail();
  $email->setFrom($info['from']);
  $email->setSubject($info['subject']);
  $email->addTo($info['to']);
  $email->setReplyTo("bonnieburch2015@gmail.com");

  foreach($info['cc'] as $cc) {
    $email->addCc($cc);
  }

  $email->addContent("text/plain", urldecode($info['contents'])); //"View this in HTML mode");
  $email->addContent("text/html", urldecode($info['contents']));

  try {
    foreach($attachments as [$data, $type, $name, $disp]) {
      $email->addAttachment($data, $type, $name, $disp);
    }
  } catch(Throwable $e){
    echo "<p>Attachment (2) error: ".htmlspecialchars($e->getMessage()).".</p>";
    exit;
  }

  $apiKey = require "/var/www/PASSWORDS/sendgrid-api-key";
  $sendgrid = new \SendGrid($apiKey);

  try {
    $response = $sendgrid->send($email);
  } catch(Exception $e) {
    $code = $e->getCode();
    $message = $e->getMessage();
    echo "<h1>Error</h1><p>Code: $code<br>SendGrid: $message.</p>";
    vardump("e", $e);
    exit;
  }
  
  if($response->statusCode() > 299) {
    $code = $response->statusCode();
    $body = json_decode($response->body());
    $sgMsg = $body->errors[0]->message ?? 'Unknown SendGrid error';
    echo "<p>Error Code: $code<br>SendGrid: ".htmlspecialchars($sgMsg).".</p>";
    exit;
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

//***********************

// Get Last 'Text to send'

if($_POST['past']) {
  $text = file_get_contents("data/lasttext.data");

  echo $text;
  exit();
}

// This is the FINAL message after 'sendit' above. This insures that we can not resend the message
// by pressing F5 etc. You can use the 'back arrow' to go back to the 'Preview' page from which you
// can choose to return to the main page or resend the information.
// We already have #xemail from 'Check Authoried'

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

// Main flow.
// We already have $xemail from 'Check Authorized'

echo <<<EOF
$top
<hr>
$errorMsg
<button id="past" value="TRUE">Get Last 'Text to send'</button>

<form enctype="multipart/form-data" method="POST">
<table>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject"></td></tr>
<tr><td>Enter Text to send</td><td><textarea id="textarea" name="texttosend" rows="5" cols="50" placeholder="Enter Text"></textarea></td></tr>
</table>
<p>Select to whom you want to send:</p>
<input id="all" type="checkbox">Select All
$familyTbl
<table>
<tr><td>Attachment File</td><td><input type="file" name="attachment[]" multiple></td></tr>
</table>
<br>
<input type="hidden" name="email" value="$xemail">
<button id="send" type="submit" name="sendpreview" value="true">Preview, then Send Email</button>
</form>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
