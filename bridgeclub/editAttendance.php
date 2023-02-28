<?php
// BLP 2023-02-24 - use new approach
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
// This file is called by spreadAttendance.php. It should NEVER be run on its own (see 'Go Away' as
// default).

require("startup.i.php");

$S = new $_site->className($_site);

if($which = $_POST['page']) {
  $S->title = "$which Done";
  $S->desc = $whcih;
  $S->banner = "<h1>Record {$which}ed</h1>";

  $id = $_POST['id'];
  $name = $_POST['name'];
  $week = $_POST['week'];
  
  switch($which) {
    case 'Delete':
      $S->query("delete from weeks where fid=$id and date='$week'");
      break;
    case 'Add':
      $S->query("insert ignore into weeks (fid, date, lasttime) values($id, '$week', now())");
      break;
    default:
      echo "<h1>Go Away</h1>";
      exit();
  }

  $week = date("l F j, Y", strtotime($week));

  [$top, $footer] = $S->getPageTopBottom();

  echo <<<EOF
$top
<hr>
<p>The record for $name for week $week has been {$which}ed.</p>
<a href="spreadAttendance.php">Return to Attendance Spread Sheet</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

if($_GET) {
  $week = $_GET['week'];
  $id = $_GET['id'];
  $page = $_GET['page'];

  $S->query("select name from bridge where id=$id");
  $name = $S->fetchrow('num')[0];

  $date = date("m-d-Y", strtotime($week));
  
  switch($page) {
    case 'delete':
      $msg = "Are you sure you want to delete week $week for $name?";
      break;
    case 'add':
      $msg = "Are you sure you want to add that $name attended for week $week?";
      break;
    default:
      echo "<h1>Go Away</h1>";
      exit();
  }

  $page = ucfirst($page);
  
  $S->title = "Are You Sure";
  $S->desc = $page;
  $S->banner = "<h1>Are You Sure?</h1>";
  $S->css =<<<EOF
form button {
  background: red;
  color: white;
  font-size: var(--blpFontSize);
  padding: 3px 10px;
  border-radius: 10px;
  border: 2px solid black;
}
form a {
  font-size: var(--blpFontSize);
  text-decoration: none;
  padding: 1px 10px;
  border: 2px solid black;
  border-radius: 10px;
  color: white;
  background: green;
}
EOF;

  [$top, $footer] = $S->getPageTopBottom();

  // $page is now Capitalized!
  
  echo <<<EOF
$top
<hr>
<h2>$msg</h2>
<form method="post">
<button type="submit" name="page" value="$page">Yes $page</button>&nbsp;
<a href="spreadAttendance.php">NO, Go Back to Bridge Attendance Spread Sheet</a>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="name" value="$name">
<input type="hidden" name="week" value="$week">
</form>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}
