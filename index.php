
<?php
// BLP 2023-02-24 - use new approach

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);

$S->title = "Bonnie's Home Page";

$S->h_inlineScript = <<<EOF
var thesite = '$S->siteName';
var thisip = '$S->ip';
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<h2 class="center">My Own Home Page -- Oh Boy</h2>
<hr>
<a href="bridgeclub/bridgeclub.php">Go To Bridge Club</a><br>
<a href="marathon/marathon.php">Go To Marathon Bridge</a>
<hr>
$footer
EOF;
