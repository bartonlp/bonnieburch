<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

$lines = file("contacts1.csv");
//vardump("lines", $lines);

foreach($lines as $line) {
  $items = explode(",", $line);
  $fname = $items[0];
  $lname = $items[1];
  $email = $items[2];
  $phone = $items[3];
  
  $S->sql("insert into bonnie.family (fname, lname, email, phone, created, lasttime) ".
            "values('$fname', '$lname', '$email', '$phone', now(), now())");
}
