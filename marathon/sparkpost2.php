<?php

if($_POST['sendit']) {
  $_site = require_once(getenv("SITELOADNAME"));
  $S = new SiteClass($_site);
  $post = preg_replace("~`~", '"', $_POST['post']);
  vardump("POST", $_POST);
    
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
  echo curl_setopt_array($ch, $options);

  $result = curl_exec($ch);
  if($result !== false) {
    echo "<br>RESULT: $result";
  } else {
    echo "<br>Failed";
  }
  exit();
};

// Not a post

echo "Go Away<br>";
