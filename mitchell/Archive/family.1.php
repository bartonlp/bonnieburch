<?php
//

$_site = require_once(getenv("SITELOADNAME")); // Get Startup info from mysitemap.json
$S = new SiteClass($_site);

// If $_POST or $_GET check authorization.

if($_REQUEST['page'] == 'auth') {
  $email = $_REQUEST['email'];
  
  if(empty($email)) {
    youareok(false, 'NO_EMAIL');
    exit();
  }

  // If the email address in in the team table you are OK.
  
  if($S->sql("select fname, lname from bonnie.family where email='$email'")) {
    [$fname, $lname] = $S->fetchrow('num');

    youareok(true, $email, $fname, $lname);
  } else {
    // If you are not then 
    youareok(false, $email);
  }
  exit();
}

// Checks to see if $ok is true or false and then either logs an
// error in the badplayer table or outputs the OK page.

function youareok(bool $ok, string $email, ?string $fname1=null, ?string $lname2=null): never {
  global $S;

  $S->title = "The Mitchell Family";
  $S->banner = "<h1>$S->title</h1>";
  $S->css = ".center {text-align: center;} .red { color: red; }";
  
  [$top, $footer] = $S->getPageTopBottom();

  if(!$ok) {
    //You are NOT OK
                
    echo <<<EOF
$top
<hr>
<h1>Sorry you are not authorized</h1>
<a href="../index.php">Return to main page</a>
<hr>
$footer
EOF;
    $S->sql("insert into $S->masterdb.badplayer (ip, site, page, botAs, type, errno, errmsg, agent, created, lasttime) " .
    "values('$S->ip', '$S->siteName', '$S->self', 'counted', 'MITCHELL', -1, 'Not Authorized', '$S->agent', now(), now())");

    exit();
  }

  // You Are OK!
  if($email == 'bonnieburch2015@gmail.com') { // Is it me?
    $greeting = "<h1 class='center red'>Hello Bonnie. You are the Administrator</h1>";
    $onlyAdmin = "<a href='add-edit.php?page=auth&email=$email'>Add/Edit/Delete Members</a><br>";
  }

  // Render the OK page. It it is me add the $onlyBlp links
  
  echo <<<EOF
$top
<hr>
$greeting
$onlyAdmin
<a href="family-email-sendgrid.php?page=auth&email=$email">Send Family Emails</a><br>
<a href="eightmm.php?page=auth&email=$email">View 8mm Home Movie</a>
<br><br><a href="../index.php">Return to main page</a>
<hr>
$footer
EOF;
  exit();
}

// Start Page

$S->title = "The Michell Family";
$S->css =<<<EOF
.center { text-align: center; }
button {
  border-radius: 5px;
  background: green;
  color: white;
}
input { width: 350px; }
input, button { font-size: 20px; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

// Call auth

echo <<<EOF
$top
<hr>
<form method='post'>
Login with your email address: <input type='text' name='email'><br>
<button type='submit' name='page' value='auth'>Continue</button>
</form>
<hr>
$footer
EOF;
