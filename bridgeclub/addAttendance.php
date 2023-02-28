<?php
// BLP 2023-02-24 - use new approach
// Add Attendance info into weeks table for Wed. games

require("startup.i.php");

// Post the ADD

if($_POST) {
  $_site->footerFile = null;
  
  $S = new $_site->className($_site);
  $S->title = "Attendance Posted";
  $S->banner = "<h1>$S->title</h1>";
  
  $S->css =<<<EOF
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) { text-align: right; }
.posted { font-weight: bold; }
EOF;

  [$top, $footer] = $S->getPageTopBottom();
  
  $ids = $_POST['id']; // id is an array of checked on elements

  // First insert the id and date.
  
  foreach($ids as $k=>$v) {
    $S->query("select name from bridge where id=$k");
    $name = $S->fetchrow('num')[0];
    $names .= "$name, ";
    
    try {
      $sql = "insert into weeks (fid, date, lasttime) values('$k', '$wed', now())";
      $S->query($sql);
    } catch(Exception $e) {
      if($e->getCode() == 1062) { // 1062 is dup key error
        $err .= "This date has already been entered for $name.<br>";
      } else {
        throw($e);
      }
    }
  }

  $sql = "select id, name from bridge order by lname";
  $S->query($sql);
  $r = $S->getResult();
  while([$id, $name] = $S->fetchrow($r, 'num')) {
    $sql = "select count(*) from weeks where fid=$id and date <= '$wed'";
    $S->query($sql);
    $cnt = $S->fetchrow('num')[0];
    $total += $cnt;
    $list .= "<tr><td>$name</td><td>$cnt</td></tr>";
  }

  // POSTED page with totals

  $name = "<span class='posted'>For: </span> " . rtrim($names, ', ') . "<br>";

  $err = $err ? "<br>$err" : null;
  
  echo <<<EOF
$top
<hr>
<h1>Data Posted $today</h1>
$name
$err
<h1>Totals as of $fullDate</h1>
<table id="week-posted" border="1">
<thead>
<tr><th></th><th>Count</th></tr>
</thead>
<tbody>
$list
</tbody>
<tfoot>
<tr style="background: yellow;"><th>Total</th><th>$total</th></tr>
</tfoot>
</table>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

// First Page

$S = new $_site->className($_site);

$S->title = "Add Bridge Attendance";
$S->banner = "<h1>$S->title</h1>";

$S->desc = "Lot of bridge playing here";
$S->css =<<<EOF
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) input { text-align: right; width: 100px; }
button { font-size: var(--blpFontSize); border-radius: 10px; padding: 5px; color: white; background: green; }
input[type='checkbox'] { width: 30px; height: 30px; }
#week tbody td:last-of-type { text-align: center; }
#week { border-collapse: collapse; }
#week tbody tr { border: 1px solid black; }
#week tbody td:first-of-type { padding: 0 5px; width: 400px; border-right: 1px solid black; }
EOF;
  
[$top, $footer] = $S->getPageTopBottom();

$S->query("select id, name from bridge order by lname");
while([$id, $name] = $S->fetchrow('num')) {
  $names .= <<<EOF
<tr><td>$name</td>
<td><input type='checkbox' name='id[$id]'></td></tr>
EOF;
}

echo <<<EOF
$top
<hr>
<h1>$fullDate</h1>
<p>Today is $today</p>
<p>To correct previous Wednesday's attendance go to <a href="spreadAttendance.php">Attendance Spread Sheet</a>.</p>
<form method="post">
<table id="week">
<thead>
<tr><th></th><th>Present</th><tr>
</thead>
<tbody>
$names
</tbody>
</table>
<br>
<button name="submit">Submit</button>
</form>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
