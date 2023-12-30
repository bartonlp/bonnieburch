<?php
// BLP 2023-02-24 - new approach
// Edit or delete a Donation entry.
// Called from spreadDonation.php. This should never be run by itself (see 'Go Away' at the bottom).

require("startup.i.php");

$S = new $_site->className($_site);
  
if($_POST['page'] == 'post') {
  $S->title = "Edit Donation Post";
  $S->banner = "<h1>Edit Donation Post</h1>";
  [$top, $footer] = $S->getPageTopBottom();
              
  $money = preg_replace("~,~", "", $_POST['money']);
  $id = $_POST['id'];
  $week = $_POST['week'];
  $name = $_POST['name'];
  
  $S->sql("update money set money='$money', lasttime=now() where fid=$id and date='$week'");
  $money = "$" . number_format($money);
  $week = date("l F j, Y", strtotime($week));
  
  echo <<<EOF
$top
<hr>
<h2>$name's record for week $week has been updated to $money</h2>
<a href="spreadDonation.php">Go Back to Bridge Donation Spread Sheet</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

if($_POST['page'] == 'delete') {
  $S->title = "Edit Donation Delete";
  $S->banner = "<h1>Edit Donation Delete</h1>";
  [$top, $footer] = $S->getPageTopBottom();
                 
  $id = $_POST['id'];
  $week = $_POST['week'];
  $name = $_POST['name'];
  $money = "$". number_format($_POST['money']);
  
  $S->sql("delete from money where fid=$id and date='$week'");
  $week = date("l F j, Y", strtotime($week));

  echo <<<EOF
$top
<hr>
<h2>$name's record for week $week for $money has been deleted</h2>
<a href="spreadDonation.php">Go Back to Bridge Donation Spread Sheet</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}
  
if($_GET['page'] == 'edit') {
  $S->title = "Edit Donation";
  $S->banner = "<h1>Edit/Delete Donation</h1>";
  $S->css =<<<EOF
  input { text-align: right; }
  button { border-radius: 5px; padding: 5px; }
  #editButton { color: white; background: green; }
  #deleteButton { color: white; background: red; }
  h2 { margin-bottom: 0px; }
EOF;

  $S->b_script .= "<script src='addDonation.js'></script>"; //  BLP 2022-10-02 - fixed, was addAttendance.js which does not exist!
  
  [$top, $footer] = $S->getPageTopBottom();
                 
  $id = $_GET['id'];
  $week = $_GET['week'];
  $S->sql("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];
  
  $S->sql("select money from money where fid=$id and date='$week'");
  $money = $S->fetchrow('num')[0];
  $date = date("l F j, Y", strtotime($week));
  $m = "$". number_format($money);
  
  echo <<<EOF
$top
<hr>
<h2>$name's record for week $date with a donation of $m</h2>
<hr>
<h2>Edit Record</h2>
<form method='post'>
<input type="text" data-type="currency" name="money" value="$money"><br>
<input type="hidden" name="name" value="$name">
<input type="hidden" name="id" value="$id">
<input type="hidden" name="week" value="$week">
<button id="editButton" type="submit" name="page" value="post">Submit</button>
</form>
<hr>
<form method="post">
<input type="hidden" name="money" value="$money">
<input type="hidden" name="name" value="$name">
<input type="hidden" name="id" value="$id">
<input type="hidden" name="week" value="$week">
<br>
<h2>Delete Record</h2>
<button id="deleteButton" type="submit" name="page" value="delete">Delete Item</button>
</form>
<hr>
<a href="spreadDonation.php">Go Back to Bridge Donation Spread Sheet</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

echo "<h1>Go Away</h1>";

