<?php
// Shows a spread sheet of donations
/*
CREATE TABLE `money` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `money` decimal(7,0) DEFAULT '0',
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);

// Check if user is Authorized
$finger = $_COOKIE['BLP-Finger'];
$bonnieFingers = require("/var/www/bartonphillipsnet/bonnieFinger.php");

if(array_intersect([$finger] , $bonnieFingers)[0] === null) {
  echo <<<EOF
<h1>You are NOT AUTHORIZED</h1>
EOF;
  exit();
}
// End of Check if user is Authorized

$unixToday = strtotime("today");
//$unixToday = strtotime('2022-02-15');
$today = date("l F j, Y", $unixToday);

$unixWed = strtotime("Wednesday", $unixToday);
$unixPrevWed = strtotime("previous Wednesday", $unixToday);
$unixNextWed = strtotime("next Wednesday", $unixToday) + 604800;
$nextWed = date('Y-m-d', $unixNextWed);

if($unixToday >= $unixWed && $unixToday < $unixNextWed) {
  $wed = date('Y-m-d', $unixWed);
} else {
  $wed = date("Y-m-d", $unixPrevWed);
  $unixWed = $unixPrevWed;
} 

$fullDate = date("l F j, Y", $unixWed);

$S = new $_site->className($_site);

if($_POST['page'] == 'postit') {
  $ids = $_POST['money'];

  foreach($ids as $id=>$money) {
    if($money == '') continue;
    $S->query("select name from bridge where id=$id");
    $r = $S->getResult();
    $names[] = $S->fetchrow($r, 'num')[0] . " \${$money}";
    $money = preg_replace("~,~", '', $money); // For the javascript currency function
    
    $S->query("insert into money (fid, date, money, lasttime) values($id, '$wed', $money, now()) ".
              "on duplicate key update money=$money, lasttime=now()");
    ++$i;
  }

  $h->title = "Add Money Posted";
  $h->banner = "<h1>Add Money Posted</h1>";
  
  [$top, $footer] = $S->getPageTopBottom($h);
  $name = implode(',<br>', $names);
  $date = date("l F j, Y", $unixWed);
  echo <<<EOF
$top
<h2>Data Posted $date:<br>$name</h2>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
  exit();
}

// This is the post from the $_GET() below

if($_POST['page'] == 'post') {
  [$top, $footer] = $S->getPageTopBottom($h);
  
  $id = $_POST['id'];
  $money = $_POST['money'];
  $date = $_POST['date'];
  $name = $_POST['name'];

  $money = preg_replace("~,~", '', $money); // When we use currency logic
  
  $S->query("insert into money (fid, date, money, lasttime) values($id, '$date', '$money', now()) ".
            "on duplicate key update money='$money', lasttime=now()");

  $money = "$". number_format($money);
  $date = date("l M j, Y", strtotime($date));
  echo <<<EOF
$top
<h1>Data POSTED for $name on $date for $money</h1>
<a href="/bridge">Return to Home Page</a>
$footer;
EOF;
  exit();
}

$h->css = <<<EOF
<style>
  input { text-align: right; }
</style>
EOF;

$b->script = <<<EOF
<script src="addmoney.js"></script>
EOF;

// This is via JavaScript. When someone click on the 'name' field in
// the table it triggers a GET call via
// location.replace('addmoney.php?page=add&id="+id);

if($_GET['page'] == 'add') {
  [$top, $footer] = $S->getPageTopBottom($h, $b);
  
  $id = $_GET['id'];
  
  $S->query("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];
  // Show a form and then goto the 'page=post' above.
  
  echo <<<EOF
$top
<h2>Donation from $name</h2>
<p>Select the Date and amount.</p>
<form method="post">
<table id="donate">
<tr>
<td>Date</td><td><input type="date" name="date"></td></tr>
<td>Amount</td><td><input type="text" data-type="currency" name="money"></td></tr>
</table>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="name" value="$name">
<button type="submit" name="page" value="post">Submit</button>
</form>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
  exit();
}

$S->query("select id, name from bridge order by lname");
$r = $S->getResult();

while([$id, $name] = $S->fetchrow($r, 'num')) {
  $S->query("select money from money where fid=$id and date='$wed'");
  $money = $S->fetchrow('num')[0];
  $total += $money;
  $lines .= "<tr><td data-id='$id'>$name</td><td><input type='text' data-type='currency' name='money[$id]' value='$money'></td></tr>";
}

$h->css .=<<<EOF
<style>
  #donate-tbl { border: 1px solid black; }
  #donate-tbl td:first-of-type { border: 1px solid black; }
  #donate-tbl td, #donate-tbl th { border-bottom: 1px solid black; }
  .tfoot { border: 1px solid black; background: yellow; }
  .total { text-align: right; padding-right: 5px; }
</style>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

$total = number_format($total);

echo <<<EOF
$top
<h2>$fullDate</h2>
<p>Today is $today</p>
<p>To add a date other than Wednsday's Bridge Date click the 'Name'.</p>
<form method="post">
<table id="donate-tbl">
<thead>
<tr><th>Name</th><th>Donation</th></tr>
</thead>
<tfoot>
<tr><th class="tfoot">Total</th><th class="tfoot total">$total</th></tr>
</tfoot>
<tbody>
$lines
</tbody>
</table>
<button type="submit" name="page" value="postit">Submit</button>
</form>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
