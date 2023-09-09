<?php
// Help for Bridgeclub app.

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

// BLP 2023-08-12 - add check. This is a little bit different from the one in startup.php.

if($_GET['blp'] != '8653' && $_SERVER['REMOTE_ADDR'] != '195.252.232.86') { // BLP 2023-08-12 - added ip address
  // BLP 2023-01-16 - Changed logic to just use the $finger from
  // bartonphillipsnet/myfingerprints.php.
  // Check if user is Authorized. Look at the BLP-Finger cookie.

  //error_log("startup.i.php: COOKIES, site=$_site->siteName, " . print_r($_COOKIE, true));
  //vardump("COOKIES", $_COOKIE);
  
  $finger = $_COOKIE['BLP-Finger'];
  //echo "finger=$finger<br>";
  
  // Get the authorized fingerprints from bartonphillipsnet.
  
  $bonnieFingers = require("/var/www/bartonphillipsnet/myfingerprints.php");

  //error_log("startup.i.php $S->siteName: finger=$finger, bonnieFingers=" . print_r($bonnieFingers, true));
  
  if(array_key_exists($finger , $bonnieFingers) === false) {
    $S->query("insert into $S->masterdb.badplayer (ip, botAs, type, count, errmsg, agent, created, lasttime) ".
              "values('$S->ip', 'counted', '{$S->self}_BB_STARTUP', 1, 'NOT AUTHOREIZED', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");
  }
  $auth = "You are <span style='color: red'>NOT AUTHORIZED</span>";
} else {
  $auth = "You Are Authorized";
}
      
$S->title = "Bridge App Help";
$S->banner = "<h1>$S->title</h1>";
$S->css =<<<EOF
button { background: green; color: white; border-radius: 10px; }
.red { color: red; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<h2 class='red'>First</h2>
<p>You must be authorized to use this site. You must either be located at Bonnie's Home, or know the secret CODE, or have a Fingerprint
in your COOKIEs. If you need to be Authorized please tell Bonnie. <b>$auth</b>.</p>
<hr>
<h2>Add Attendance for {date}</h2>
<p>There are two columns: <b>Name</b> and <b>Present</b>. Use the checkmark box under <b>Present</b>
to indicate that a person was pressent on the date indicated by <b>{date}</b>.
The current Wednsday bridge date will appear instead of <b>{date}</b>, for example <b>Wed 01-05-22</b>.
When you have checked all of the players who were present go to the bottom of the page and press the <button>Submit</button> button.
The next page will show the date the information was posted and for whom. It also show a list of attendance for that date.
At the bottom of the screen is a link <b>Return to Home Page</b>. Press the link when done viewing the results.
<hr>
<h2>Add Donation for {date}</h2>
<p>There are two columns: <b>Name</b> and <b>Donation</b>.</p>
<p>If you click on a <i>name</i> in the <b>Name</b> column you can add
a new date and amount, that is a date that is not the current Wednesday's <b>{date}</b>. The page will show the name of the player and
a date selection input box and an amount input box. Press the <button>Submit</button> button. You are show the player's name the date
and the amount that has been posted. Click on <b>Return to Home Page</b>.</p>

<p>If you enter dollar values under the <b>Donation</b> column
you can post them by going to the bottom of the screen and pressing the <button>Submit</button> button. The page will show the date posted and a
list of playes and the amount the donated. Click on <b>Return to Home Page</b>.</p>
<hr>
<h2>Show Attendance Totals to {date}</h2>
<p>Shows the number of times a player was in attendance since the first game of the season until the current Wednesday.</p>
<hr>
<h2>Show Donation Totals to {date}</h2>
<p>Shows the total donation given at any date until the current Wednesday.</p>
<hr>
<h2>Attendance Spread Sheet</h2>
<p>Shows all the players and dates who were in attendance along with the total for each player and at the bottom a <b>Grand Total</b>.</p>
<hr>
<h2>Donation Spread Sheet</h2>
<p>Shows all the players, dates and donations for all who donated along with the total for each player and at the bottom a <b>Grand Total</b>.</p>
<hr>
<h2>Edit Bridge Names</h2>
<hr>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
