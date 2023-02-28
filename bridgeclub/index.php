<?php
// BLP 2023-02-24 - use new approach

$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
$S->title = "Not Authorized";
$S->banner = "<h1>NOT AUTHORIZED</h1>";
[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<p>This subdirectory is protected. You are not authorized.</p>
<a href="https://www.bonnieburch.com">Go to our homepage</a>
<hr>
$footer
EOF;
