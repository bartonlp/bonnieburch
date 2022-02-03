<?php
// Footer file
// BLP 2021-12-15 -- add geo.js
// BLP 2021-10-24 -- counterWidget and lastmod are passed from getPageFooter()
// BLP 2018-02-24 -- added 'script' just before </body>

// getPageFooter($b) uses only the object $b.

return <<<EOF
<footer>
<h2><a target="_blank" href='aboutwebsite.php'>About This Site</a></h2>
<div id="address">
<address>
  Copyright &copy; $this->copyright<br>
$this->address<br>
<a href='mailto:$this->EMAILADDRESS'>$this->EMAILADDRESS</a>
</address>
</div>
{$b->msg}
{$b->msg1}
<br>
$counterWigget
$lastmod
{$b->msg2}
</footer>
$geo
{$b->script}
</body>
</html>
EOF;
