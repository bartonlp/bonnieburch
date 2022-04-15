<?php
// This file is used to set up bonnieburch.com with the correct fingerprint.
// NOTE *** There are only two places where the myip table is inserted or updated to, that is here
// and in bartonphillips.com/register.php.

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

if($_POST['page'] == 'finger') {
  // BLP 2022-01-12 -- /tmp is not really /tmp. Look at the apache2.service file. Notice the
  // 'PrivateTmp=true' item. This tells apache to use a seperate /tmp.
  // The actual location is:
  // /tmp/systemd-private-a27bd3d0445a474e80ded3917a0f1bb9-apache2.service-mvTPqh/tmp/
  // This is NOT the case with the command line version of PHP which really does write to /tmp!
  
  $visitor = $_POST['visitor'];
  if(file_put_contents("/tmp/visitorfingertemp", $visitor) === false) {
    error_log("can't write");
  }
  echo "OK";
  exit();
}
      
// If a post from the form

if($_POST) {
  $S = new Database($_site);
  
  $name = $S->escape($_POST['name']);
  $email = $S->escape($_POST['email']);

  if($email == "bonnieburch2015@gmail.com") {
    $name = "Bonnie Burch"; // Force name

    $S->query("select count(*) from information_schema.tables ".
              "where (table_schema = '$S->masterdb') and (table_name = 'myip')");

    if(!$S->fetchrow('num')[0]) {
      throw new Exception(__LINE__ .": register.php, myip table does not exist");
    }
    
    // Update the myip tables.
    $sql = "insert into $S->masterdb.myip (myIp, createtime, lasttime) values('$S->ip', now(), now()) " .
           "on duplicate key update lasttime=now()";

    $S->query($sql);
  }

  // Always set the cookie. We use the sql id from the members table.
  // BLP 2021-09-21 -- Add email with ip.

  $options =  array(
                    'expires' => date('U') + 31536000,
                    'path' => '/',
                    'domain' => "." . $S->siteDomain, // leading dot for compatibility or use subdomain
                    'secure' => true,      // or false
                    'httponly' => false,    // or true. If true javascript can't be used.
                    'samesite' => 'Lax'    // None || Lax  || Strict // BLP 2021-12-20 -- changed to Lax
                   );

  if(setcookie('SiteId', "$S->ip:$email", $options) === false) {
    echo "Can't set cookie SiteId in addcookie.php<br>";
    throw(new Exception("Can't set cookie SiteId in addcookie.php " . __LINE__));
  }

  $visitorId = file_get_contents("/tmp/visitorfingertemp");
  unlink("/tmp/visitorfingertemp");
  //error_log("visitorId: $visitorId");
  
  if(setcookie('BLP-Finger', $visitorId, $options) === false) {
    echo "Can't set cookie BLP-Finger in addcookie.php<br>";
    throw(new Exception("Can't set cookie BLP-Finger in addcookie.php " . __LINE__));
  }

  header("Location: /");
  exit();
}

$S = new $_site->className($_site);

$h->title = "Add Cookie";
$h->css = <<<EOF
  <style>
input {
  font-size: 1rem;
  padding-left: .5rem;
}
input[type="submit"] {
  border-radius: .5rem;
  background-color: green;
}
  </style>
EOF;

// BLP 2022-01-16 -- noGeo is a new flag that has been added to footer.i.php

$b->noGeo = true; // Don't add geo.js
$b->script = "<script src='https://bartonphillips.net/js/getFingerprint.js'></script>";

list($top, $footer) = $S->getPageTopBottom($h, $b);

// Render Page

echo <<<EOF
$top
<h1>Add Cookie</h1>
<form method="post">
<table>
<tbody>
<tr>
<td><span class="lynx">Enter Name </span><input type="text" name="name" placeholder="Enter Name"></td>
</tr>
<tr>
<td><span class="lynx">Enter Email Address </span><input type="text" name="email" autofocus required placeholder="Enter Email Address"></td>
</tr>
</tbody>
</table>
<input type="submit" value="Submit">
</form>
$footer
EOF;
