
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
<a href="bridgeclub/bridgeclub.php">Bridge Club</a><br>
<a href="marathon/marathon.php">Marathon Bridge</a><br>
<a href="mitchell/family.php">The Mitchell Family</a><br>
<a href="Grandma Journal.pdf">Grandma Journal</a>
<hr>
$footer
EOF;
