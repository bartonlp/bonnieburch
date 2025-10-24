<?php
$_site = require_once getenv("SITELOADNAME");
ErrorClass::setDevelopment(true);
$S = new SiteClass($_site);
$T = new dbTables($S);

if($_GET['cnt']) {
  $cnt = $_GET['cnt'];
  $S->sql("select Subject, Text from bonnie.backups where cnt=$cnt");
  [$Subject, $Text] = $S->fetchrow("num");
  echo <<<EOF
<style>td, th { padding: 0 5px; }</style>
<p>This is changed to the 'lasttext.data'.</p>
<hr>
<table border='1'>
<tr><th>Subject</th><td>$Subject</td></tr>
<tr><th>Text</th><td>$Text</th</tr>
</table>
EOF;
  
  file_put_contents("./data/lasttext.data", "$Subject\n$Text");
  exit;
}

$S->banner = "<h1>Backups</h1>";

$S->css = <<<EOF
td, th { padding: 0 5px; }
.cnt { text-align: right; cursor: pointer; }
EOF;

$callback = function(&$disp) {
  $disp = preg_replace_callback("~<td class='cnt'>(.*?)</td>~", function($m) {
    return  "<td class='cnt'><a href='backups.php?cnt=$m[1]'>$m[1]</a></td>";
  }, $disp);
};
    
$sql = "select cnt, Subject, Text from bonnie.backups";
$tbl = $T->maketable($sql, ['callback'=>$callback, 'attr'=>['id'=>'backups', 'border'=>'1']], true)[0];

[$top, $bottom] = $S->getPageTopBottom();

echo <<<EOF
$top
$tbl
$bottom
EOF;
