<?php
$_site = require_once getenv("SITELOADNAME");
$S = new SiteClass($_site);

$S->banner = "<h1>nextpage</h1>";

echo <<<EOF
<h1>This is a test</h1>
EOF;
