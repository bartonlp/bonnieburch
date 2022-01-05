<?php
$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$h->title = "Bonnie Brideg";
$h->desc = "Lot of bridge playing here";
$h->banner = "<h1>Bonnie's Bridge Home Page</h1>";

[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
<a href="addBridgeWeek.php">Add New Info</a><br>
<a href="showBridgeTotals.php">Show Totals to Date</a><br>
<a href="spreadsheet.php">Spread Sheet</a><br>
<hr>
$footer
EOF;
