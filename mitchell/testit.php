<?php
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new SiteClass($_site);

// Sendpreview

if($_POST['sendpreview']) {
  $S->title = "Preview";
  $S->banner = "<h1>$S->title</h1>";

  $S->b_inlineScript = <<<EOF
history.replaceState(null, '', './family.php');
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
  $contents = 'contents';

  [$top, $footer] = $S->getPageTopBottom();
    
  echo <<<EOF
$top
<hr>
<p>From: Here<br>
Subject: There<br>
$contents
<form method="post">
<input type="hidden" name="email" value="$xemail">
<button type="submit" name="sendit" value="sendit">Send It</button>
</form>
<br><a href="testit.php?page=auth&email=$xemail">Return to <b>blank</b> Send Mail</a>
<div id="backarrow"><button>Return <b>filled in</b> Send Mail</button></div>
<hr>
$footer
EOF;

  exit();
}

// Sendit. Via <form> sendpreviews.

if(isset($_POST['sendit'])) {
  $S->title = "Data Sent";
  $S->banner = "<h1>$S->title</h1>";

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<h2>You information has been sent.</h2>
<form method="post">
<input type='hidden' name='email' value='$xemail'>
<button type='submit' name="page" value="startpage">Return to Bonnie's Home Page</button>
</form>
<hr>
$footer
EOF;

  exit;
}

//**************
// Start of Page

$S->title = "Family Emails";  
$S->banner = "<h1>$S->title</h1>";

[$top, $footer] = $S->getPageTopBottom();

// Main flow.
// We already have $xemail from 'Check Authorized'

echo <<<EOF
$top
<hr>
<form method="POST">
<table>
<tr><td>Enter Subject</td>
<td><input id="input" type="text" name="subject" value="Subject test"></td></tr>
<tr><td>Enter Text to send</td>
<td><textarea id="textarea" name="texttosend" rows="5" cols="50" placeholder="Enter Text">This is a test</textarea></td></tr>
</table>
<p>Select to whom you want to send:</p>
<input type="hidden" name="email" value="$xemail">
<input type="hidden" name="name" value="bartonphillips.com">
<button id="sendit" type="submit" name="sendpreview" value="true">Preview, then Send Email</button>
</form>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;



//<form enctype="multipart/form-data" method="POST">