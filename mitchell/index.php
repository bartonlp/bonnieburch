<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);

$S->banner = "<h1>Main Index for <i>bonnieburch.com</i></h1>";

header("refresh:5;url=https://bonnieburch.com");

[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<p>This is <i>bonnieburch.com</i>.</p>
$bottom
EOF;
