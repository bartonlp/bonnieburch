<?php

if($_POST['sendit']) {
  $_site = require_once(getenv("SITELOADNAME"));
  $S = new SiteClass($_site);
  $post = preg_replace("~`~", '"', $_POST['post']);
  //vardump("POST", $_POST);
    
  $apikey = getenv("SPARKPOST_API_KEY");

  $options = [
              CURLOPT_URL=>"https://api.sparkpost.com/api/v1/transmissions", //?num_rcpt_errors",
              CURLOPT_HEADER=>0,
              CURLOPT_HTTPHEADER=>[
                                   "Authorization:$apikey",
                                   "Content-Type:application/json"
                                  ],
              CURLOPT_POST=>true,
              CURLOPT_RETURNTRANSFER=>true,
              CURLOPT_POSTFIELDS=>$post
                                 ];

  //vardump("options", $options);
  
  $ch = curl_init();
  curl_setopt_array($ch, $options);

  $result = curl_exec($ch);
  if($result === false) {
    $errorMsg = "Error 'imap_mail'<br>" . imap_errors();
    $S->title = "Send Error";
    $S->banner = "<h1>$S->title</h1>";
    $msg = "<p>$errorMsg</p>";
  } else {
    $S->title = "Data Sent";
    $S->banner = "<h1>$S->title</h1>";
    $msg =<<<EOF
<h2>Your information has been sent.</h2>
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
//  unlink("data/{$info['attachment']}"); // Unlink a file if it is in 'data/'. If nothing there we don't care.
  exit();
};

// Not a post

echo "Go Away<br>";
