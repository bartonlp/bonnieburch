<?php
// BLP 2023-02-24 - use new approach
// This is the Home page for Bonnie Bridge
// BLP 2022-01-07 -- I should ALWAYS WORK in the bridgetest directory.
// It has a .gitignore that is NOT pushed to 'origin' and a mysitemap.json that is NOT pushed to 'origin'.
// If I NEVER edit anything in the bridge directory everything will work OK.
// NOTE: I do not use 'startup.i.php' here because users can look at this page.

$_site = require_once(getenv("SITELOADNAME"));
$S = new $_site->className($_site);
//vardump("S", $S);
// Define a week and the first wed. we will use.

define("WEEK", 604800);
define("STARTWED", 1641358800);

$unixToday = strtotime("today");
//$unixToday = strtotime('2023-01-04');
$today = date("l F j, Y", $unixToday);

$unixWed = strtotime("Wednesday", $unixToday);
$unixPrevWed = strtotime("previous Wednesday", $unixToday);
$unixNextWed = strtotime("next Wednesday", $unixToday) + 604800;
$nextWed = date('Y-m-d', $unixNextWed);

if($unixToday >= $unixWed && $unixToday < $unixNextWed) {
  $wed = date('Y-m-d', $unixWed);
} else {
  $wed = date("Y-m-d", $unixPrevWed);
  $unixWed = $unixPrevWed;
} 

$fullDate = date("D m-d-y", $unixWed);

$S->title = "Bonnie Bridge";
$S->desc = "Lot of bridge playing here";
$S->banner = "<h1>Bonnie's Bridge Club</h1>";

$S->css =<<<EOF
button { font-size: var(--blpFontSize); border-radius: 10px; background: green; }
button a { text-decoration: none; color: white; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<h2 class="center">Every Wednesday at Carolina Colors</h2>
<hr>
<a href="addAttendance.php">Add Attendance for $fullDate</a><br>
<a href="addDonation.php">Add Donation for $fullDate</a><br>
<a href="showAttendanceTotals.php">Show Attendance Totals to $fullDate</a><br>
<a href="showDonationTotals.php">Show Donation Totals to $fullDate</a><br>
<a href="spreadAttendance.php">Attendance Spread Sheet</a><br>
<a href="spreadDonation.php">Donation Spread Sheet</a><br>
<a href="editBridgeNames.php">Edit Bridge Names</a><br>
<button><a href="help.php">Get Help</a></button>
<hr>
$footer
EOF;
