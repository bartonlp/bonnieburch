<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new Database($_site);

$months = ['September', 'October', 'November', 'December', 'January', 'February', 'March', 'April', 'May'];

// Delete everything from scores.

$S->sql("delete from scores");

// Now recreate an empty table

$n = 0;

foreach($months as $k=>$month) {
  for($i=1; $i<13; ++$i) {
    $n += $S->sql("insert into scores values($i, '$month', $k, 0, null, null)");
  }
}

echo "Done. $n inserts<br>";
