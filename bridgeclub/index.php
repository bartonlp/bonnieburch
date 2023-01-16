<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
$h->title = "Not Authorized";
$h->banner = "<h1>NOT AUTHORIZED</h1>";
[$top, $footer] = $S->getPageTopBottom($h);

echo <<<EOF
$top
<hr>
<p>This subdirectory is protected. You are not authorized.</p>
<a href="https://www.bonnieburch.com">Go to our homepage</a>
<hr>
$footer
EOF;
