<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

session_start();

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

// Now I can use the mail program from marathon.

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
  global $S;

  $errorMsg = '';
  $envelope["from"] = $from = "barton@bartonphillips.org";
  $to = "bartonphillips@gmail.com";

  $subject = $info['subject'];
  $sendto = $info['sendtosend'];
  $sendfile = $info['sendfile'];
  $textto = $info['texttosend'];
  $femail = $info['femail'];

  $date = date("Y-m-d");

  if($textto) {
    $contents = $textto;
  } else {
    if(empty($_FILES['filename']['name'])) {
      $errorMsg .= "<h2>You must supply a 'Send to Filename'</h2>";
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
        $uploaddir = '/var/www/bonnieburch.com/data/';
        $uploadfile = $uploaddir . basename($_FILES['attachment']['name']);

        if(!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadfile)) {
          echo "Move error<br>";
        }
      }
    }
  }
  
  if($femail) {
    $cc = '';

    foreach($femail as $e) {
      $cc .= "$e,";
    }
    $envelope["cc"] = rtrim($cc, ",");
  } else {
    $errorMsg .= "<h2>You must provide a CC list</h2>";
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

$xemail = $_REQUEST['email'];

if(empty($xemail) || !$S->query("select fname, lname from bonnie.family where email='$xemail'")) {
  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

//***********************
// FROM form POST 'sendit'
// The main pages does a post to 'sendpreview' which if everything looks OK does a POST to 'sendit'

if($_POST['sendit']) {
  $xemail = $_POST['email'];

  $info = $_SESSION['info'];
  $headers = $info[1];
  $to = $info['to'];
  $subject = $info['subject'];

  // Send the emails
  
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
<a href="family.php?page=auth&email=$xemail">Return to Bonnie's Home Page</a>
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
  exit();
}

//*****************************
// FROM form POST 'sendpreview'
// The main program does a post to here. If all is OK this does a post to 'sendit'

if($_POST['sendpreview']) {
  $xemail = $_POST['email'];
  
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
<input type="hidden" name="email" value="$xemail">
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
<br><a href="sendmail.php?page=auth&email=$xemail">Return to Send Mail</a>
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
$S->css =<<<EOF
form table input { width: 1000px; font-size: 30px; }
td:first-of-type { padding-right: 20px; }
input[type="checkbox"] { margin-left: 0px; vertical-align: bottom; width: 30px; height: 30px; }
form button { padding: 5px 15px; font-size: 30px; border-radius: 10px; background: green; color: white; }
EOF;

$S->b_inlineScript =<<<EOF
$("#all").on("click", function() {
  if($("#all").prop('checked')) {
    $("input[type='checkbox']").prop('checked', true);
  } else {
    $("input[type='checkbox']").prop('checked', false);
  }
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

$S->query("select fname, lname, phone, email, address from bonnie.family order by lname");
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
<form enctype="multipart/form-data" method="POST">
<table>
<tr><td>Enter Send to Filename</td><td><input type="file" name="filename"></td></tr>
<tr><td>Enter Text to send</td><td><textarea name="texttosend" rows="5" cols="50" placeholder="Enter Text"></textarea></td></tr>
</table>
<p>Select to whom you want to send:</p>
<input id="all" type="checkbox">Select All
$familyTbl
<table>
<tr><td>Enter Subject</td><td><input type="text" name="subject" value="$subject"></td></tr>
<tr><td>Attachment File</td><td><input type="file" name="attachment"></td></tr>
</table>
<br>
<input type="hidden" name="email" value="$xemail">
<button id="send" type="submit" name="sendpreview" value="true">Send Email</button>
</form>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
