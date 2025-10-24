<?php
$_site = require_once getenv("SITELOADNAME");
ErrorClass::setDevelopment(true);
$S = new SiteClass($_site);

if($_GET['page'] == '4') {
  $S->b_inlineScript = <<<EOF
sessionStorage.setItem('sentEmailDone', 'yes');
EOF;
  
  $S->banner = "<h1>Next Page (4)</h1>";
  [$top, $bottom] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<p>This is to done</p>
<a href="test1.php">Done go to one</a>
$bottom
EOF;
  
  exit;
}

// Page 2

if($_GET['page'] == '2') {
  $S->banner = "<h1>Page 2</h1>";
  [$top, $bottom] = $S->getPageTopBottom();

  echo <<<EOF
$top
<form method="get">
<p>Starts text & names</p>
Enter <button name="page" value="3">Submit</button><br>
</form>
$bottom
EOF;
  exit;
}

// Page 3

if($_GET['page'] == '3') {
  $S->b_inlineScript = <<<EOF
$(document).on("click", "#page2", function(e) {
  e.preventDefault();
  console.log("I have done 'back'");
  // Give the browser time to finalize pushState before navigating
  setTimeout(() => {
    history.back();
  }, 50); // 50 ms is usually enough
});
EOF;
  
  $S->banner = "<h1>Page 3</h1>";
  [$top, $bottom] = $S->getPageTopBottom();

  echo <<<EOF
$top
<form method="get">
<p>Go back to Page 2 (text & name)</p>
<button id='page2'>back to text & name</button>
<p>Go to 'nextpage' done with Email</p>
<button name="page" value="4">next page</button>
</form>
$bottom
EOF;
  exit;
}

// Page 1

$S->banner = "<h1>PAGE (1)</h1>";
[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
<form method="get">
Enter <button name="page" value='2'>Submit</button>
<p>Starts over</p>
</form>
$bottom
EOF;

