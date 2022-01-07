<?php
// This is the Home page for Bonnie Bridge
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$h->title = "Bonnie Bridge";
$h->desc = "Lot of bridge playing here";
$h->banner = "<h1>Bonnie's Bridge Home Page</h1>";

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
<a href="addBridgeWeek.php">Add Attendance Info</a><br>
<a href="addmoney.php">Add Donation Info</a><br>
<a href="showBridgeTotals.php">Show Attendance Totals to Date</a><br>
<a href="showdonationTotals.php">Show Donation Totals to Date</a><br>
<a href="spreadsheet.php">Attendance Spread Sheet</a><br>
<a href="spreadmoney.php">Donation Spread Sheet</a><br>
<a href="editnames.php">Edit Names</a><br>
<hr>
$footer
EOF;
