<?php
// BLP 2023-02-24 - use new approach
// Shows a spread sheet of donations

require("startup.i.php");

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

  $S->title = "Add Donation Posted";
  $S->banner = "<h1>$S->title</h1>";
  
  [$top, $footer] = $S->getPageTopBottom();
  $name = implode(',<br>', $names);
  $date = date("l F j, Y", $unixWed);
  echo <<<EOF
$top
<hr>
<h2>Data Posted $date:<br>$name</h2>
<a href="addDonation.php">Return to Add Donation Page</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

// This is the post from the $_GET() below

if($_POST['page'] == 'post') {
  $S->title = "Custom Week";
  $S->banner = "<h1>$S->title</h1>";
  
  [$top, $footer] = $S->getPageTopBottom();
  
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
<hr>
<h2>Data POSTED for $name on $date for $money</h2>
<a href="addDonation.php">Return to Add Donation Page</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer;
EOF;
  exit();
}

$S->css = <<<EOF
input { text-align: right; }
EOF;

$S->b_script = <<<EOF
<script src="addDonation.js"></script>
EOF;

// This is via JavaScript. When someone click on the 'name' field in
// the table it triggers a GET call via
// location.replace('addmoney.php?page=add&id="+id);

if($_GET['page'] == 'add') {
  $S->title = "Donation for Week";
  $S->banner = "<h1>$S->title</h1>";
  $S->css .= "button { background: green; color: white; border-radius: 10px; }";
  
  [$top, $footer] = $S->getPageTopBottom();
  
  $id = $_GET['id'];
  
  $S->query("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];
  // Show a form and then goto the 'page=post' above.
  
  echo <<<EOF
$top
<hr>
<h2>Donation from $name</h2>
<p>Select the Date and amount.</p>
<form method="post">
<table id="donate">
<tr>
<td>Date</td><td><input type="date" name="date" required></td></tr>
<td>Amount</td><td><input type="text" data-type="currency" name="money" required></td></tr>
</table>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="name" value="$name">
<button type="submit" name="page" value="post">Submit</button>
</form>
<br>
<a href="addDonation.php">Return to Add Donation Page</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
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

$S->title = "Add Donation";
$S->banner = "<h1>$S->title</h1>";

$S->css .=<<<EOF
  input[data-type='currency'] { font-size: var(--blpFontSize); width: 150px; border: 0; padding-right: 5px;}
  button {
    font-size: var(--blpFontSize);
    border-radius: 10px;
    padding: 5px;
    color: white;
    background: green;
  }
  #donate-tbl { border: 1px solid black; }
  #donate-tbl { border-collapse: collapse; }
  #donate-tbl tbody tr { border: 1px solid black; }
  #donate-tbl tbody td:first-of-type { padding: 0 5px; width: 400px; border-right: 1px solid black; } 
  .tfoot { border: 1px solid black; background: yellow; }
  .total { text-align: right; padding-right: 5px; }
EOF;

[$top, $footer] = $S->getPageTopBottom();

$total = number_format($total);

echo <<<EOF
$top
<hr>
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
<br>
<button type="submit" name="page" value="postit">Submit</button>
</form>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
